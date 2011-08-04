<?php

/**
 * Script for deleting steps
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
$stepid     = required_param('stepid', PARAM_INT);
$confirm    = optional_param('confirm', false, PARAM_BOOL);

// This is an admin page
admin_externalpage_setup('blocksettingworkflow');

// Require login
require_login();

// Require the workflow:editdefinitions capability
require_capability('block/workflow:editdefinitions', get_context_instance(CONTEXT_SYSTEM));

// Load the step and check that we are allowed to delete it
$step   = new block_workflow_step($stepid);
$step->require_deletable();

// The confirmation strings
$confirmstr = get_string('deletestepcheck', 'block_workflow', $step->name);
$confirmurl = new moodle_url('/blocks/workflow/deletestep.php', array('stepid' => $stepid, 'confirm' => 1));
$returnurl  = new moodle_url('/blocks/workflow/editsteps.php', array('workflowid' => $step->workflowid));

// Set page url
$PAGE->set_url('/blocks/workflow/deletestep.php', array('stepid' => $stepid));

// Set the heading and page title
$title = get_string('confirmstepdeletetitle', 'block_workflow', $step->name);
$PAGE->set_title($title);
$PAGE->set_heading($title);

// Add the breadcrumbs
$PAGE->navbar->add($step->workflow()->name, $returnurl);
$PAGE->navbar->add(get_string('deletestep', 'block_workflow', $step->name));

if ($confirm) {
    // Confirm the session key to stop CSRF
    require_sesskey();

    // Delete the step
    $step->delete();

    // Redirect
    redirect($returnurl);
}

// Display the delete confirmation dialogue
echo $OUTPUT->header();
echo $OUTPUT->confirm($confirmstr, $confirmurl, $returnurl);
echo $OUTPUT->footer();
