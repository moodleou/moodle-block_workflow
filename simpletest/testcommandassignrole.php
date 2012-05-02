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
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Include our test library so that we can use the same mocking system for
// all tests.
require_once(dirname(__FILE__) . '/lib.php');

class test_block_workflow_command_assignrole extends block_workflow_testlib {
    public function test_assignrole() {
        $command = new block_workflow_command_assignrole();
        $this->assertIsA($command, 'block_workflow_command');
    }

    public function test_parse_no_state() {
        $workflow = $this->create_workflow(false);
        $step     = $this->create_step($workflow);

        // This should assign an editingteacher role to a teacher and student.
        $args = 'editingteacher to teacher student';

        // Try parsing without a context.
        $class = block_workflow_command::create('block_workflow_command_assignrole');
        $result = $class->parse($args, $step);

        // Test: $result should have data.
        $this->assertNotNull($result);

        // There should be no errors.
        $this->assertEqual(count($result->errors), 0);

        // Test: $result should have a newrole of editingteacher.
        $this->assertEqual($result->newrole->name, 'editingteacher');

        // Test: $result should have a list of roles containing 'teacher' and 'student'.
        $this->assertIsA($result->roles, 'array');
        $this->assertEqual(count($result->roles), 2);
        $this->assertEqual(count(array_filter($result->roles, create_function('$r', 'return ($r->name == "manager");'))), 0);
        $this->assertEqual(count(array_filter($result->roles, create_function('$r', 'return ($r->name == "teacher");'))), 1);
        $this->assertEqual(count(array_filter($result->roles, create_function('$r', 'return ($r->name == "student");'))), 1);

        // No $state was given so $result->users should be an empty array.
        $this->assertIsA($result->users, 'array');
        $this->assertEqual(count($result->users), 0);
    }

    public function test_parse_no_state_invalid_newrole() {
        $workflow = $this->create_workflow(false);
        $step     = $this->create_step($workflow);

        // This should assign an invalid role to a teacher and student.
        $args = 'badrole to teacher student';

        // Try parsing without a context.
        $class = block_workflow_command::create('block_workflow_command_assignrole');
        $result = $class->parse($args, $step);

        // Test: $result should have data.
        $this->assertNotNull($result);

        // There should be one error.
        $this->assertEqual(count($result->errors), 1);

        // Test: $result should have no newrole.
        $this->assertFalse($result->newrole);

        // Test: $result should have a list of roles containing 'teacher' and 'student'.
        $this->assertIsA($result->roles, 'array');
        $this->assertEqual(count($result->roles), 2);
        $this->assertEqual(count(array_filter($result->roles, create_function('$r', 'return ($r->name == "manager");'))), 0);
        $this->assertEqual(count(array_filter($result->roles, create_function('$r', 'return ($r->name == "teacher");'))), 1);
        $this->assertEqual(count(array_filter($result->roles, create_function('$r', 'return ($r->name == "student");'))), 1);

        // No $state was given so $result->users should be an empty array.
        $this->assertIsA($result->users, 'array');
        $this->assertEqual(count($result->users), 0);
    }

    public function test_parse_no_state_invalid_targetrole() {
        $workflow = $this->create_workflow(false);
        $step     = $this->create_step($workflow);

        // This should assign an editing teacher role to a teacher.
        $args = 'editingteacher to badrole student';

        // Try parsing without a context.
        $class = block_workflow_command::create('block_workflow_command_assignrole');
        $result = $class->parse($args, $step);

        // Test: $result should have data.
        $this->assertNotNull($result);

        // There should be one error for the invalid role.
        $this->assertEqual(count($result->errors), 1);

        // Test: $result should have a newrole of editingteacher.
        $this->assertEqual($result->newrole->name, 'editingteacher');

        // Test: $result should have a list of roles containing 'student'.
        $this->assertIsA($result->roles, 'array');
        $this->assertEqual(count($result->roles), 1);
        $this->assertEqual(count(array_filter($result->roles, create_function('$r', 'return ($r->name == "manager");'))), 0);
        $this->assertEqual(count(array_filter($result->roles, create_function('$r', 'return ($r->name == "teacher");'))), 0);
        $this->assertEqual(count(array_filter($result->roles, create_function('$r', 'return ($r->name == "student");'))), 1);

        // No $state was given so $result->users should be an empty array.
        $this->assertIsA($result->users, 'array');
        $this->assertEqual(count($result->users), 0);
    }

    public function test_parse_with_state() {
        $workflow = $this->create_workflow(false);
        $step     = $this->create_step($workflow);
        $state    = $this->assign_workflow($workflow);

        // This should assign an editing teacher role to a teacher.
        $args = 'editingteacher to teacher student';

        // Try parsing without a context.
        $class = block_workflow_command::create('block_workflow_command_assignrole');
        $result = $class->parse($args, $step, $state);

        // Test: $result should have data.
        $this->assertNotNull($result);

        // There should be no errors.
        $this->assertEqual(count($result->errors), 0);

        // Test: $result should have a newrole of editingteacher.
        $this->assertEqual($result->newrole->name, 'editingteacher');

        // Test: $result should have a list of roles containing 'teacher' and 'student'.
        $this->assertIsA($result->roles, 'array');
        $this->assertEqual(count($result->roles), 2);
        $this->assertEqual(count(array_filter($result->roles, create_function('$r', 'return ($r->name == "manager");'))), 0);
        $this->assertEqual(count(array_filter($result->roles, create_function('$r', 'return ($r->name == "teacher");'))), 1);
        $this->assertEqual(count(array_filter($result->roles, create_function('$r', 'return ($r->name == "student");'))), 1);

        // A $state was given so $result->users should be an empty array.
        $this->assertIsA($result->users, 'array');

        // There is one person in each role, and we're assigning two roles.
        $this->assertEqual(count($result->users), 2);
    }

    public function test_parse_with_state_invalid_newrole() {
        $workflow = $this->create_workflow(false);
        $step     = $this->create_step($workflow);
        $state    = $this->assign_workflow($workflow);

        // This should assign an invalid role to a teacher and student.
        $args = 'badrole to teacher student';

        // Try parsing without a context.
        $class = block_workflow_command::create('block_workflow_command_assignrole');
        $result = $class->parse($args, $step, $state);

        // Test: $result should have data.
        $this->assertNotNull($result);

        // There should be one error.
        $this->assertEqual(count($result->errors), 1);

        // Test: $result should have no newrole.
        $this->assertFalse($result->newrole);

        // Test: $result should have a list of roles containing 'teacher' and 'student'.
        $this->assertIsA($result->roles, 'array');
        $this->assertEqual(count($result->roles), 2);
        $this->assertEqual(count(array_filter($result->roles, create_function('$r', 'return ($r->name == "manager");'))), 0);
        $this->assertEqual(count(array_filter($result->roles, create_function('$r', 'return ($r->name == "teacher");'))), 1);
        $this->assertEqual(count(array_filter($result->roles, create_function('$r', 'return ($r->name == "student");'))), 1);

        // A $state was given so $result->users should be an empty array.
        $this->assertIsA($result->users, 'array');

        // There is one person in each role, and we're assigning two roles.
        $this->assertEqual(count($result->users), 2);
    }

    public function test_parse_with_state_invalid_targetrole() {
        $workflow = $this->create_workflow(false);
        $step     = $this->create_step($workflow);
        $state    = $this->assign_workflow($workflow);

        // This should assign an invalid role to a teacher and student.
        $args = 'editingteacher to badrole student';

        // Try parsing without a context.
        $class = block_workflow_command::create('block_workflow_command_assignrole');
        $result = $class->parse($args, $step, $state);

        // Test: $result should have data.
        $this->assertNotNull($result);

        // There should be one error for the invalid role.
        $this->assertEqual(count($result->errors), 1);

        // Test: $result should have a newrole of editingteacher.
        $this->assertEqual($result->newrole->name, 'editingteacher');

        // Test: $result should have a list of roles containing 'student'.
        $this->assertIsA($result->roles, 'array');
        $this->assertEqual(count($result->roles), 1);
        $this->assertEqual(count(array_filter($result->roles, create_function('$r', 'return ($r->name == "manager");'))), 0);
        $this->assertEqual(count(array_filter($result->roles, create_function('$r', 'return ($r->name == "teacher");'))), 0);
        $this->assertEqual(count(array_filter($result->roles, create_function('$r', 'return ($r->name == "student");'))), 1);

        // A $state was given so $result->users should be an empty array.
        $this->assertIsA($result->users, 'array');

        // There is one person in each role, and we're assigning one valid role.
        $this->assertEqual(count($result->users), 1);
    }

    public function test_execute() {
        $workflow = $this->create_workflow(false);
        $step     = $this->create_step($workflow);
        $state    = $this->assign_workflow($workflow);

        // This should assign an invalid role to a teacher and student.
        $args = 'editingteacher to teacher student';

        // Try parsing without a context - we need this for verification.
        $class = block_workflow_command::create('block_workflow_command_assignrole');
        $result = $class->parse($args, $step, $state);

        // Check the current assignments.
        foreach ($result->users as $user) {
            $assignments = $this->testdb->get_records('role_assignments', array('roleid' => $result->newrole->id,
                    'contextid' => $result->context->id, 'userid' => $user->id, 'component' => 'block_workflow',
                    'itemid' => $state->id));
            $this->assertEqual(count($assignments), 0);
        }

        // Execute.
        $class = block_workflow_command::create('block_workflow_command_assignrole');
        $class->execute($args, $state);

        // Check the new assignments.
        foreach ($result->users as $user) {
            $assignments = $this->testdb->get_records('role_assignments', array('roleid' => $result->newrole->id,
                    'contextid' => $result->context->id, 'userid' => $user->id, 'component' => 'block_workflow',
                    'itemid' => $state->id));
            $this->assertEqual(count($assignments), 1);
        }

    }
}
