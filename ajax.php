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
 * Server-side script for all ajax request for workflow API
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');

// Require the session key.
require_sesskey();

$action  = required_param('action', PARAM_ACTION);
$stateid = required_param('stateid', PARAM_INT);

$state   = new block_workflow_step_state($stateid);
list($context, $course, $cm) = get_context_info_array($state->contextid);

// Require login.
require_login($course, false, $cm);

$PAGE->set_context($context);
$PAGE->set_url('/blocks/workflow/ajax.php', array('stateid' => $stateid, 'action' => $action));

// Send headers.
echo $OUTPUT->header();

$outcome = new stdClass;
$outcome->success = true;
$outcome->response = new stdClass;
$outcome->error = '';

if (!block_workflow_can_make_changes($state)) {
    // Check that the user is allowed to work on this step.
    throw new block_workflow_ajax_exception(get_string('notallowedtodothisstep', 'block_workflow'));
}

switch ($action) {
    case 'getcomment':
        $outcome->response->comment = $state->comment;
        break;
    case 'savecomment':
        $text = required_param('text', PARAM_CLEANHTML);
        $format = required_param('format', PARAM_INT);
        $state->update_comment($text, $format);
        $outcome->response->blockcomments = shorten_text($text, BLOCK_WORKFLOW_MAX_COMMENT_LENGTH);
        break;
    case 'finishstep':
        $text = required_param('text', PARAM_CLEANHTML);
        $format = required_param('format', PARAM_INT);
        $renderer = $PAGE->get_renderer('block_workflow');

        // Retrieve the next step.
        $newstate = $state->finish_step($text, $format);
        $canview = ($newstate) ? has_capability('block/workflow:view', $newstate->context()) : false;

        if ($newstate && ($canview || block_workflow_can_make_changes($newstate))) {
            // There is a next possible state, and the current user may view and/or work on it.
            $outcome->response->blockcontent = $renderer->block_display($newstate);
            $outcome->response->stateid = $newstate->id;
        } else if (has_capability('block/workflow:manage', $state->context())) {
            // Last step has been reached, if permitted retrieve the list of workflows.
            $workflows = new block_workflow_workflow();
            $appliesto = $state->step()->workflow()->appliesto;
            $options = $workflows->available_workflows($appliesto);
            // Retrieve previous uses.
            $previous = $workflows->load_context_workflows($state->contextid);
            // Display.
            $outcome->response->blockcontent = $renderer->assign_workflow($state->contextid, $options, $previous);
            $outcome->response->listworkflows = true;
        } else if ($newstate) {
            // There is a new step, but this user can't view it, and can't work on it ...
            $outcome->response->blockcontent = $renderer->block_display_step_complete_confirmation();
        } else {
            // ... or display message that there are no steps left.
            $outcome->response->blockcontent = $renderer->block_display_no_more_steps();
        }
        break;
    case 'toggletaskdone':
        // Toggle the todo item (mark as done).
        $todoid = required_param('todoid', PARAM_INT);
        $outcome->response->iscompleted = $state->todo_toggle($todoid);
        break;
    default:
        throw new block_workflow_ajax_exception('unknowajaxaction');
}

echo json_encode($outcome);
