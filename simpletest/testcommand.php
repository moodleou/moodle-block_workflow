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
 * Workflow block unit tests
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

class test_block_workflow_command extends block_workflow_testlib {

    /**
     * Test the role_exists function
     * - Positive test for a known good role
     * - Negative test for a known bad role (check for exception)
     */
    public function test_role_exists() {
        // First test that a known good role works.
        $result = block_workflow_command::role_exists('manager');
        $this->assertIsA($result, 'stdClass');

        $errors = array();
        $result = block_workflow_command::require_role_exists('manager', $errors);
        $this->assertIsA($result, 'stdClass');
        $this->assertEqual(count($errors), 0);

        // And test that a known bad role doesn't work.
        $result = block_workflow_command::role_exists('invalidrole');
        $this->assertFalse($result);

        $errors = array();
        $result = block_workflow_command::require_role_exists('invalidrole', $errors);
        $this->assertEqual(count($errors), 1);
    }

    /**
     * Test the type of workflow
     * - Positive test for an activity (activity == true)
     * - Negative test for an activity (activity == false)
     */
    public function test_is_activity() {
        // Create a new workflow.
        $data = new stdClass();
        $data->shortname            = 'sampleworkflow';
        $data->name                 = 'sampleworkflow';
        $data->appliesto            = 'quiz';
        $data->obsolete             = 0;
        $data->description          = 'This is a test workflow';
        $data->descriptionformat    = FORMAT_PLAIN;

        // Create a new workflow object.
        $workflow = new block_workflow_workflow();
        $workflow->create_workflow($data);

        // Check that this is an activity.
        $result = block_workflow_command::is_activity($workflow);
        $this->assertTrue($result);

        // Modify the workflow to be a course workflow.
        $data = new stdClass();
        $data->appliesto            = 'course';
        $workflow->update($data);

        // And confirm that this is no longer an activity.
        $result = block_workflow_command::is_activity($workflow);
        $this->assertFalse($result);
    }
}
