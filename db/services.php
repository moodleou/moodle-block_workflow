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
 * Workflow block external functions and service definitions.
 *
 * @package    block_workflow
 * @copyright  2023 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$functions = [
    'block_workflow_get_step_state_comment' => [
        'classname' => 'block_workflow\\external\\get_step_state_comment',
        'methodname' => 'execute',
        'description' => 'Get comment from the current state',
        'type' => 'read',
        'ajax' => true,
    ],
    'block_workflow_update_step_state_comment' => [
        'classname' => 'block_workflow\\external\\update_step_state_comment',
        'methodname' => 'execute',
        'description' => 'Create/update comment from the current state',
        'type' => 'write',
        'ajax' => true,
    ],
    'block_workflow_finish_step' => [
        'classname' => 'block_workflow\\external\\finish_step',
        'methodname' => 'execute',
        'description' => 'Mark a step as finished',
        'type' => 'write',
        'ajax' => true,
    ],
    'block_workflow_update_step_state_task_state' => [
        'classname' => 'block_workflow\\external\\update_step_state_task_state',
        'methodname' => 'execute',
        'description' => 'Update the completed status of a task for a step state',
        'type' => 'write',
        'ajax' => true,
    ],
];
