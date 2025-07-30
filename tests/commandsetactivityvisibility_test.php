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
 * Workflow block tests
 *
 * @package   block_workflow
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group block_workflow
 */

namespace block_workflow;

use block_workflow_command_setactivityvisibility;
use block_workflow_command;

defined('MOODLE_INTERNAL') || die();

// Include our test library so that we can use the same mocking system for all tests.
global $CFG;
require_once(dirname(__FILE__) . '/lib.php');

/**
 * Unit tests for the commandsetactivityvisibility functionality in the block_workflow plugin.
 *
 * This class extends the block_workflow_testlib to provide test cases for verifying
 * the correct behavior of activity visibility commands within the workflow block.
 */
final class commandsetactivityvisibility_test extends \block_workflow_testlib {
    /**
     * Tests setactivityvisibility.
     *
     * @covers \block_workflow_command_setactivityvisibility
     */
    public function test_setactivityvisibility(): void {
        $command = new block_workflow_command_setactivityvisibility();
        $this->assertInstanceOf('block_workflow_command', $command);
    }

    /**
     * Tests the parse methods no state hidden.
     *
     * @covers \block_workflow_command_setactivityvisibility::parse
     */
    public function test_parse_no_state_hidden(): void {
        $workflow = $this->create_activity_workflow('quiz', false);
        $step     = $this->create_step($workflow);

        // This should change the visibility to hidden.
        $args = 'hidden';

        // Try parsing without a context.
        $class = block_workflow_command::create('block_workflow_command_setactivityvisibility');
        $result = $class->parse($args, $step);

        // Test: $result should have data.
        $this->assertNotNull($result);

        // There should be no errors.
        $this->assertEquals(count($result->errors), 0);

        // Test: $result should have a visibility state of 0.
        $this->assertEquals($result->visibility, 0);

        // Test: $result should have no id.
        $this->assertFalse(isset($result->id));
    }

    /**
     * Tests the parse methods no state visible.
     *
     * @covers \block_workflow_command_setactivityvisibility::parse
     */
    public function test_parse_no_state_visible(): void {
        $workflow = $this->create_activity_workflow('quiz', false);
        $step     = $this->create_step($workflow);

        // This should change the visibility to visible.
        $args = 'visible';

        // Try parsing without a context.
        $class = block_workflow_command::create('block_workflow_command_setactivityvisibility');
        $result = $class->parse($args, $step);

        // Test: $result should have data.
        $this->assertNotNull($result);

        // There should be no errors.
        $this->assertEquals(count($result->errors), 0);

        // Test: $result should have a visibility state of 0.
        $this->assertEquals($result->visibility, 1);

        // Test: $result should have no id.
        $this->assertFalse(isset($result->id));
    }

    /**
     * Tests the parse methods no state invalid state.
     *
     * @covers \block_workflow_command_setactivityvisibility::parse
     */
    public function test_parse_no_state_invalid_state(): void {
        $workflow = $this->create_activity_workflow('quiz', false);
        $step     = $this->create_step($workflow);

        // This should change the visibility to invalid.
        $args = 'invalid';

        // Try parsing without a context.
        $class = block_workflow_command::create('block_workflow_command_setactivityvisibility');
        $result = $class->parse($args, $step);

        // Test: $result should have data.
        $this->assertNotNull($result);

        // There should be one error.
        $this->assertEquals(count($result->errors), 1);

        // Test: $result should have no visible.
        $this->assertFalse(isset($result->visibility));

        // Test: $result should have no id.
        $this->assertFalse(isset($result->id));
    }

    /**
     * Tests the parse methods no state appliestocourse.
     *
     * @covers \block_workflow_command_setactivityvisibility::parse
     */
    public function test_parse_no_state_appliestocourse(): void {
        $workflow = $this->create_workflow(false);
        $step     = $this->create_step($workflow);

        // This should change the visibility to hidden.
        $args = 'hidden';

        // Try parsing without a context.
        $class = block_workflow_command::create('block_workflow_command_setactivityvisibility');
        $result = $class->parse($args, $step);

        // Test: $result should have data.
        $this->assertNotNull($result);

        // There should be one error.
        $this->assertEquals(count($result->errors), 1);

        // Test: $result should have no id.
        $this->assertFalse(isset($result->id));
    }

    /**
     * Tests the parse methods with state hidden.
     *
     * @covers \block_workflow_command_setactivityvisibility::parse
     */
    public function test_parse_with_state_hidden(): void {
        $workflow = $this->create_activity_workflow('quiz', false);
        $step     = $this->create_step($workflow);
        $state    = $this->assign_workflow($workflow);

        // This should change the visibility to hidden.
        $args = 'hidden';

        // Try parsing with a state.
        $class = block_workflow_command::create('block_workflow_command_setactivityvisibility');
        $result = $class->parse($args, $step, $state);

        // Test: $result should have data.
        $this->assertNotNull($result);

        // There should be no errors.
        $this->assertEquals(count($result->errors), 0);

        // Test: $result should have a visibility state of 0.
        $this->assertEquals($result->visibility, 0);

        // Test: $result should have a context, step, workflow and cm.
        $this->assertNotNull($result->context);
        $this->assertNotNull($result->step);
        $this->assertNotNull($result->workflow);
        $this->assertNotNull($result->cm);
    }

    /**
     * Tests the parse methods with state visible.
     *
     * @covers \block_workflow_command_setactivityvisibility::parse
     */
    public function test_parse_with_state_visible(): void {
        $workflow = $this->create_activity_workflow('quiz', false);
        $step     = $this->create_step($workflow);
        $state    = $this->assign_workflow($workflow);

        // This should change the visibility to visible.
        $args = 'visible';

        // Try parsing with a state.
        $class = block_workflow_command::create('block_workflow_command_setactivityvisibility');
        $result = $class->parse($args, $step, $state);

        // Test: $result should have data.
        $this->assertNotNull($result);

        // There should be no errors.
        $this->assertEquals(count($result->errors), 0);

        // Test: $result should have a visibility state of 0.
        $this->assertEquals($result->visibility, 1);

        // Test: $result should have a context, step, workflow and cm.
        $this->assertNotNull($result->context);
        $this->assertNotNull($result->step);
        $this->assertNotNull($result->workflow);
        $this->assertNotNull($result->cm);
    }

    /**
     * Tests the parse methods with state invalid state.
     *
     * @covers \block_workflow_command_setactivityvisibility::parse
     */
    public function test_parse_with_state_invalid_state(): void {
        $workflow = $this->create_activity_workflow('quiz', false);
        $step     = $this->create_step($workflow);
        $state    = $this->assign_workflow($workflow);

        // This should change the visibility to invalid.
        $args = 'invalid';

        // Try parsing with a state.
        $class = block_workflow_command::create('block_workflow_command_setactivityvisibility');
        $result = $class->parse($args, $step, $state);

        // Test: $result should have data.
        $this->assertNotNull($result);

        // There should be one error.
        $this->assertEquals(count($result->errors), 1);

        // Test: $result should have no visibility.
        $this->assertFalse(isset($result->visibility));

        // Test: $result should have a context, step, workflow and cm.
        $this->assertNotNull($result->context);
        $this->assertNotNull($result->step);
        $this->assertNotNull($result->workflow);
        $this->assertNotNull($result->cm);
    }

    /**
     * Tests the parse methods.
     *
     * @covers \block_workflow_command_setactivityvisibility::parse
     */
    public function test_parse_with_state_appliestocourse(): void {
        $workflow = $this->create_workflow(false);
        $step     = $this->create_step($workflow);
        $state    = $this->assign_workflow($workflow);

        // This should change the visibility to hidden.
        $args = 'hidden';

        // Try parsing without a context.
        $class = block_workflow_command::create('block_workflow_command_setactivityvisibility');
        $result = $class->parse($args, $step, $state);

        // Test: $result should have data.
        $this->assertNotNull($result);

        // There should be one error.
        $this->assertEquals(count($result->errors), 1);
    }

    /**
     * Tests the execute methods.
     *
     * @covers \block_workflow_command_setactivityvisibility::execute
     */
    public function test_execute_hidden(): void {
        global $DB;
        $workflow = $this->create_activity_workflow('quiz', false);
        $step     = $this->create_step($workflow);
        $state    = $this->assign_workflow($workflow);

        // This should change the visibility to hidden.
        $args = 'hidden';

        // Check that the parent course is visible.
        $check = $DB->get_record('course', ['id' => $this->courseid]);
        $this->assertEquals($check->visible, 1);

        $module = $workflow->appliesto;
        $sql = "SELECT cm.id
                FROM {" . $module . "} m
                INNER JOIN {course_modules} cm ON cm.instance = m.id
                INNER JOIN {context} c ON c.instanceid = cm.id
                INNER JOIN {modules} md ON md.id = cm.module
                WHERE md.name = ? AND cm.course = ? LIMIT 1";
        $instance = $DB->get_record_sql($sql, [$module, $this->courseid]);

        // Check that the activity visibility is currently visible.
        $check = $DB->get_record('course_modules', ['id' => $instance->id]);
        $this->assertEquals($check->visible, 1);

        // Execute.
        $class = block_workflow_command::create('block_workflow_command_setactivityvisibility');
        $class->execute($args, $state);

        // Check that the activity visibility is now hidden.
        $check = $DB->get_record('course_modules', ['id' => $instance->id]);
        $this->assertEquals($check->visible, 0);

        // This should not have affected the parent courses visibility.
        $check = $DB->get_record('course', ['id' => $this->courseid]);
        $this->assertEquals($check->visible, 1);
    }
}
