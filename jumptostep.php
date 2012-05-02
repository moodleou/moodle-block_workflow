<?php

/**
 * Workflow block
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');

// Get the submitted paramaters
$contextid  = required_param('contextid', PARAM_INT);
$stepid     = required_param('stepid', PARAM_INT);
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
require_capability('block/workflow:manage', $PAGE->context);

// Grab the current state and the intended state
$state = new block_workflow_step_state();

if (!$state->load_active_state($contextid)) {
    // Jumping back from after the end of the workflow, so there is no current
    // step. Just record the contextid.
    $state->contextid = $contextid;
}
$step = new block_workflow_step($stepid);

// Set the page URL
$PAGE->set_url('/blocks/workflow/jumptostep.php', array('contextid' => $contextid, 'stepid' => $stepid));
$PAGE->set_pagelayout('standard');
$PAGE->set_course($course);

// Set the heading and page title
$tparams = array('stepname' => $step->name, 'contextname' => print_context_name($context));
$title = get_string('jumptostepon', 'block_workflow', $tparams);
$PAGE->set_heading($title);
$PAGE->set_title($title);

// Determine the URL -- we should redirect to the relevant context page
$returnurl = get_context_url($context);

// Add the breadcrumbs
$PAGE->navbar->add(get_string('blocks'));
$PAGE->navbar->add(get_string('pluginname', 'block_workflow'));
$PAGE->navbar->add(get_string('jumpstep', 'block_workflow'));

// If confirmatation has already been received, then process
if ($confirm) {
    // Confirm the session key to stop CSRF
    require_sesskey();

    // Jump to the specified step
    $state->jump_to_step(null, $stepid);

    // Redirect
    redirect($returnurl);
}

// Generate the confirmation message
$strparams = array();
$strparams['fromstep']   = $state->step()->name;
$strparams['tostep']     = $step->name;
$strparams['workflowon'] = print_context_name($context);

$PAGE->set_title(get_string('jumptosteptitle', 'block_workflow', $strparams));

$confirmstr = get_string('jumptostepcheck', 'block_workflow', $strparams);

// Generate the confirmation button
$confirmurl = new moodle_url('/blocks/workflow/jumptostep.php',
        array('contextid' => $contextid, 'stepid' => $stepid, 'confirm' => 1));
$confirmbutton  = new single_button($confirmurl, get_string('confirm'), 'post');

echo $OUTPUT->header();
echo $OUTPUT->confirm($confirmstr, $confirmbutton, $returnurl);
echo $OUTPUT->footer();
