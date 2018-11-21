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
 * Workflow block library code. This file defines some miscellaneous things, and
 * then includes all the workflow classes.
 *
 * @package   block_workflow
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once($CFG->dirroot . '/blocks/workflow/classes/exceptions.php');
require_once($CFG->dirroot . '/blocks/workflow/classes/workflow.php');
require_once($CFG->dirroot . '/blocks/workflow/classes/step.php');
require_once($CFG->dirroot . '/blocks/workflow/classes/step_state.php');
require_once($CFG->dirroot . '/blocks/workflow/classes/email.php');
require_once($CFG->dirroot . '/blocks/workflow/classes/todo.php');
require_once($CFG->dirroot . '/blocks/workflow/classes/command.php');
require_once($CFG->dirroot . '/blocks/workflow/classes/command_assignrole.php');
require_once($CFG->dirroot . '/blocks/workflow/classes/command_email.php');
require_once($CFG->dirroot . '/blocks/workflow/classes/command_override.php');
require_once($CFG->dirroot . '/blocks/workflow/classes/command_setactivitysetting.php');
require_once($CFG->dirroot . '/blocks/workflow/classes/command_setactivityvisibility.php');
require_once($CFG->dirroot . '/blocks/workflow/classes/command_setcoursevisibility.php');
require_once($CFG->dirroot . '/blocks/workflow/classes/command_setactivitylinkedsetting.php');


/** @var string An active state for a step_state. */
define('BLOCK_WORKFLOW_STATE_ACTIVE',       'active');

/** @var string A completed state for a step_state. */
define('BLOCK_WORKFLOW_STATE_COMPLETED',    'completed');

/** @var string An aborted state for a step_state. */
define('BLOCK_WORKFLOW_STATE_ABORTED',      'aborted');

/** @var int The enabled state for a workflow. */
define('BLOCK_WORKFLOW_ENABLED',            0);

/** @var int The obsolste state for a workflow. */
define('BLOCK_WORKFLOW_OBSOLETE',           1);

/** @var int The maximum comment length to be disapled in block. */
define('BLOCK_WORKFLOW_MAX_COMMENT_LENGTH', 200);

/**
 * Return an list of all of the workflows ordered by obsolete status, then appliesto, and finally
 * the shortname
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @return  array   Containing a list of the all workflows
 */
function block_workflow_load_workflows() {
    global $DB;
    return $DB->get_records('block_workflow_workflows', null, 'obsolete ASC, appliesto ASC, shortname ASC');
}

/**
 * Return the list of modules that workflows may apply to (appliesto)
 *
 * The list contains course as the first item, plus every installed plugin
 * as returned by {@link get_plugin_list}.
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @return  array   Associative array to fill an appliesto select
 */
function block_workflow_appliesto_list() {
    // Applies to should contain courses ...
    $return = array('course' => get_string('course'));

    // ... and any installed modules.
    $mods = get_plugin_list('mod');
    foreach ($mods as $name => $path) {
        $return[$name] = get_string('pluginname', 'mod_' . $name);
    }
    return $return;
}

/**
 * Return the formatted language string for the specified $appliesto
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @param   string  $appliesto  The language key
 * @return  string              The formatted version for the $appliesto
 */
function block_workflow_appliesto($appliesto) {
    if ($appliesto == 'course') {
        return get_string($appliesto);
    }
    return get_string('pluginname', 'mod_' . $appliesto);
}

/**
 * Returns a list of the roles available at the specified contextlevel
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @param   string  $contextlevel   The contextlevel
 * @return  mixed                   The database results, or null if no result was found
 */
function block_workflow_contextlevel_roles($contextlevel) {
    global $DB;

    $sql = "SELECT DISTINCT roles.*
            FROM {role_context_levels} cl
            INNER JOIN {role} roles ON roles.id = cl.roleid
            WHERE cl.contextlevel = ?
            ORDER BY roles.sortorder ASC
            ";
    return role_fix_names($DB->get_records_sql($sql, array($contextlevel)));
}

/**
 * Return an array of the default editor options to use for the standard moodle html editor
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @return  array   Containing a list of default properties
 */
function block_workflow_editor_options() {
    $options = array();

    // Disallow files.
    $options['maxfiles'] = 0;
    $options['autosave'] = false;

    return $options;
}

/**
 * Return a human-readable string to describe the editor format
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @param   int     The editor format
 * @return  string  The human-readable string format
 */
function block_workflow_editor_format($type) {
    switch ($type) {
        case FORMAT_HTML:
            return get_string('format_html', 'block_workflow');
        case FORMAT_PLAIN:
            return get_string('format_plain', 'block_workflow');
        default:
            return get_string('format_unknown', 'block_workflow');
    }
}

/**
 * Coverts human-readable string to editor format, used in importing
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @param   string     The human-readable string format
 * @return  int        The editor format
 */
function block_workflow_convert_editor_format($format) {
    $knownformats = array(
        get_string('format_html', 'block_workflow')  => FORMAT_HTML,
        get_string('format_plain', 'block_workflow') => FORMAT_PLAIN,
    );
    if (isset($knownformats[$format])) {
        return $knownformats[$format];
    } else {
        throw new block_workflow_exception(get_string('invalidformat', 'block_workflow', $format));
    }
}

/**
 * Check whether the current user can make changes to the specified state
 *
 * That is to say, that ths current user has either the workflow:dostep
 * permission, or is listed in the step roles for the specified state step
 *
 * @param   object  $state  The step_state object
 * @return  boolean         Whether or not the user has permission
 */
function block_workflow_can_make_changes($state) {
    global $USER;

    static $canmakechanges = array();

    $context = $state->context();

    if (isset($canmakechanges[$context->id][$state->id])) {
        return $canmakechanges[$context->id][$state->id];
    } else {
        $canmakechanges[$context->id][$state->id] = false;
    }

    if (has_capability('block/workflow:dostep', $context)) {
        $canmakechanges[$context->id][$state->id] = true;
        return $canmakechanges[$context->id][$state->id];
    }

    foreach ($state->step()->roles() as $role) {
        if (user_has_role_assignment($USER->id, $role->id, $context->id)) {
            $canmakechanges[$context->id][$state->id] = true;
            return $canmakechanges[$context->id][$state->id];
        }
    }
    return $canmakechanges[$context->id][$state->id];
}

/**
 * Return all active steps which are set for 'extranotify' or 'autofinish'
 *
 * @param array $stepoptions An array of params to check whther we are looking for 'extranotify' or 'autofinish'
 * @return array
 */
function block_workflow_get_active_steps_with_fields_not_null($stepoptions) {
    global $DB;
    list($offsettype, $offset, $textarea) = $stepoptions;
    $where = $textarea ? ' AND step.onextranotifyscript IS NOT NULL ' : '';

    $sql = "SELECT state.id AS stateid, state.stepid, state.state,
                        wf.id AS workflowid, wf.appliesto,
                        step.name AS stepname, step.$offsettype, step.$offset,
                        c.id AS courseid, c.shortname AS courseshortname,
                        cm.instance AS moduleid
                FROM {block_workflow_step_states} state
                LEFT JOIN {block_workflow_steps} step ON step.id = state.stepid
                LEFT JOIN {block_workflow_workflows} wf ON wf.id = step.workflowid
                LEFT JOIN {context} ctx ON ctx.id = state.contextid
                LEFT JOIN {course} c ON c.id = ctx.instanceid
                LEFT JOIN {course_modules} cm ON cm.id = ctx.instanceid AND wf.appliesto <> 'course'

                WHERE step.$offsettype IS NOT NULL $where
                    AND state.state = :state
                    AND (ctx.contextlevel = :coursecotext OR ctx.contextlevel = :modulecontext)
                    ORDER BY state.id ASC";

    $options = array('state' => BLOCK_WORKFLOW_STATE_ACTIVE,
            'coursecotext' => CONTEXT_COURSE,
            'modulecontext' => CONTEXT_MODULE);

    return $DB->get_records_sql($sql, $options);
}

/**
 * Return the timestamp for 'extranotify' or 'autofinish'
 * @param string $courseshortname
 * @param int $courseid
 * @param int $moduleid
 * @param string $offsettype
 * @param int $offset
 * @return int
 */
function block_workflow_get_offset_time($courseshortname, $courseid, $moduleid, $offsettype, $offset) {
    global $DB;
    list($dbtable, $dbfield) = explode(';', $offsettype);

    $timestamp = 0;
    if ($dbtable === 'vl_v_crs_version_pres') {
        $timestamp = $DB->get_field_sql("
                SELECT MIN($dbfield)
                  FROM vl_v_crs_version_pres
                 WHERE vle_course_short_name = ?
                ", array($courseshortname));
    } else if ($dbtable === 'course') {
        $timestamp = $DB->get_field('course', $dbfield, array('id' => $courseid));
    } else {
        $timestamp = $DB->get_field($dbtable, $dbfield, array('id' => $moduleid));
    }
    return $timestamp + $offset;
}

/**
 * Send email notifications.
 * @return void
 */
function block_workflow_send_extra_notification() {
    $options = array('extranotify', 'extranotifyoffset', 'onextranotifyscript');
    $activesteps = block_workflow_get_active_steps_with_fields_not_null($options);

    if (!$activesteps) {
        return;
    }
    $now = time();

    foreach ($activesteps as $key => $activestep) {
        try {
            $notificationtime = block_workflow_get_offset_time($activestep->courseshortname,
                    $activestep->courseid, $activestep->moduleid, $activestep->extranotify, $activestep->extranotifyoffset);

            // Is is the time to notify?
            if ($notificationtime < $now) {
                $state = new block_workflow_step_state($activestep->stateid);
                if ($state->step()->onextranotifyscript) {
                    $state->step()->process_script($state, $state->step()->onextranotifyscript);

                    // Trigger an event for the extra notification.
                    $event = \block_workflow\event\step_extra_notification_processed::create_from_step_state($state);
                    $event->trigger();
                }

                // Cron setup user.
                cron_setup_user();
            }
        } catch (Exception $e) {
            block_workflow_report_scheduled_task_error('send extra notifications', $e, $activestep);
        }
    }
}

/**
 * Finish the step automatically.
 * @return void
 */
function block_workflow_autofinish_steps() {
    $options = array('autofinish', 'autofinishoffset', null);
    $activesteps = block_workflow_get_active_steps_with_fields_not_null($options);

    if (!$activesteps) {
        return;
    }
    $now = time();

    foreach ($activesteps as $key => $activestep) {
        try {
            $autofinishtime = block_workflow_get_offset_time($activestep->courseshortname,
                    $activestep->courseid, $activestep->moduleid, $activestep->autofinish, $activestep->autofinishoffset);

            // Is is the time to finish the step automatically?
            if ($autofinishtime < $now) {
                // Add a comment and finish the step automatically.
                $newcomment = get_string('finishstepautomatically',  'block_workflow',
                                            date('H:i:s') . ' on ' . date('jS \of F Y'));

                $state = new block_workflow_step_state($activestep->stateid);
                $state->finish_step($newcomment, FORMAT_HTML);

                // Cron setup user.
                cron_setup_user();
            }
        } catch (Exception $e) {
            block_workflow_report_scheduled_task_error(
                    'automatic step finisher', $e, $activestep);
        }
    }
}

/**
 * Helper function used by both {@link block_workflow_autofinish_steps()}
 * and {@link block_workflow_send_extra_notification()}.
 *
 * @param string $taskname which task - used in the debug output.
 * @param Exception $e the exception.
 * @param $activestep if this relates to a particular step state.
 * Only the $activestep->stateid field is used (currently).
 */
function block_workflow_report_scheduled_task_error($taskname, Exception $e, $activestep = null) {

    $message = "Workflow task $taskname failed: " . $e->getMessage() . " at " . date('H:i:s');
    if ($activestep) {
        $message .= ' while processing step state ' . $activestep->stateid;
    }
    mtrace($message);
    if (!empty($e->debuginfo)) {
        mtrace("Debug info:");
        mtrace($e->debuginfo);
    }
    mtrace("Backtrace:");
    mtrace(format_backtrace($e->getTrace(), true));
}
