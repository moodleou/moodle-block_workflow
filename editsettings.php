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
 * Script to allow creation or updating of basic workflow settings
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once(dirname(__FILE__) . '/edit_workflow_form.php');
require_once($CFG->libdir . '/adminlib.php');

// Get the submitted paramaters.
$workflowid = optional_param('workflowid', 0, PARAM_INT);

// This is an admin page.
admin_externalpage_setup('blocksettingworkflow');

// Require login.
require_login();

// Require the workflow:editdefinitions capability.
require_capability('block/workflow:editdefinitions', get_context_instance(CONTEXT_SYSTEM));

// Grab a workflow object.
$workflow   = new block_workflow_workflow();

// Attempt to the set page/return url initially.
$returnurl  = new moodle_url('/blocks/workflow/editsteps.php', array('workflowid' => $workflowid));

if ($workflowid) {
    // If we've been given an existing workflow.
    $workflow->load_workflow($workflowid);
    $title = get_string('editworkflow', 'block_workflow', $workflow->name);
    $PAGE->set_url('/blocks/workflow/editsettings.php', array('workflowid' => $workflowid));
} else {
    // We're creating a new workflow.
    $title = get_string('createworkflow', 'block_workflow');
    $PAGE->set_url('/blocks/workflow/editsettings.php');
}

// Set the page header and title.
$PAGE->set_title($title);
$PAGE->set_heading($title);

// Add the breadcrumbs.
if ($workflowid) {
    $PAGE->navbar->add($workflow->name, $returnurl);
    $PAGE->navbar->add(get_string('edit', 'block_workflow'));
} else {
    $PAGE->navbar->add(get_string('create', 'block_workflow'));
}

// Moodle form to create/edit workflow.
$customdata = array('steps' => $workflow->steps(), 'is_deletable' => $workflow->is_deletable());
$editform = new edit_workflow(null, $customdata);

if ($editform->is_cancelled()) {
    // Form was cancelled.
    if ($workflowid) {
        redirect($returnurl);
    } else {
        // Cancelled on form creation, so redirect to manage.php instead.
        redirect(new moodle_url('/blocks/workflow/manage.php'));
    }
} else if ($data = $editform->get_data()) {
    // Form was submitted.
    $formdata = new stdClass();
    $formdata->id                 = $data->workflowid;
    $formdata->shortname          = $data->shortname;
    $formdata->name               = $data->name;
    $formdata->description        = $data->description_editor['text'];
    $formdata->descriptionformat  = $data->description_editor['format'];
    $formdata->obsolete           = $data->obsolete;

    // Only update the appliesto if we have access to it.
    if (isset($data->appliesto)) {
        $formdata->appliesto          = $data->appliesto;
    }

    // Determine what to do at the end of the final workflow step.
    if (isset($data->atendgobacktostep)) {
        $formdata->atendgobacktostep  = $data->atendgobacktostep;
    } else {
        $formdata->atendgobacktostep  = null;
    }

    if ($workflow->id) {
        $workflow->update($formdata);
    } else {
        $workflow->create_workflow($formdata);
    }

    // Redirect to the editsteps page.
    redirect(new moodle_url('/blocks/workflow/editsteps.php', array('workflowid' => $workflow->id)));
}

$data = new stdClass();

if ($workflow->id) {
    // We're editing an existing workflow, so set the default data for the form.
    $data->workflowid           = $workflow->id;
    $data->shortname            = $workflow->shortname;
    $data->name                 = $workflow->name;
    $data->description          = clean_text($workflow->description, $workflow->descriptionformat);
    $data->obsolete             = $workflow->obsolete;
    $data->appliesto            = $workflow->appliesto;
    $data->atendgobacktostep    = $workflow->atendgobacktostep;
    $data = file_prepare_standard_editor($data, 'description', array());
    $editform->set_data($data);
}

// Grab the renderer.
$renderer = $PAGE->get_renderer('block_workflow');

// Display the page and form.
echo $OUTPUT->header();
echo $renderer->edit_workflow_instructions($data);
$editform->display();
echo $OUTPUT->footer();
