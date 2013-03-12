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
 * Toggle a task as done
 *
 * @package   block_workflow
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');

// Get the submitted paramaters.
$todoid     = required_param('todoid', PARAM_INT);
$stateid    = required_param('stateid', PARAM_INT);
$returnto   = optional_param('returnto', '', PARAM_LOCALURL);

// Require login and a valid session key.
require_login();
require_sesskey();

// Grab the state, and context.
$state      = new block_workflow_step_state($stateid);
$context    = $state->context();

// Require the workflow:dostep capability.
require_capability('block/workflow:dostep', $context);

// Toggle the todo item.
$state->todo_toggle($todoid);

if ($returnto) {
    redirect(new moodle_url($returnto));
} else {
    // Redirect to our best guess for the correct page.
    redirect(get_context_url($context));
}
