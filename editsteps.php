<?php

/**
 * Script to display infromation about workflow and controls to edit it
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

// This is an admin page
admin_externalpage_setup('blocksettingworkflow');

// Require login
require_login();

// Require the workflow:editdefinitions capability
require_capability('block/workflow:editdefinitions', get_context_instance(CONTEXT_SYSTEM));

// Grab the workflow
$workflow   = new block_workflow_workflow($workflowid);

// Set the returnurl as we'll use this in a few places
$returnurl  = new moodle_url('/blocks/workflow/editsteps.php', array('workflowid' => $workflowid));

// Set various page settings
$title = get_string('editworkflow', 'block_workflow', $workflow->name);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_url($returnurl);

// Add the breadcrumbs
$PAGE->navbar->add($workflow->name, $returnurl);

// Grab the renderer
$renderer   = $PAGE->get_renderer('block_workflow');

// Display the page header
echo $OUTPUT->header();

// List the current workflow settings
echo $renderer->display_workflow($workflow);

// List the current workflow steps
echo $renderer->list_steps($workflow);

// Display the page footer
echo $OUTPUT->footer();
