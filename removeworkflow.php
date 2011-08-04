<?php

/**
 * Script to allow a workflow to be removed from a context
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once($CFG->libdir . '/adminlib.php');

// Get the submitted paramaters
$workflowid = required_param('workflowid', PARAM_INT);
$contextid  = required_param('contextid',  PARAM_INT);
$confirm    = optional_param('confirm', false, PARAM_BOOL);

// Determine the context and cm
list($context, $course, $cm) = get_context_info_array($contextid);

// Require login
require_login($course, false, $cm);

if ($cm) {
    $PAGE->set_cm($cm);
}
else {
    $PAGE->set_context($context);
}

// Require the workflow:manage capability
require_capability('block/workflow:manage', $context);

// Set various page options
$PAGE->set_pagelayout('standard');
$PAGE->set_course($course);
$PAGE->set_url('/blocks/workflow/removeworkflow.php', array('workflowid' => $workflowid, 'contextid' => $contextid));

// Grab the workflow
$workflow = new block_workflow_workflow($workflowid);
$tparams = array('workflowname' => $workflow->name, 'contexttitle' => print_context_name($context));

// Check that this workflow is assigned to this context
$stepstates = $workflow->step_states($contextid);
$statelist = array_filter($stepstates, create_function('$a', 'return isset($a->stateid);'));
if (count($statelist) == 0) {
    throw new block_workflow_not_assigned_exception(get_string('workflownotassignedtocontext', 'block_workflow', $tparams));
}

// Set the heading and page title
$title = get_string('removeworkflowfromcontext', 'block_workflow', $tparams);
$PAGE->set_heading($title);
$PAGE->set_title($title);

// Add the breadcrumbs
$PAGE->navbar->add(get_string('blocks'));
$PAGE->navbar->add(get_string('pluginname', 'block_workflow'));
$PAGE->navbar->add($workflow->name);
$PAGE->navbar->add(get_string('remove', 'block_workflow'));

// The confirmation strings
$confirmstr = get_string('removeworkflowcheck', 'block_workflow', $tparams);
$confirmurl = new moodle_url('/blocks/workflow/removeworkflow.php',
        array('workflowid' => $workflowid, 'contextid' => $contextid, 'confirm' => 1));
$returnurl  = new moodle_url('/blocks/workflow/overview.php',
        array('workflowid' => $workflowid, 'contextid' => $contextid));

if ($confirm) {
    // Confirm the session key to stop CSRF
    require_sesskey();

    // Remove the workflow from the context
    $workflow->remove_workflow($contextid);

    // Redirect
    redirect(get_context_url($context));
}

// Display the delete confirmation dialogue
echo $OUTPUT->header();
echo $OUTPUT->confirm($confirmstr, $confirmurl, $returnurl);
echo $OUTPUT->footer();
