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

class test_block_workflow_command_assignrole extends block_workflow_testlib {
    public function test_assignrole() {
        $command = new block_workflow_command_assignrole();
        $this->assertIsA($command, 'block_workflow_command');
    }

    public function test_parse_no_state() {
        $workflow = $this->create_workflow(false);
        $step     = $this->create_step($workflow);

        // This should assign an editingteacher role to a teacher and student
        $args = 'editingteacher to teacher student';

        // Try parsing without a context
        $class = block_workflow_command::create('block_workflow_command_assignrole');
        $result = $class->parse($args, $step);

        // $result should have data
        $this->assertNotNull($result);

        // There should be no errors
        $this->assertEqual(count($result->errors), 0);

        // $result should have a newrole of editingteacher
        $this->assertEqual($result->newrole->name, 'editingteacher');

        // $result should have a list of roles containing 'teacher' and 'student'
        $this->assertIsA($result->roles, 'array');
        $this->assertEqual(count($result->roles), 2);
        $this->assertEqual(count(array_filter($result->roles, create_function('$r', 'return ($r->name == "manager");'))), 0);
        $this->assertEqual(count(array_filter($result->roles, create_function('$r', 'return ($r->name == "teacher");'))), 1);
        $this->assertEqual(count(array_filter($result->roles, create_function('$r', 'return ($r->name == "student");'))), 1);

        // because no $state was given, $result->users should be an empty array
        $this->assertIsA($result->users, 'array');
        $this->assertEqual(count($result->users), 0);
    }

    public function test_parse_no_state_invalid_newrole() {
        $workflow = $this->create_workflow(false);
        $step     = $this->create_step($workflow);

        // This should assign an invalid role to a teacher and student
        $args = 'badrole to teacher student';

        // Try parsing without a context
        $class = block_workflow_command::create('block_workflow_command_assignrole');
        $result = $class->parse($args, $step);

        // $result should have data
        $this->assertNotNull($result);

        // There should be one error
        $this->assertEqual(count($result->errors), 1);

        // $result should have no newrole
        $this->assertFalse($result->newrole);

        // $result should have a list of roles containing 'teacher' and 'student'
        $this->assertIsA($result->roles, 'array');
        $this->assertEqual(count($result->roles), 2);
        $this->assertEqual(count(array_filter($result->roles, create_function('$r', 'return ($r->name == "manager");'))), 0);
        $this->assertEqual(count(array_filter($result->roles, create_function('$r', 'return ($r->name == "teacher");'))), 1);
        $this->assertEqual(count(array_filter($result->roles, create_function('$r', 'return ($r->name == "student");'))), 1);

        // because no $state was given, $result->users should be an empty array
        $this->assertIsA($result->users, 'array');
        $this->assertEqual(count($result->users), 0);
    }

    public function test_parse_no_state_invalid_targetrole() {
        $workflow = $this->create_workflow(false);
        $step     = $this->create_step($workflow);

        // This should assign an editing teacher role to a teacher
        $args = 'editingteacher to badrole student';

        // Try parsing without a context
        $class = block_workflow_command::create('block_workflow_command_assignrole');
        $result = $class->parse($args, $step);

        // $result should have data
        $this->assertNotNull($result);

        // There should be one error for the invalid role
        $this->assertEqual(count($result->errors), 1);

        // $result should have a newrole of editingteacher
        $this->assertEqual($result->newrole->name, 'editingteacher');

        // $result should have a list of roles containing 'student'
        $this->assertIsA($result->roles, 'array');
        $this->assertEqual(count($result->roles), 1);
        $this->assertEqual(count(array_filter($result->roles, create_function('$r', 'return ($r->name == "manager");'))), 0);
        $this->assertEqual(count(array_filter($result->roles, create_function('$r', 'return ($r->name == "teacher");'))), 0);
        $this->assertEqual(count(array_filter($result->roles, create_function('$r', 'return ($r->name == "student");'))), 1);

        // because no $state was given, $result->users should be an empty array
        $this->assertIsA($result->users, 'array');
        $this->assertEqual(count($result->users), 0);
    }

    public function test_parse_with_state() {
        $workflow = $this->create_workflow(false);
        $step     = $this->create_step($workflow);
        $state    = $this->assign_workflow($workflow);

        // This should assign an editing teacher role to a teacher
        $args = 'editingteacher to teacher student';

        // Try parsing without a context
        $class = block_workflow_command::create('block_workflow_command_assignrole');
        $result = $class->parse($args, $step, $state);

        // $result should have data
        $this->assertNotNull($result);

        // There should be no errors
        $this->assertEqual(count($result->errors), 0);

        // $result should have a newrole of editingteacher
        $this->assertEqual($result->newrole->name, 'editingteacher');

        // $result should have a list of roles containing 'teacher' and 'student'
        $this->assertIsA($result->roles, 'array');
        $this->assertEqual(count($result->roles), 2);
        $this->assertEqual(count(array_filter($result->roles, create_function('$r', 'return ($r->name == "manager");'))), 0);
        $this->assertEqual(count(array_filter($result->roles, create_function('$r', 'return ($r->name == "teacher");'))), 1);
        $this->assertEqual(count(array_filter($result->roles, create_function('$r', 'return ($r->name == "student");'))), 1);

        // because a $state was given, $result->users should be an empty array
        $this->assertIsA($result->users, 'array');

        // There is one person in each role, and we're assigning two roles
        $this->assertEqual(count($result->users), 2);
    }

    public function test_parse_with_state_invalid_newrole() {
        $workflow = $this->create_workflow(false);
        $step     = $this->create_step($workflow);
        $state    = $this->assign_workflow($workflow);

        // This should assign an invalid role to a teacher and student
        $args = 'badrole to teacher student';

        // Try parsing without a context
        $class = block_workflow_command::create('block_workflow_command_assignrole');
        $result = $class->parse($args, $step, $state);

        // $result should have data
        $this->assertNotNull($result);

        // There should be one error
        $this->assertEqual(count($result->errors), 1);

        // $result should have no newrole
        $this->assertFalse($result->newrole);

        // $result should have a list of roles containing 'teacher' and 'student'
        $this->assertIsA($result->roles, 'array');
        $this->assertEqual(count($result->roles), 2);
        $this->assertEqual(count(array_filter($result->roles, create_function('$r', 'return ($r->name == "manager");'))), 0);
        $this->assertEqual(count(array_filter($result->roles, create_function('$r', 'return ($r->name == "teacher");'))), 1);
        $this->assertEqual(count(array_filter($result->roles, create_function('$r', 'return ($r->name == "student");'))), 1);

        // because a $state was given, $result->users should be an empty array
        $this->assertIsA($result->users, 'array');

        // There is one person in each role, and we're assigning two roles
        $this->assertEqual(count($result->users), 2);
    }

    public function test_parse_with_state_invalid_targetrole() {
        $workflow = $this->create_workflow(false);
        $step     = $this->create_step($workflow);
        $state    = $this->assign_workflow($workflow);

        // This should assign an invalid role to a teacher and student
        $args = 'editingteacher to badrole student';

        // Try parsing without a context
        $class = block_workflow_command::create('block_workflow_command_assignrole');
        $result = $class->parse($args, $step, $state);

        // $result should have data
        $this->assertNotNull($result);

        // There should be one error for the invalid role
        $this->assertEqual(count($result->errors), 1);

        // $result should have a newrole of editingteacher
        $this->assertEqual($result->newrole->name, 'editingteacher');

        // $result should have a list of roles containing 'student'
        $this->assertIsA($result->roles, 'array');
        $this->assertEqual(count($result->roles), 1);
        $this->assertEqual(count(array_filter($result->roles, create_function('$r', 'return ($r->name == "manager");'))), 0);
        $this->assertEqual(count(array_filter($result->roles, create_function('$r', 'return ($r->name == "teacher");'))), 0);
        $this->assertEqual(count(array_filter($result->roles, create_function('$r', 'return ($r->name == "student");'))), 1);

        // because a $state was given, $result->users should be an empty array
        $this->assertIsA($result->users, 'array');

        // There is one person in each role, and we're assigning one valid role
        $this->assertEqual(count($result->users), 1);
    }

    public function test_execute() {
        $workflow = $this->create_workflow(false);
        $step     = $this->create_step($workflow);
        $state    = $this->assign_workflow($workflow);

        // This should assign an invalid role to a teacher and student
        $args = 'editingteacher to teacher student';

        // Try parsing without a context - we need this for verification
        $class = block_workflow_command::create('block_workflow_command_assignrole');
        $result = $class->parse($args, $step, $state);

        /**
         * Check the current assignments
         */
        foreach ($result->users as $user) {
            $assignments = $this->testdb->get_records('role_assignments', array('roleid' => $result->newrole->id,
                    'contextid' => $result->context->id, 'userid' => $user->id, 'component' => 'block_workflow',
                    'itemid' => $state->id));
            $this->assertEqual(count($assignments), 0);
        }

        // Execute
        $class = block_workflow_command::create('block_workflow_command_assignrole');
        $class->execute($args, $state);

        /**
         * Check the new assignments
         */
        foreach ($result->users as $user) {
            $assignments = $this->testdb->get_records('role_assignments', array('roleid' => $result->newrole->id,
                    'contextid' => $result->context->id, 'userid' => $user->id, 'component' => 'block_workflow',
                    'itemid' => $state->id));
            $this->assertEqual(count($assignments), 1);
        }

    }
}
