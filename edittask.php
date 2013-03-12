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
 * Script to add/update an existing task
 *
 * @package   block_workflow
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once(dirname(__FILE__) . '/edittask_form.php');
require_once($CFG->libdir . '/adminlib.php');

$taskid = optional_param('id', 0, PARAM_INT);
$task = new block_workflow_todo();

// This is an admin page.
admin_externalpage_setup('blocksettingworkflow');

// Require login.
require_login();

// Require the workflow:editdefinitions capability.
require_capability('block/workflow:editdefinitions', get_context_instance(CONTEXT_SYSTEM));
if ($taskid) {
    // An existing task was specified.
    $task->load_by_id($taskid);
    $returnurl  = new moodle_url('/blocks/workflow/editstep.php', array('stepid' => $task->stepid));
    $PAGE->set_title(get_string('edittask', 'block_workflow', $task->task));
    $PAGE->set_url('/blocks/workflow/edittask.php', array('taskid' => $taskid));
    $data = (object) $task;
} else {
    // Creating a new task. We require the stepid.
    $stepid         = required_param('stepid', PARAM_INT);
    $step           = new block_workflow_step($stepid);
    $returnurl      = new moodle_url('/blocks/workflow/editstep.php', array('stepid' => $step->id));
    $PAGE->set_title(get_string('createtask', 'block_workflow', $step->name));
    $PAGE->set_url('/blocks/workflow/edittask.php');
    $data = new stdClass();
    $data->stepid   = $step->id;
}

// Create/edit task form.
$editform = new task_edit();

$editform->set_data($data);

if ($editform->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $editform->get_data()) {
    $formdata = new stdClass();
    $formdata->id   = $data->id;
    $formdata->task = $data->task;

    if ($taskid) {
        $task->update_todo($formdata);
    } else {
        $formdata->stepid = $data->stepid;
        $task->create_todo($formdata);
    }

    redirect($returnurl);
}

// Display the page.
echo $OUTPUT->header();
$editform->display();
echo $OUTPUT->footer();
