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
 * Script for deleting workflows
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once($CFG->libdir . '/adminlib.php');

// Get the submitted paramaters.
$workflowid = required_param('workflowid', PARAM_INT);
$confirm    = optional_param('confirm', false, PARAM_BOOL);

// This is an admin page.
admin_externalpage_setup('blocksettingworkflow');

// Require login.
require_login();

// Require the workflow:editdefinitions capability.
require_capability('block/workflow:editdefinitions', get_context_instance(CONTEXT_SYSTEM));

// Load the workflow and check that we are allowed to delete it.
$workflow   = new block_workflow_workflow($workflowid);
$workflow->require_deletable();

// The confirmation strings.
$confirmstr = get_string('deleteworkflowcheck', 'block_workflow', $workflow->name);
$confirmurl = new moodle_url('/blocks/workflow/delete.php', array('workflowid' => $workflowid, 'confirm' => 1));
$returnurl  = new moodle_url('/blocks/workflow/manage.php');

// Set page url.
$PAGE->set_url('/blocks/workflow/delete.php', array('workflowid' => $workflowid));

// Set the heading and page title.
$title = get_string('confirmworkflowdeletetitle', 'block_workflow', $workflow->shortname);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add(get_string('deleteworkflow', 'block_workflow'));

if ($confirm) {
    // Confirm the session key to stop CSRF.
    require_sesskey();

    // Delete the step.
    $workflow->delete();

    // Redirect.
    redirect($returnurl);
}

// Display the delete confirmation dialogue.
echo $OUTPUT->header();
echo $OUTPUT->confirm($confirmstr, $confirmurl, $returnurl);
echo $OUTPUT->footer();
