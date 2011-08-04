<?php

/**
 * Workflow block tests
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}

// Include our test library so that we can use the same mocking system for
// all tests
require_once(dirname(__FILE__) . '/lib.php');

class test_block_workflow_command_setcoursevisibility extends block_workflow_testlib {
    public function test_setcoursevisibility() {
        $command = new block_workflow_command_setcoursevisibility();
        $this->assertIsA($command, 'block_workflow_command');
    }

    public function test_parse_no_state_hidden() {
        $workflow = $this->create_workflow(false);
        $step     = $this->create_step($workflow);

        // This should change the visibility to hidden
        $args = 'hidden';

        // Try parsing without a state
        $class = block_workflow_command::create('block_workflow_command_setcoursevisibility');
        $result = $class->parse($args, $step);

        // $result should have data
        $this->assertNotNull($result);

        // There should be no errors
        $this->assertEqual(count($result->errors), 0);

        // $result should have a visible state of 0
        $this->assertEqual($result->visible, 0);

        // $result should have no id
        $this->assertFalse(isset($result->id));
    }

    public function test_parse_no_state_visible() {
        $workflow = $this->create_workflow(false);
        $step     = $this->create_step($workflow);

        // This should change the visibility to visible
        $args = 'visible';

        // Try parsing without a state
        $class = block_workflow_command::create('block_workflow_command_setcoursevisibility');
        $result = $class->parse($args, $step);

        // $result should have data
        $this->assertNotNull($result);

        // There should be no errors
        $this->assertEqual(count($result->errors), 0);

        // $result should have a visible state of 0
        $this->assertEqual($result->visible, 1);

        // $result should have no id
        $this->assertFalse(isset($result->id));
    }

    public function test_parse_no_state_invalid_state() {
        $workflow = $this->create_workflow(false);
        $step     = $this->create_step($workflow);

        // This should change the visibility to invalid
        $args = 'invalid';

        // Try parsing without a state
        $class = block_workflow_command::create('block_workflow_command_setcoursevisibility');
        $result = $class->parse($args, $step);

        // $result should have data
        $this->assertNotNull($result);

        // There should be one error
        $this->assertEqual(count($result->errors), 1);

        // $result should have no visible
        $this->assertFalse(isset($result->visible));

        // $result should have no id
        $this->assertFalse(isset($result->id));
    }

    public function test_parse_no_state_appliestoactivity() {
        $workflow = $this->create_activity_workflow('quiz', false);
        $step     = $this->create_step($workflow);

        // This should change the visibility to hidden
        $args = 'hidden';

        // Try parsing without a state
        $class = block_workflow_command::create('block_workflow_command_setcoursevisibility');
        $result = $class->parse($args, $step);

        // $result should have data
        $this->assertNotNull($result);

        // There should be one error
        $this->assertEqual(count($result->errors), 1);

        // $result should have no id
        $this->assertFalse(isset($result->id));
    }

    public function test_parse_with_state_hidden() {
        $workflow = $this->create_workflow(false);
        $step     = $this->create_step($workflow);
        $state    = $this->assign_workflow($workflow);

        // This should change the visibility to hidden
        $args = 'hidden';

        // Try parsing with a state
        $class = block_workflow_command::create('block_workflow_command_setcoursevisibility');
        $result = $class->parse($args, $step, $state);

        // $result should have data
        $this->assertNotNull($result);

        // There should be no errors
        $this->assertEqual(count($result->errors), 0);

        // $result should have a visible state of 0
        $this->assertEqual($result->visible, 0);

        // $result should have an id of the context's instanceid  ($this->courseid)
        $this->assertEqual($result->id, $this->courseid);
    }

    public function test_parse_with_state_visible() {
        $workflow = $this->create_workflow(false);
        $step     = $this->create_step($workflow);
        $state    = $this->assign_workflow($workflow);

        // This should change the visibility to visible
        $args = 'visible';

        // Try parsing with a state
        $class = block_workflow_command::create('block_workflow_command_setcoursevisibility');
        $result = $class->parse($args, $step, $state);

        // $result should have data
        $this->assertNotNull($result);

        // There should be no errors
        $this->assertEqual(count($result->errors), 0);

        // $result should have a visible state of 0
        $this->assertEqual($result->visible, 1);

        // $result should have an id of the context's instanceid  ($this->courseid)
        $this->assertEqual($result->id, $this->courseid);
    }

    public function test_parse_with_state_invalid_state() {
        $workflow = $this->create_workflow(false);
        $step     = $this->create_step($workflow);
        $state    = $this->assign_workflow($workflow);

        // This should change the visibility to invalid
        $args = 'invalid';

        // Try parsing with a state
        $class = block_workflow_command::create('block_workflow_command_setcoursevisibility');
        $result = $class->parse($args, $step, $state);

        // $result should have data
        $this->assertNotNull($result);

        // There should be one error
        $this->assertEqual(count($result->errors), 1);

        // $result should have no visible
        $this->assertFalse(isset($result->visible));

        // $result should have an id of the context's instanceid  ($this->courseid)
        $this->assertEqual($result->id, $this->courseid);
    }

    public function test_parse_with_state_appliestoactivity() {
        $workflow = $this->create_activity_workflow('quiz', false);
        $step     = $this->create_step($workflow);
        $state    = $this->assign_workflow($workflow);

        // This should change the visibility to hidden
        $args = 'hidden';

        // Try parsing with a state
        $class = block_workflow_command::create('block_workflow_command_setcoursevisibility');
        $result = $class->parse($args, $step, $state);

        // $result should have data
        $this->assertNotNull($result);

        // There should be one error
        $this->assertEqual(count($result->errors), 1);
    }

    public function test_execute_hidden() {
        $workflow = $this->create_workflow(false);
        $step     = $this->create_step($workflow);
        $state    = $this->assign_workflow($workflow);

        // This should change the visibility to hidden
        $args = 'hidden';

        // Check that the course visibility is currently visible
        $check = $this->testdb->get_record('course', array('id' => $this->courseid));
        $this->assertEqual($check->visible, 1);

        // Try parsing without a context
        $class = block_workflow_command::create('block_workflow_command_setcoursevisibility');
        $class->execute($args, $state);

        // Check that the course visibility is now hidden
        $check = $this->testdb->get_record('course', array('id' => $this->courseid));
        $this->assertEqual($check->visible, 0);
    }
}
