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
 * Workflow block test unit for todo class.
 *
 * @package   block_workflow
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group block_workflow
 */

namespace block_workflow;

use block_workflow_todo;
use stdClass;

defined('MOODLE_INTERNAL') || die();

// Include our test library so that we can use the same mocking system for all tests.
global $CFG;
require_once(dirname(__FILE__) . '/lib.php');

class todos_test extends \block_workflow_testlib {
    public function test_todo_validation() {
        // Create a new workflow.
        $workflow = $this->create_workflow();

        // And add a step to that workflow.
        $step = $this->create_step($workflow);

        // Attempt to create a todo with bad data.
        $todo = new block_workflow_todo();
        $data = new stdClass();

        // Missing task should throw block_workflow_invalid_todo_exception.
        $this->expect_exception_without_halting('block_workflow_invalid_todo_exception',
                $todo, 'create_todo', $data);
        $data->task = 'TASK-ONE';

        // Missing stepid.
        $this->expect_exception_without_halting('block_workflow_invalid_todo_exception',
                $todo, 'create_todo', $data);

        // Invalid stepid.
        $data->stepid = -1;
        $this->expect_exception_without_halting('block_workflow_invalid_step_exception',
                $todo, 'create_todo', $data);
        $data->stepid = $step->id;

        // Invalid field.
        $data->badfield = 'baddata';
        $this->expect_exception_without_halting('block_workflow_invalid_todo_exception',
                $todo, 'create_todo', $data);
        unset($data->badfield);

        // Give an obsolete value of obsolete which we'll check in a minute.
        $data->obsolete = BLOCK_WORKFLOW_OBSOLETE;
    }

    public function test_todo_create() {
        // Create a new workflow.
        $workflow = $this->create_workflow();

        // And add a step to that workflow.
        $step = $this->create_step($workflow);

        $todo = new block_workflow_todo();
        $data = new stdClass();
        $data->stepid = $step->id;
        $data->task = 'TASK-ONE';

        // Successful creation.
        $todo->create_todo($data);

        // Check that what we created matches.
        $this->compare_todo($data, $todo);

        // Check that the obsolete value is set to enabled for creation.
        $this->assertEquals($todo->obsolete, BLOCK_WORKFLOW_ENABLED);

        // Update the todo.
        $data = new stdClass();

        // Attempt to update the stepid with something different.
        $data->stepid = -1;
        $this->expect_exception_without_halting('block_workflow_invalid_todo_exception',
                $todo, 'update_todo', $data);
        unset($data->stepid);

        // Invalid field.
        $data->badfield = 'baddata';
        $this->expect_exception_without_halting('block_workflow_invalid_todo_exception',
                $todo, 'update_todo', $data);
        unset($data->badfield);
    }

    public function test_todo_update() {
        // Create a new workflow.
        $workflow = $this->create_workflow();

        // And add a step to that workflow.
        $step = $this->create_step($workflow);

        $todo = new block_workflow_todo();
        $data = new stdClass();
        $data->stepid = $step->id;
        $data->task = 'TASK-ONE';

        // Successful creation.
        $todo->create_todo($data);

        // Check that what we created matches.
        $this->compare_todo($data, $todo);

        // Set an obsolete value too.
        $data->obsolete = BLOCK_WORKFLOW_OBSOLETE;

        // Update the task too.
        $data->task = 'UPDATED';

        // Update the todo.
        $todo->update_todo($data);

        // Compare the updated task.
        $this->compare_todo($data, $todo, array('id'));
    }

    public function test_todo_loading() {
        // Create a new workflow.
        $workflow = $this->create_workflow();

        // And add a step to that workflow.
        $step = $this->create_step($workflow);

        // Add a new todo to play with.
        $todo = $this->create_todo($step);

        // Test loading the step with it's various load functions.
        // Using the constructor.
        $reloaded = new block_workflow_todo($todo->id);
        $this->compare_todo($todo, $reloaded);

        // And using the load_by_id.
        $reloaded = new block_workflow_todo();
        $reloaded->load_by_id($todo->id);
        $this->compare_todo($todo, $reloaded);
    }

    public function test_todo_clone() {
        // Create a new workflow.
        $workflow = $this->create_workflow();

        // And add a step to that workflow.
        $step = $this->create_step($workflow);

        // Add a new todo to play with.
        $todo = $this->create_todo($step);

        // Clone the todo.
        $clone = $todo->clone_todo($todo->id);

        $this->compare_todo($todo, $clone, array('id'));
        $this->assertNotEquals($todo->id, $clone->id);

        // Add a second step and clone the task from the first step.
        $newstep = $this->create_step($workflow);
        $clone = $todo->clone_todo($todo->id, $newstep->id);
        $this->compare_todo($todo, $clone, array('id', 'stepid'));
        $this->assertNotEquals($todo->id,        $clone->id);
        $this->assertNotEquals($todo->stepid,    $clone->stepid);
        $this->assertEquals($clone->stepid,      $newstep->id);
    }

    public function test_todo_delete() {
        // Create a new workflow.
        $workflow = $this->create_workflow();

        // And add a step to that workflow.
        $step = $this->create_step($workflow);

        // Add a new todo to play with.
        $todo = $this->create_todo($step);

        // Try deleting the todo.
        $todo->delete_todo();

        // Confirm that we can't load it any more.
        $reload = new block_workflow_todo();
        $this->expect_exception_without_halting('block_workflow_invalid_todo_exception',
                $reload, 'load_by_id', $todo->id);
    }

    public function test_todo_toggle() {
        // Create a new workflow.
        $workflow = $this->create_workflow();

        // And add a step to that workflow.
        $step = $this->create_step($workflow);

        // Add a new todo to play with.
        $todo = $this->create_todo($step);

        // Check the toggle function.
        // At creation, todos are created enabled.
        $this->assertEquals($todo->obsolete, BLOCK_WORKFLOW_ENABLED);

        // Toggling should disable.
        $todo->toggle();
        $this->assertEquals($todo->obsolete, BLOCK_WORKFLOW_OBSOLETE);

        // And again should re-enable.
        $todo->toggle();
        $this->assertEquals($todo->obsolete, BLOCK_WORKFLOW_ENABLED);

        // It should be possible to call in a static context
        // toggle returns a $todo too, so we should check what's returned.
        // First to disable.
        $return = block_workflow_todo::toggle_task($todo->id);
        $check  = new block_workflow_todo($todo->id);
        $this->assertEquals($check->obsolete, BLOCK_WORKFLOW_OBSOLETE);
        $this->compare_todo($return, $check, array());

        // And also to re-enable.
        $return = block_workflow_todo::toggle_task($todo->id);
        $check  = new block_workflow_todo($todo->id);
        $this->assertEquals($check->obsolete, BLOCK_WORKFLOW_ENABLED);
        $this->compare_todo($return, $check, array());
    }

    public function test_todo_step() {
        // Create a new workflow.
        $workflow = $this->create_workflow();

        // And add a step to that workflow.
        $step = $this->create_step($workflow);

        // Add a new todo to play with.
        $todo = $this->create_todo($step);

        // Check that $todo->step() gives us the same step.
        $this->compare_step($step, $todo->step());
    }
}
