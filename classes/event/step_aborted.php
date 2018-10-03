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

namespace block_workflow\event;


/**
 * Event implementation for workflow and step aborted
 *
 * @package   block_workflow
 * @copyright 2018 IT Kartellet ApS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * This event is triggered when the state of a workflow-step is set to aborted.
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - int stepid: The ID of the step which has its status changed.
 *      - int stepstateid: The ID of the this state-change.
 *      - int workflowid: The ID of the workflow this step is part of.
 *      - int timestamp: The timestamp of when the state-change occurred (same timestamp as written to the state_change table)
 *      - string stepname: The name of the step which has its status changed.
 *      - string workflowname: The name of the workflow this step is part of.
 * }
 */
class step_aborted extends \core\event\base {

    protected function init() {
        $this->data['objecttable'] = 'block_workflow_steps';
        $this->data['crud'] = 'u'; // // c(reate), r(ead), u(pdate), d(elete). The status of a step has changed, so this is an "update".
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /** Convenience factory-method to create an event from a step_state object.
     *
     *  @param \block_workflow_step_state $stepstate The step-state with the data to create the event from.
     *  @return \core\event\base
     */
    public static function create_from_step_state(\block_workflow_step_state $stepstate) {
        return self::create([
            'context' => $stepstate->context(),
            'objectid' => $stepstate->stepid,
            'other' => [
                'stepid' => $stepstate->stepid,
                'stepstateid' => $stepstate->id,
                'workflowid' => $stepstate->step()->workflowid,
                'timestamp' => $stepstate->timemodified,
                'stepname' => $stepstate->step()->name,
                'workflowname' => $stepstate->step()->workflow()->name
            ]
        ]);
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventstepaborted', 'block_workflow');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     * (Note: These texts are note stored in the database, but read every time the log is shown, so they must be backwards compatible)
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' aborted the step '".$this->other['stepname']."' (id = ".$this->other['stepid'].").";
    }

    /**
     * Returns a Moodle URL where the event can be observed afterwards. Can be null, if no valid location is present.
     * @return null|\moodle_url
     */
    public function get_url() {
        return new \moodle_url('/blocks/workflow/overview.php', ['contextid' => $this->contextid, 'workflowid' => $this->other['workflowid']]);
    }

    /**
     * Custom validation.
     *
     * Here we check that the extra custom fields for this events (described in the class phpdoc comment) were actually given as parameters to the event when it was triggered.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->other['stepid'])) {
            throw new \coding_exception('The \'stepid\' value must be set in \'other\' of the event.');
        }

        if (!isset($this->other['stepstateid'])) {
            throw new \coding_exception('The \'stepstateid\' value must be set in \'other\' of the event.');
        }

        if (!isset($this->other['workflowid'])) {
            throw new \coding_exception('The \'workflowid\' value must be set in \'other\' of the event.');
        }

        if (!isset($this->other['timestamp'])) {
            throw new \coding_exception('The \'timestamp\' value must be set in \'other\' of the event.');
        }

        if (!isset($this->other['stepname'])) {
            throw new \coding_exception('The \'stepname\' value must be set in \'other\' of the event.');
        }

        if (!isset($this->other['workflowname'])) {
            throw new \coding_exception('The \'workflowname\' value must be set in \'other\' of the event.');
        }
    }
}
