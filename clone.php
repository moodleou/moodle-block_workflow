<?php

/**
 * Clone a workflow
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/clone_form.php');
require_once($CFG->libdir . '/adminlib.php');

// Get the submitted paramaters
$workflowid = required_param('workflowid', PARAM_INT);

// This is an admin page
admin_externalpage_setup('blocksettingworkflow');

// Require login
require_login();

// Require the workflow:editdefinitions capability
require_capability('block/workflow:editdefinitions', get_context_instance(CONTEXT_SYSTEM));

// Grab a workflow object
$workflow   = new block_workflow_workflow($workflowid);

// Set page and return urls
$returnurl  = new moodle_url('/blocks/workflow/manage.php');
$PAGE->set_url('/blocks/workflow/clone.php', array('workflowid' => $workflowid));

// Page settings
$title = get_string('cloneworkflowname', 'block_workflow', $workflow->name);
$PAGE->set_title($title);
$PAGE->set_heading($title);

// Add the breadcrumbs
$PAGE->navbar->add(get_string('clone', 'block_workflow'));

// Grab the renderer
$renderer = $PAGE->get_renderer('block_workflow');

// Moodle form to clone the workflow
$cloneform = new clone_workflow();

if ($cloneform->is_cancelled()) {
    // Form was cancelled
    redirect($returnurl);
}
else if ($data = $cloneform->get_data()) {
    // Form was submitted
    unset($data->submitbutton);
    unset($data->workflowid);

    // Clone the workflow using the data given
    $workflow = block_workflow_workflow::clone_workflow($workflowid, $data);

    // Redirect to the newly created workflow
    redirect(new moodle_url('/blocks/workflow/editsteps.php', array('workflowid' => $workflow->id)));
}

// Set the clone workflow form defaults
$data = new stdClass();
$data->workflowid           = $workflow->id;
$data->shortname            = $workflow->shortname;
$data->name                 = $workflow->name;
$data->description          = $workflow->description;
$data->descriptionformat    = $workflow->descriptionformat;
$data->appliesto            = block_workflow_appliesto($workflow->appliesto);
$data = file_prepare_standard_editor($data, 'description', array());

$cloneform->set_data($data);

// Display the page and form
echo $OUTPUT->header();
echo $renderer->clone_workflow_instructions($workflow);
$cloneform->display();
echo $OUTPUT->footer();
