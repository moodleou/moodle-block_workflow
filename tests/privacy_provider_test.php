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
 * Tests for the Privacy Provider API implementation.
 *
 * @package block_workflow
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_workflow;

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\writer;
use block_workflow\privacy\provider;
use core_privacy\local\request\userlist;

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../locallib.php');

/**
 * Tests for the Privacy Provider API implementation.
 *
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class privacy_provider_test extends \core_privacy\tests\provider_testcase {

    /** @var \stdClass A student who is enrolled in course */
    protected $student1;
    /** @var \stdClass A student who is enrolled in course */
    protected $student2;
    /** @var \stdClass A course context */
    protected $coursecontext;
    /** @var \stdClass A workflow block created in course */
    protected $workflowblock;
    /** @var \stdClass A workflow object created in course */
    protected $workflow;
    /** @var \stdClass A step created in workflow */
    protected $step;
    /** @var \stdClass A to-do task created in step */
    protected $todo;
    /** @var \stdClass */
    protected $generator;

    /**
     * All tests make database changes.
     * Set up for each test
     */
    public function setUp(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create the generator object and do standard checks.
        $generator = $this->getDataGenerator();
        $plugingenerator = $this->getDataGenerator()->get_plugin_generator('block_workflow');

        // Create course data to test.
        $course = $generator->create_course();
        $this->coursecontext = \context_course::instance($course->id);

        // Create a new workflow block.
        $this->workflowblock = $plugingenerator->create_instance(['parentcontextid' => $this->coursecontext->id]);

        $this->student1 = $generator->create_user();
        $this->student2 = $generator->create_user();

        // Create a new workflow object.
        $this->workflow = new \block_workflow_workflow();

        // Create a new workflow.
        $data = new \stdClass();
        $data->shortname            = 'courseworkflow';
        $data->name                 = 'First course workflow';
        $data->appliesto            = 'course';
        $data->obsolete             = 0;
        $data->description          = 'This is a test workflow applying to a course for the unit test';
        $data->descriptionformat    = FORMAT_HTML;

        // Create_workflow will return a completed workflow object.
        $this->workflow->create_workflow($data);

        // When creating a workflow, the initial step will have automatically been created.
        // Retrieve the list of steps.
        $steps = $this->workflow->steps();
        $this->workflow->step_states($this->coursecontext->id);
        $this->step = array_pop($steps);

        // Add the workflow to our course (returns the block_workflow_step_state).
        $this->setUser($this->student1);
        $state = $this->workflow->add_to_context($this->coursecontext->id);

        // Finish the first step.
        $state->finish_step('Student 1 comment on task 1', FORMAT_HTML);

        // Create a new to-do.
        $this->todo = new \block_workflow_todo();
        $data = new \stdClass();
        $data->stepid   = $this->step->id;
        $data->task     = 'TASK ONE';
        $this->todo->create_todo($data);

        // Toggle the to-do item.
        $state->todo_toggle($this->todo->id);
    }

    /**
     * Test for provider::get_contexts_for_userid().
     */
    public function test_get_contexts_for_userid() {
        // Get workflow context for student1.
        $contextids = provider::get_contexts_for_userid($this->student1->id)->get_contextids();

        // Context for student1 equal workflow context.
        $this->assertTrue(in_array($this->workflowblock->parentcontextid, $contextids));
    }

    /**
     * Test for provider::get_users_in_context().
     */
    public function test_get_users_in_context() {
        $userlist = new userlist($this->coursecontext, 'block_workflow');
        $this->assertCount(0, $userlist);
        provider::get_users_in_context($userlist);
        $this->assertCount(1, $userlist);
        $this->assertEquals([$this->student1->id], $userlist->get_userids());
    }

    /**
     * Test for provider::export_user_data().
     */
    public function test_export_user_data() {
        // Get workflow context for student1.
        $contextids = provider::get_contexts_for_userid($this->student1->id)->get_contextids();
        $context = \context::instance_by_id($contextids[0]);

        $contextlist = new approved_contextlist($this->student1, 'block_workflow', $contextids);

        // Export all of the data for the context.
        provider::export_user_data($contextlist);
        $writer = writer::with_context($context);
        $this->assertTrue($writer->has_any_data());
        $data = $writer->get_data([get_string('pluginname', 'block_workflow')]);

        // Check student1 have 2 state change to the step.
        $this->assertEquals((object)[
            'workflowname' => $this->workflow->name,
            'description' => $this->workflow->description,
            'stepname' => $this->step->name,
            'userid' => get_string('privacy_you', 'block_workflow'),
            'newstate' => 'active'
        ], $data->statechangedata[0]);
        $this->assertEquals((object)[
            'workflowname' => $this->workflow->name,
            'description' => $this->workflow->description,
            'stepname' => $this->step->name,
            'userid' => get_string('privacy_you', 'block_workflow'),
            'newstate' => 'completed'
        ], $data->statechangedata[1]);

        // Check student1 has one task done.
        $this->assertEquals((object)[
            'stepname' => $this->step->name,
            'taskdone' => $this->todo->task,
            'userid' => get_string('privacy_you', 'block_workflow')
        ], $data->tododonedata[0]);
    }

    /**
     * Test for delete_data_for_user().
     */
    public function test_delete_data_for_user() {
        global $DB;
        // Get workflow context for student1.
        $contextids = provider::get_contexts_for_userid($this->student1->id)->get_contextids();
        $contextlist = new approved_contextlist($this->student1, 'block_workflow', $contextids);

        provider::delete_data_for_user($contextlist);

        // State change by student 1 should be updated to admin user id.
        $this->assertCount(0, $DB->get_records('block_workflow_state_changes', ['userid' => $this->student1->id]));
        $this->assertCount(2, $DB->get_records('block_workflow_state_changes', ['userid' => get_admin()->id]));

        // To_do done change by student 1 should be updated to admin user id.
        $this->assertCount(0, $DB->get_records('block_workflow_todo_done', ['userid' => $this->student1->id]));
        $this->assertCount(1, $DB->get_records('block_workflow_todo_done', ['userid' => get_admin()->id]));
    }

    /**
     * Test for provider::test_delete_data_for_users().
     */
    public function test_delete_data_for_users() {
        global $DB;
        // Get workflow context for student1.
        $approveduserids = [$this->student1->id];
        $approvedlist = new approved_userlist($this->coursecontext, 'block_workflow', $approveduserids);

        provider::delete_data_for_users($approvedlist);

        // State change by student 1 should be updated to admin user id.
        $this->assertCount(0, $DB->get_records('block_workflow_state_changes', ['userid' => $this->student1->id]));
        $this->assertCount(2, $DB->get_records('block_workflow_state_changes', ['userid' => get_admin()->id]));

        // To_do done change by student 1 should be updated to admin user id.
        $this->assertCount(0, $DB->get_records('block_workflow_todo_done', ['userid' => $this->student1->id]));
        $this->assertCount(1, $DB->get_records('block_workflow_todo_done', ['userid' => get_admin()->id]));
    }

    /**
     * Test for delete_data_for_all_users_in_context().
     */
    public function test_delete_data_for_all_users_in_context() {
        global $DB;

        // Add the workflow to our course (returns the block_workflow_step_state).
        $this->setUser($this->student2);
        $state = $this->workflow->add_to_context($this->coursecontext->id);

        // Finish the first step.
        $state->finish_step('Student 2 comment on task 1', FORMAT_HTML);

        // Create a new to-do.
        $this->todo = new \block_workflow_todo();
        $data = new \stdClass();
        $data->stepid = $this->step->id;
        $data->task = 'TASK ONE';
        $this->todo->create_todo($data);

        // Toggle the to-do item.
        $state->todo_toggle($this->todo->id);

        $params = ['statescontextid' => $this->coursecontext->id];
        $statechangesql = "SELECT statechanges.id, statechanges.userid
                             FROM {block_workflow_state_changes} statechanges
                        LEFT JOIN {block_workflow_step_states} states ON states.id = statechanges.stepstateid
                            WHERE states.contextid = :statescontextid";

        $statechange = $DB->get_records_sql($statechangesql, $params);
        $tododonesql = "SELECT done.id, done.userid
                          FROM {block_workflow_todo_done} done
                     LEFT JOIN {block_workflow_step_states} states ON states.id = done.stepstateid
                         WHERE states.contextid = :statescontextid";
        $tododone = $DB->get_records_sql($tododonesql, $params);

        // Before deletion, we should have 4 state change, 2 to-do task done for student1 and student2.
        $this->assertEquals(4, count($statechange));
        $this->assertEquals(2, count($tododone));

        // Delete data based on context.
        provider::delete_data_for_all_users_in_context($this->coursecontext);

        // After deletion, the date for student1 and student2 should have been update to admin user id.
        $statechange = $DB->get_records_sql($statechangesql, $params);
        $tododone = $DB->get_records_sql($tododonesql, $params);
        $this->assertEquals(4, count($statechange));
        $this->assertEquals(2, count($tododone));
        foreach ($statechange as $recordstatechange) {
            $this->assertEquals(get_admin()->id, $recordstatechange->userid);
        }
        foreach ($tododone as $recordtodo) {
            $this->assertEquals(get_admin()->id, $recordtodo->userid);
        }
    }
}
