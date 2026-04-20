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

namespace block_workflow\external;

defined('MOODLE_INTERNAL') || die();
// Include api test library so that we can use the same mocking system for all tests.
require_once(dirname(__FILE__) . '/external_api_base_lib.php');

/**
 * Unit tests for the get_step_state_comment webservice.
 *
 * @package block_workflow
 * @copyright 2023 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class get_step_state_comment_test extends external_api_base_lib {
    /**
     * Test get step state comment successfully.
     *
     * @covers \get_step_state_comment::execute
     */
    public function test_get_step_state_comment(): void {
        // Call the external service function.
        $returnvalue = get_step_state_comment::execute($this->state1id);

        // We need to execute the return values cleaning process to simulate
        // the web service server.
        $returnvalue = \core_external\external_api::clean_returnvalue(
            get_step_state_comment::execute_returns(),
            $returnvalue
        );
        $this->assertEquals('<p>Sample Comment</p>', $returnvalue['response']);
    }

    /**
     * Test get step state comment without capability.
     *
     * @covers \get_step_state_comment::execute
     * @runInSeparateProcess
     */
    public function test_get_step_state_comment_failed(): void {
        $this->resetAfterTest();
        $this->setUser($this->egstudent);

        // Remove the required capabilities by the external function.
        $context = \context_course::instance($this->course->id);
        $this->unassignUserCapability('block/workflow:dostep', $context->id, $this->studenroleid, $this->course->id);
        // User can't make change in block.
        $this->expectExceptionMessage(get_string('notallowedtodothisstep', 'block_workflow'));
        get_step_state_comment::execute($this->state1id);
    }
}
