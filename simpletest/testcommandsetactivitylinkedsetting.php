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
 * Tests of {@link block_workflow_command_setactivitylinkedsetting}.
 *
 * @package   block_workflow
 * @copyright 2012 the Open University.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Include our test library so that we can use the same mocking system for
// all tests.
require_once(dirname(__FILE__) . '/lib.php');

/**
 * Tests of {@link block_workflow_command_setactivitylinkedsetting}.
 *
 * These tests cheat and uses a random workflow table, so we can be sure it exists.
 * That does mean, however, that the test commands are pretty senseless.
 *
 * @copyright 2012 the Open University.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class test_block_workflow_command_setactivitylinkedsetting extends block_workflow_testlib {
    public function test_setcoursevisibility() {
        $command = $this->make_command();
        $this->assertIsA($command, 'block_workflow_command');
    }

    protected function make_command() {
        $this->workflow = $this->create_activity_workflow('quiz', false);
        $this->step     = $this->create_step($this->workflow);
        return block_workflow_command::create('block_workflow_command_setactivitylinkedsetting');
    }

    public function test_parse_clear() {
        $command = $this->make_command();
        $result = $command->parse('block_workflow_steps by workflowid clear', $this->step);

        // Verify.
        $this->assertNotNull($result);
        $this->assertEqual(count($result->errors), 0);
        $this->assertEqual($result->table, 'block_workflow_steps');
        $this->assertEqual($result->fkcolumn, 'workflowid');
        $this->assertEqual($result->action, block_workflow_command_setactivitylinkedsetting::CLEAR);
    }

    public function test_parse_set() {
        $command = $this->make_command();
        $result = $command->parse('block_workflow_steps by workflowid set stepno 1 name Fred', $this->step);

        // Verify.
        $this->assertNotNull($result);
        $this->assertEqual(count($result->errors), 0);
        $this->assertEqual($result->table, 'block_workflow_steps');
        $this->assertEqual($result->fkcolumn, 'workflowid');
        $this->assertEqual($result->action, block_workflow_command_setactivitylinkedsetting::SET);
        $this->assertEqual($result->toset, array('stepno' => '1', 'name' => 'Fred'));
    }

    public function test_parse_missing_by() {
        $command = $this->make_command();
        $result = $command->parse('block_workflow_steps workflowid clear', $this->step);

        // Verify.
        $this->assertNotNull($result);
        $this->assertEqual(count($result->errors), 1);
    }

    public function test_parse_missing_clear() {
        $command = $this->make_command();
        $result = $command->parse('block_workflow_steps by workflowid', $this->step);

        // Verify.
        $this->assertNotNull($result);
        $this->assertEqual(count($result->errors), 1);
    }

    public function test_parse_missing_neither_clear_nor_set() {
        $command = $this->make_command();
        $result = $command->parse('block_workflow_steps by workflowid frog', $this->step);

        // Verify.
        $this->assertNotNull($result);
        $this->assertEqual(count($result->errors), 1);
    }

    public function test_parse_junk_after_clear() {
        $command = $this->make_command();
        $result = $command->parse('block_workflow_steps by workflowid clear junk', $this->step);

        // Verify.
        $this->assertNotNull($result);
        $this->assertEqual(count($result->errors), 1);
    }

    public function test_parse_cols_values_mismatched() {
        $command = $this->make_command();
        $result = $command->parse('block_workflow_steps by workflowid set name', $this->step);

        // Verify.
        $this->assertNotNull($result);
        $this->assertEqual(count($result->errors), 1);
    }

    public function test_parse_unknown_table() {
        $command = $this->make_command();
        $result = $command->parse('__unknown_db_table_name___ by workflowid clear', $this->step);

        // Verify.
        $this->assertNotNull($result);
        $this->assertEqual(count($result->errors), 1);
    }

    public function test_parse_unknown_fk_column() {
        $command = $this->make_command();
        $result = $command->parse('block_workflow_steps by __unknown_column__ clear', $this->step);

        // Verify.
        $this->assertNotNull($result);
        $this->assertEqual(count($result->errors), 1);
    }

    public function test_parse_unknown_set_column() {
        $command = $this->make_command();
        $result = $command->parse('block_workflow_steps by workflowid set name frog __unknown_column__ 1', $this->step);

        // Verify.
        $this->assertNotNull($result);
        $this->assertEqual(count($result->errors), 1);
    }
}
