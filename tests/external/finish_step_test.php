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
use block_workflow_step_state;

defined('MOODLE_INTERNAL') || die();
// Include api test library so that we can use the same mocking system for all tests.
require_once(dirname(__FILE__) . '/external_api_base_lib.php');

/**
 * Unit tests for the finish_step web service.
 *
 * @package block_workflow
 * @copyright 2023 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class finish_step_test extends external_api_base_lib {
    /**
     * Test finish step run successfully.
     *
     * @covers \finish_step::execute
     */
    public function test_finish_step(): void {
        // Call the external service function.
        $returnvalue = finish_step::execute($this->state1id, 'Finish comment', FORMAT_HTML);

        // We need to execute the return values cleaning process to simulate
        // the web service server.
        $returnvalue = \core_external\external_api::clean_returnvalue(
            finish_step::execute_returns(),
            $returnvalue
        );
        // Load the updated data for two states.
        $state1 = new block_workflow_step_state($this->state1id);
        $state2 = new block_workflow_step_state($returnvalue['stateid']);

        // Verify step 1 is complete.
        $this->assertEquals($this->step1->id, $state1->stepid);
        $this->assertEquals(BLOCK_WORKFLOW_STATE_COMPLETED, $state1->state);

        // Verify step 2 is active.
        $this->assertEquals($this->step2->id, $state2->stepid);
        $this->assertEquals(BLOCK_WORKFLOW_STATE_ACTIVE, $state2->state);
        // Finish step state 2.
        $returnvalue = finish_step::execute($state2->id, 'Finish comment', FORMAT_HTML);
        $state2 = new block_workflow_step_state($state2->id);
        // Verify step 2 is complete.
        $this->assertEquals(BLOCK_WORKFLOW_STATE_COMPLETED, $state2->state);
        // We should return the listofworkflows.
        $this->assertEquals(1, $returnvalue['listworkflows']);
    }
}
