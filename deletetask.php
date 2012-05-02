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
 * Script for deleting tasks
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
$id         = required_param('id', PARAM_INT);
$confirm    = optional_param('confirm', false, PARAM_BOOL);

// This is an admin page.
admin_externalpage_setup('blocksettingworkflow');

// Require login.
require_login();

// Require the workflow:editdefinitions capability.
require_capability('block/workflow:editdefinitions', get_context_instance(CONTEXT_SYSTEM));

// Load the todo.
$todo      = new block_workflow_todo($id);
$returnurl = new moodle_url('/blocks/workflow/editstep.php', array('stepid' => $todo->stepid));
$workflow  = $todo->step()->workflow();

// Generate the confirmation message.
$strparams = array('stepname' => $todo->step()->name, 'taskname' => $todo->task);

// Set the heading and page title.
$title = get_string('deletetasktitle', 'block_workflow', $strparams);
$PAGE->set_title($title);
$PAGE->set_heading($title);

// Add the breadcrumbs.
$PAGE->navbar->add($workflow->name, $returnurl);
$PAGE->navbar->add(get_string('deletetask', 'block_workflow'));

$confirmstr = get_string('deletetaskcheck', 'block_workflow', $strparams);

// Generate the confirmation button.
$confirmurl = new moodle_url('/blocks/workflow/deletetask.php',
        array('id' => $todo->id, 'confirm' => 1));
$confirmbutton  = new single_button($confirmurl, get_string('confirm'), 'post');

// Set page url.
$PAGE->set_url('/blocks/workflow/deletetask.php', array('id' => $id));

// If confirmatation has already been received, then process.
if ($confirm) {
    // Confirm the session key to stop CSRF.
    require_sesskey();

    // Toggle the role.
    $todo->delete_todo();

    // Redirect.
    redirect($returnurl);
}

// Display the delete confirmation dialogue.
echo $OUTPUT->header();
echo $OUTPUT->confirm($confirmstr, $confirmbutton, $returnurl);
echo $OUTPUT->footer();
