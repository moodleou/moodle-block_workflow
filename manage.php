<?php

/**
 * Main workflow configuration page
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once($CFG->libdir . '/adminlib.php');

// This is an admin page
admin_externalpage_setup('blocksettingworkflow');

// Require login
require_login();

// Require the workflow:editdefinitions capability
require_capability('block/workflow:editdefinitions', get_context_instance(CONTEXT_SYSTEM));

// Page settings
$title = get_string('manageworkflows', 'block_workflow');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));

// Grab the renderer
$renderer       = $PAGE->get_renderer('block_workflow');

// Display the manage workflows interface
$workflows      = block_workflow_load_workflows();
$emails         = block_workflow_email::load_emails();
$tableworkflows = $renderer->manage_workflows($workflows, $emails);

// Display the page
echo $OUTPUT->header();
echo $tableworkflows;
echo $OUTPUT->footer();
