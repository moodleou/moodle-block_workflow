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
 * Clone a workflow
 *
 * @package   block_workflow
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once(dirname(__FILE__) . '/clone_form.php');
require_once($CFG->libdir . '/adminlib.php');

// Get the submitted paramaters.
$workflowid = required_param('workflowid', PARAM_INT);

// This is an admin page.
admin_externalpage_setup('blocksettingworkflow');

// Require login.
require_login();

// Require the workflow:editdefinitions capability.
require_capability('block/workflow:editdefinitions', context_system::instance());

// Grab a workflow object.
$workflow   = new block_workflow_workflow($workflowid);

// Set page and return urls.
$returnurl  = new moodle_url('/blocks/workflow/manage.php');
$PAGE->set_url('/blocks/workflow/clone.php', array('workflowid' => $workflowid));

// Page settings.
$title = get_string('cloneworkflowname', 'block_workflow', $workflow->name);
$PAGE->set_title($title);
$PAGE->set_heading($title);

// Add the breadcrumbs.
$PAGE->navbar->add(get_string('clone', 'block_workflow'));

// Grab the renderer.
$renderer = $PAGE->get_renderer('block_workflow');

// Moodle form to clone the workflow.
$cloneform = new clone_workflow();

if ($cloneform->is_cancelled()) {
    // Form was cancelled.
    redirect($returnurl);
} else if ($data = $cloneform->get_data()) {
    // Form was submitted.
    unset($data->submitbutton);
    unset($data->workflowid);

    // Clone the workflow using the data given.
    $workflow = block_workflow_workflow::clone_workflow($workflowid, $data);

    // Redirect to the newly created workflow.
    redirect(new moodle_url('/blocks/workflow/editsteps.php', array('workflowid' => $workflow->id)));
}

// Set the clone workflow form defaults.
$data = new stdClass();
$data->workflowid           = $workflow->id;
$data->shortname            = get_string('clonedshortname', 'block_workflow', $workflow->shortname);
$data->name                 = get_string('clonedname', 'block_workflow', $workflow->name);
$data->description          = $workflow->description;
$data->descriptionformat    = $workflow->descriptionformat;
$data->appliesto            = block_workflow_appliesto($workflow->appliesto);
$data = file_prepare_standard_editor($data, 'description', array('noclean' => true));

$cloneform->set_data($data);

// Display the page and form.
echo $OUTPUT->header();
echo $renderer->clone_workflow_instructions($workflow);
$cloneform->display();
echo $OUTPUT->footer();
