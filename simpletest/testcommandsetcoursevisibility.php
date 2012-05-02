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
 * Workflow block tests.
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Include our test library so that we can use the same mocking system for
// all tests.
require_once(dirname(__FILE__) . '/lib.php');

class test_block_workflow_command_setcoursevisibility extends block_workflow_testlib {
    public function test_setcoursevisibility() {
        $command = new block_workflow_command_setcoursevisibility();
        $this->assertIsA($command, 'block_workflow_command');
    }

    public function test_parse_no_state_hidden() {
        $workflow = $this->create_workflow(false);
        $step     = $this->create_step($workflow);

        // This should change the visibility to hidden.
        $args = 'hidden';

        // Try parsing without a state.
        $class = block_workflow_command::create('block_workflow_command_setcoursevisibility');
        $result = $class->parse($args, $step);

        // Test: $result should have data.
        $this->assertNotNull($result);

        // There should be no errors.
        $this->assertEqual(count($result->errors), 0);

        // Test: $result should have a visible state of 0.
        $this->assertEqual($result->visible, 0);

        // Test: $result should have no id.
        $this->assertFalse(isset($result->id));
    }

    public function test_parse_no_state_visible() {
        $workflow = $this->create_workflow(false);
        $step     = $this->create_step($workflow);

        // This should change the visibility to visible.
        $args = 'visible';

        // Try parsing without a state.
        $class = block_workflow_command::create('block_workflow_command_setcoursevisibility');
        $result = $class->parse($args, $step);

        // Test: $result should have data.
        $this->assertNotNull($result);

        // There should be no errors.
        $this->assertEqual(count($result->errors), 0);

        // Test: $result should have a visible state of 0.
        $this->assertEqual($result->visible, 1);

        // Test: $result should have no id.
        $this->assertFalse(isset($result->id));
    }

    public function test_parse_no_state_invalid_state() {
        $workflow = $this->create_workflow(false);
        $step     = $this->create_step($workflow);

        // This should change the visibility to invalid.
        $args = 'invalid';

        // Try parsing without a state.
        $class = block_workflow_command::create('block_workflow_command_setcoursevisibility');
        $result = $class->parse($args, $step);

        // Test: $result should have data.
        $this->assertNotNull($result);

        // There should be one error.
        $this->assertEqual(count($result->errors), 1);

        // Test: $result should have no visible.
        $this->assertFalse(isset($result->visible));

        // Test: $result should have no id.
        $this->assertFalse(isset($result->id));
    }

    public function test_parse_no_state_appliestoactivity() {
        $workflow = $this->create_activity_workflow('quiz', false);
        $step     = $this->create_step($workflow);

        // This should change the visibility to hidden.
        $args = 'hidden';

        // Try parsing without a state.
        $class = block_workflow_command::create('block_workflow_command_setcoursevisibility');
        $result = $class->parse($args, $step);

        // Test: $result should have data.
        $this->assertNotNull($result);

        // There should be one error.
        $this->assertEqual(count($result->errors), 1);

        // Test: $result should have no id.
        $this->assertFalse(isset($result->id));
    }

    public function test_parse_with_state_hidden() {
        $workflow = $this->create_workflow(false);
        $step     = $this->create_step($workflow);
        $state    = $this->assign_workflow($workflow);

        // This should change the visibility to hidden.
        $args = 'hidden';

        // Try parsing with a state.
        $class = block_workflow_command::create('block_workflow_command_setcoursevisibility');
        $result = $class->parse($args, $step, $state);

        // Test: $result should have data.
        $this->assertNotNull($result);

        // There should be no errors.
        $this->assertEqual(count($result->errors), 0);

        // Test: $result should have a visible state of 0.
        $this->assertEqual($result->visible, 0);

        // Test: $result should have an id of the context's instanceid ($this->courseid).
        $this->assertEqual($result->id, $this->courseid);
    }

    public function test_parse_with_state_visible() {
        $workflow = $this->create_workflow(false);
        $step     = $this->create_step($workflow);
        $state    = $this->assign_workflow($workflow);

        // This should change the visibility to visible.
        $args = 'visible';

        // Try parsing with a state.
        $class = block_workflow_command::create('block_workflow_command_setcoursevisibility');
        $result = $class->parse($args, $step, $state);

        // Test: $result should have data.
        $this->assertNotNull($result);

        // There should be no errors.
        $this->assertEqual(count($result->errors), 0);

        // Test: $result should have a visible state of 0.
        $this->assertEqual($result->visible, 1);

        // Test: $result should have an id of the context's instanceid ($this->courseid).
        $this->assertEqual($result->id, $this->courseid);
    }

    public function test_parse_with_state_invalid_state() {
        $workflow = $this->create_workflow(false);
        $step     = $this->create_step($workflow);
        $state    = $this->assign_workflow($workflow);

        // This should change the visibility to invalid.
        $args = 'invalid';

        // Try parsing with a state.
        $class = block_workflow_command::create('block_workflow_command_setcoursevisibility');
        $result = $class->parse($args, $step, $state);

        // Test: $result should have data.
        $this->assertNotNull($result);

        // There should be one error.
        $this->assertEqual(count($result->errors), 1);

        // Test: $result should have no visible.
        $this->assertFalse(isset($result->visible));

        // Test: $result should have an id of the context's instanceid ($this->courseid).
        $this->assertEqual($result->id, $this->courseid);
    }

    public function test_parse_with_state_appliestoactivity() {
        $workflow = $this->create_activity_workflow('quiz', false);
        $step     = $this->create_step($workflow);
        $state    = $this->assign_workflow($workflow);

        // This should change the visibility to hidden.
        $args = 'hidden';

        // Try parsing with a state.
        $class = block_workflow_command::create('block_workflow_command_setcoursevisibility');
        $result = $class->parse($args, $step, $state);

        // Test: $result should have data.
        $this->assertNotNull($result);

        // There should be one error.
        $this->assertEqual(count($result->errors), 1);
    }

    public function test_execute_hidden() {
        $workflow = $this->create_workflow(false);
        $step     = $this->create_step($workflow);
        $state    = $this->assign_workflow($workflow);

        // This should change the visibility to hidden.
        $args = 'hidden';

        // Check that the course visibility is currently visible.
        $check = $this->testdb->get_record('course', array('id' => $this->courseid));
        $this->assertEqual($check->visible, 1);

        // Try parsing without a context.
        $class = block_workflow_command::create('block_workflow_command_setcoursevisibility');
        $class->execute($args, $state);

        // Check that the course visibility is now hidden.
        $check = $this->testdb->get_record('course', array('id' => $this->courseid));
        $this->assertEqual($check->visible, 0);
    }
}
