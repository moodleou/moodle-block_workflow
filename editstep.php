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
 * Script to create or update an existing step
 *
 * @package   block_workflow
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once(dirname(__FILE__) . '/editstep_form.php');
require_once($CFG->libdir . '/adminlib.php');

// Get the submitted paramaters.
$stepid = optional_param('stepid', 0, PARAM_INT);

// This is an admin page.
admin_externalpage_setup('blocksettingworkflow');

// Require login.
require_login();

// Require the workflow:editdefinitions capability.
require_capability('block/workflow:editdefinitions', context_system::instance());

if ($stepid) {
    // If we've been given an existing workflow.
    $step       = new block_workflow_step($stepid);
    $returnurl  = new moodle_url('/blocks/workflow/editsteps.php', array('workflowid' => $step->workflowid));
    $todos      = $step->todos(false);
    $doers      = $step->roles();
    $roles      = block_workflow_contextlevel_roles($step->workflow()->context());
    $title      = get_string('editstepname', 'block_workflow', $step->name);
    $PAGE->set_url('/blocks/workflow/editstep.php', array('stepid' => $stepid));

    // Add the breadcrumbs.
    $PAGE->navbar->add($step->workflow()->name, $returnurl);
    $PAGE->navbar->add(get_string('editstep', 'block_workflow'));

    $appliesto = $DB->get_field('block_workflow_workflows', 'appliesto', array('id' => $step->workflowid));
} else {
    // We're creating a new step.
    $workflowid  = required_param('workflowid', PARAM_INT);
    $workflow    = new block_workflow_workflow($workflowid);
    $returnurl   = new moodle_url('/blocks/workflow/editsteps.php', array('workflowid' => $workflowid));
    $title       = get_string('createstepname', 'block_workflow', $workflow->name);
    $beforeafter = optional_param('beforeafter', 0, PARAM_INT);
    $PAGE->set_url('/blocks/workflow/editstep.php', array('workflowid' => $workflowid));

    // Add the breadcrumbs.
    $PAGE->navbar->add($workflow->name, $returnurl);
    $PAGE->navbar->add(get_string('createstep', 'block_workflow'));

    $appliesto = $workflow->appliesto;
}

// Set various page settings.
$PAGE->set_title($title);
$PAGE->set_heading($title);

// Moodle form to create/edit step.
$stepedit = new step_edit('editstep.php', array('appliesto' => $appliesto));

if ($stepedit->is_cancelled()) {
    // Form was cancelled.
    redirect($returnurl);
} else if ($data = $stepedit->get_data()) {
    // Form has been submitted.
    $formdata = new stdClass();
    $formdata->id                   = $data->stepid;
    $formdata->name                 = $data->name;
    $formdata->instructions         = $data->instructions_editor['text'];
    $formdata->instructionsformat   = $data->instructions_editor['format'];
    $formdata->onactivescript       = $data->onactivescript;
    $formdata->oncompletescript     = $data->oncompletescript;
    $formdata->autofinish           = isset($data->autofinish) ? $data->autofinish : '';
    $formdata->autofinishoffset     = $data->autofinishoffset;
    $formdata->extranotify          = isset($data->extranotify) ? $data->extranotify : '';
    $formdata->extranotifyoffset    = $data->extranotifyoffset;
    $formdata->onextranotifyscript  = $data->onextranotifyscript;

    if (isset($step)) {
        // We're editing an existing step.
        $step->update_step($formdata);
    } else {
        // Creating a new step.
        $step = new block_workflow_step();
        $formdata->workflowid = $data->workflowid;
        $step->create_step($formdata, $data->beforeafter);
    }

    // Redirect to the editsteps.php page.
    redirect($returnurl);
}

$data = new stdClass();

if (isset($beforeafter)) {
    // If we're creating the step in a specific location.
    $data->beforeafter = $beforeafter;
}

if (isset($step)) {
    // Retrieve the current step data for the form.
    $data->stepid               = $step->id;
    $data->name                 = $step->name;
    $data->instructions         = $step->instructions;
    $data->instructionsformat   = $step->instructionsformat;
    $data->onactivescript       = $step->onactivescript;
    $data->oncompletescript     = $step->oncompletescript;
    $data->autofinish           = $step->autofinish;
    $data->autofinishoffset     = $step->autofinishoffset;
    $data->extranotify          = $step->extranotify;
    $data->extranotifyoffset    = $step->extranotifyoffset;
    $data->onextranotifyscript  = $step->onextranotifyscript;
    $data = file_prepare_standard_editor($data, 'instructions', array('noclean' => true));
    $stepedit->set_data($data);
} else {
    // Otherwise, this is a new step belonging to $workflowid.
    $data->workflowid   = $workflow->id;
    $stepedit->set_data($data);
}

// Grab the renderer.
$renderer = $PAGE->get_renderer('block_workflow');

// Display the page.
echo $OUTPUT->header();

if (isset($step)) {
    echo $renderer->edit_step_instructions($step);
} else {
    echo $renderer->create_step_instructions($workflow);
}

// The edit step form.
$stepedit->display();

if (isset($step)) {
    // The list of to-do actions.
    echo $renderer->step_todolist($todos, $step);

    // The list of actors (doers).
    echo $renderer->step_doers($roles, $doers, $stepid);
}

echo $OUTPUT->footer();
