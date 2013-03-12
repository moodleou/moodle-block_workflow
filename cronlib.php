<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Script to finish active steps on cron
 *
 * @package   block_workflow
 * @copyright 2012 The Open Univeersity
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once(dirname(__FILE__) . '/editstep_form.php');
require_once($CFG->libdir . '/adminlib.php');

class block_workflow_automatic_step_finisher {

    public function cron($startinghour = 1, $finishinghour = 7, $lasthours = 14) {
        mtrace('Workflow: Automatic step finisher started at '.date('H:i:s') . ' on ' . date('jS \of F Y'));

        // Get all active steps which are set to finish automatically.
        if (!$activesteps = $this->get_all_active_steps()) {
            mtrace('Could not find any active steps set to finish automatically.');
            mtrace('Automatic step finisher stopped at '.date('H:i:s') . ' on ' . date('jS \of F Y'));
            return;
        }

        // Get all active steps which are ready to finish automatically.
        if (!$readyactivesteps = $this->get_ready_autofinish_steps($activesteps)) {
            mtrace('Could not find any active steps ready to be finished.');
            mtrace('Automatic step finisher stopped at '.date('H:i:s') . ' on ' . date('jS \of F Y'));
            return;
        }

        // If debugging, do not check for lastrun and the time interval to run the cron.
        if (debugging()) {
            mtrace('Automatic step finisher runs from 1 to 7 daily. However, if debugging is set,
                                                    it can runs as often as you run the cron.');
            // Finish steps automatically.
            mtrace($this->finish_steps_automatically($readyactivesteps));
            mtrace('Workflow: Automatic step finisher completed at '.date('H:i:s') . ' on ' . date('jS \of F Y'));
            return;
        }

        // Check the last run time.
        $lastrun = get_config('block_workflow', 'lastrun');

        // If lastrun is not set (first time running this cron) set it to zero.
        if (!$lastrun) {
            $lastrun = 0;
        }

        // Setup current time and current hour.
        $currenttime = time();
        $currenthour = date('H');

        // Stop if cron was run within the $lasthours.
        if (($currenttime <= ($lastrun + ($lasthours * 60 * 60)) && ($lastrun != 0))) {
            mtrace("Workflow: Automatic step finisher stopped, because it was run within the last $lasthours hours.");
            return;
        }

        // Stop if the currenttime is not within the starting and finishing time.
        $timerange = false;
        if ($startinghour < $finishinghour) {
            if (($currenthour >= $startinghour) && ($currenthour < $finishinghour)) {
                $timerange = true;
            }
        } else if ($startinghour > $finishinghour) {
            if (($currenthour >= $startinghour) || ($currenthour < $finishinghour)) {
                $timerange = true;
            }
        }

        // The current time is out of the time-range.
        if (!$timerange) {
            mtrace('Workflow: Automatic step finisher stopped, because the time is not within the cron time-range (' .
                    $startinghour . '-' .  $finishinghour . ')');
            return;
        }

        // Finish steps automatically.
        mtrace($this->finish_steps_automatically($readyactivesteps));

        // Set the time for this run (This will be the lastrun for the next execution).
        set_config('lastrun', time(), 'block_workflow');

        mtrace('Workflow: Automatic step finisher completed at '.date('H:i:s') . ' on ' . date('jS \of F Y'));
    }

    /**
     * Get all active steps which are set to finish automatically.
     *
     */
    protected function get_all_active_steps() {
        global $DB;
        $sql = "SELECT state.id AS stateid, state.stepid,
                        wf.id AS workflowid, wf.appliesto,
                        step.name AS stepname, step.autofinish, step.autofinishoffset,
                        c.id AS courseid, cm.instance AS moduleid
                FROM {block_workflow_step_states} state
                LEFT JOIN {block_workflow_steps} step ON step.id = state.stepid
                LEFT JOIN {block_workflow_workflows} wf ON wf.id = step.workflowid
                LEFT JOIN {context} ctx ON ctx.id = state.contextid
                LEFT JOIN {course} c ON c.id = ctx.instanceid
                LEFT JOIN {course_modules} cm ON cm.id = ctx.instanceid AND wf.appliesto <> 'course'

                WHERE step.autofinish != :autofinish
                    AND step.autofinishoffset != :autofinishoffset
                    AND state.state = :state
                    AND (ctx.contextlevel = :coursecotext OR ctx.contextlevel = :modulecontext)
                    ORDER BY state.id ASC";

        $options = array('autofinish' => '',
                        'autofinishoffset' => 0,
                        'state' => BLOCK_WORKFLOW_STATE_ACTIVE,
                        'coursecotext' => CONTEXT_COURSE,
                        'modulecontext' => CONTEXT_MODULE);

        return $DB->get_records_sql($sql, $options);
    }

    /**
     * Get all active steps which are ready to be finished automatically
     * @param object $activesteps, array of all active steps
     * @return array of active steps which are ready to be finished automatically
     */
    protected function get_ready_autofinish_steps($activesteps) {
        $readyautofinishsteps = array();
        if ($activesteps) {
            $now = time();
            foreach ($activesteps as $key => $activestep) {

                // If the step is not ready to be finished automayically, move to the next active step.
                if ($this->is_ready_for_autofinish($activestep, $now)) {
                    $readyautofinishsteps[$key] = $activestep;
                }
            }
        }
        return $readyautofinishsteps;
    }

    /**
     * Check whether the active step is ready to be finished automatically.
     * @param object $activestep, the active step object
     * @param int $now, curent time
     * @return boolean
     */
    protected function is_ready_for_autofinish($activestep, $now) {
        global $DB;
        list($dbtable, $dbfield) = explode('_', $activestep->autofinish);

        // Set variable to the course id or module id.
        if ($dbtable === 'course') {
            $id = $activestep->courseid;
        } else {
            $id = $activestep->moduleid;
        }
        $field = $DB->get_field($dbtable, $dbfield, array('id' => $id));

        $finishtime = $field + $activestep->autofinishoffset;

        if ($finishtime < $now) {
            return true;
        }
        return false;
    }

    /**
     * Finishes the steps which are ready to finish automatically and adds comments.
     *
     * @param array $readyautofinishsteps
     */
    protected function finish_steps_automatically($readyautofinishsteps) {

        // There are not any active steps.
        if (!$readyautofinishsteps) {
            return false;
        }

        $trace = null;
        foreach ($readyautofinishsteps as $activestep) {
            // Automatically finish this step.
            $state = new block_workflow_step_state($activestep->stateid);

            // Add a comment and finsh the step automatically.
            $newcomment = get_string('finishstepautomatically',  'block_workflow',
                                        date('H:i:s') . ' on ' . date('jS \of F Y'));
            $state->finish_step($newcomment, FORMAT_PLAIN);

            // Cron setup user.
            cron_setup_user();

            $trace .= "Added the comment '$newcomment \n";
            $trace .= "The step '$activestep->stepname' with id=$activestep->stepid is now completed.\n";
        }
        return $trace;
    }

}
