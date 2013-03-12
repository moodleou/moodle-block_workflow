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
 * Update a workflow comment
 *
 * @package   block_workflow
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once(dirname(__FILE__) . '/editcomment_form.php');

// Get the submitted paramaters.
$stateid    = required_param('stateid', PARAM_INT);
$state      = new block_workflow_step_state($stateid);

// Determine the context and cm.
list($context, $course, $cm) = get_context_info_array($state->contextid);

// Require login.
require_login($course, false, $cm);

if ($cm) {
    $PAGE->set_cm($cm);
} else {
    $PAGE->set_context($context);
}

// Check permissions using can_make_changes -- this checks whether the user either:
// * has workflow:dostep
// * is in the step_doers list.
block_workflow_can_make_changes($state);

// Set the page URL.
$PAGE->set_url('/blocks/workflow/editcomment.php', array('stateid' => $stateid));
$PAGE->set_pagelayout('standard');
$PAGE->set_course($course);

// Set the heading and page title.
$tparams = array('stepname' => $state->step()->name, 'contextname' => print_context_name($context));
$title = get_string('editingcommentfor', 'block_workflow', $tparams);
$PAGE->set_heading($title);
$PAGE->set_title($title);

// Add the breadcrumbs.
$PAGE->navbar->add(get_string('blocks'));
$PAGE->navbar->add(get_string('pluginname', 'block_workflow'));
$PAGE->navbar->add($state->step()->name);

// Moodle form to update the state comment.
$mform = new state_editcomment(null, array('state' => $state));

// Grab a returnurl which relates to the context.
$returnurl = get_context_url($context);

if ($mform->is_cancelled()) {
    // Form was cancelled.
    redirect($returnurl);
} else if ($data = $mform->get_data()) {
    // Update the comment and redirect.
    $state->update_comment($data->comment_editor['text'], $data->comment_editor['format']);
    redirect($returnurl);
}

// Retrieve the current state data for the form.
$data = new stdClass();
$data->comment      = $state->comment;
$data->commentformat = $state->commentformat;
$data->stateid      = $state->id;
$data->workflowname = $state->step()->workflow()->name;
$data->stepname     = $state->step()->name;
$data->instructions = $state->step()->instructions;
$data = file_prepare_standard_editor($data, 'comment', array());

$mform->set_data($data);

// Display the page.
echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
