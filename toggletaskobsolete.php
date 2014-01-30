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
 * Toggles a task as obsolete
 *
 * @package   block_workflow
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');

// Get the submitted paramaters.
$taskid = required_param('taskid', PARAM_INT);

// Require login and a valid session key.
require_login();
require_sesskey();

// Require the workflow:editdefinitions capability.
require_capability('block/workflow:editdefinitions', context_system::instance());

// Toggle the todo item.
$task = block_workflow_todo::toggle_task($taskid);

// Redirect.
redirect(new moodle_url('/blocks/workflow/editstep.php', array('stepid' => $task->stepid)));
