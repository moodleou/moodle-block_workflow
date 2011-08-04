<?php

/**
 * Script for deleting workflows
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
$confirm    = optional_param('confirm', false, PARAM_BOOL);

// This is an admin page
admin_externalpage_setup('blocksettingworkflow');

// Require login
require_login();

// Require the workflow:editdefinitions capability
require_capability('block/workflow:editdefinitions', get_context_instance(CONTEXT_SYSTEM));

// Load the workflow and check that we are allowed to delete it
$workflow   = new block_workflow_workflow($workflowid);
$workflow->require_deletable();

// The confirmation strings
$confirmstr = get_string('deleteworkflowcheck', 'block_workflow', $workflow->name);
$confirmurl = new moodle_url('/blocks/workflow/delete.php', array('workflowid' => $workflowid, 'confirm' => 1));
$returnurl  = new moodle_url('/blocks/workflow/manage.php');

// Set page url
$PAGE->set_url('/blocks/workflow/delete.php', array('workflowid' => $workflowid));

// Set the heading and page title
$title = get_string('confirmworkflowdeletetitle', 'block_workflow', $workflow->shortname);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add(get_string('deleteworkflow', 'block_workflow'));

if ($confirm) {
    // Confirm the session key to stop CSRF
    require_sesskey();

    // Delete the step
    $workflow->delete();

    // Redirect
    redirect($returnurl);
}

// Display the delete confirmation dialogue
echo $OUTPUT->header();
echo $OUTPUT->confirm($confirmstr, $confirmurl, $returnurl);
echo $OUTPUT->footer();
