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
 * Unit tests for the update_step_state_task_state webservice.
 *
 * @package block_workflow
 * @copyright 2023 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class update_step_state_task_state_test extends external_api_base_lib {

    /**
     * Test update update_step_state_task_state successfully.
     *
     * @covers \update_step_state_task_state::execute
     */
    public function test_update_step_state_task_state(): void {
        // Create a new to-do.
        $todo = new \block_workflow_todo();
        $data = new \stdClass();
        $data->stepid = $this->step1->id;
        $data->task = 'TASK ONE';
        $todo->create_todo($data);

        // Call the external service function.
        $returnvalue = update_step_state_task_state::execute($this->state1id, $todo->id, true);

        // We need to execute the return values cleaning process to simulate
        // the web service server.
        $returnvalue = \core_external\external_api::clean_returnvalue(
            update_step_state_task_state::execute_returns(),
            $returnvalue
        );
        $this->assertEquals(1, $returnvalue['response']);
    }
}
