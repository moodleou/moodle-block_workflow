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
 * Workflow block test for extra notify fuctionality.
 *
 * @package   block_workflow
 * @copyright 2012 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group block_workflow
 * */

defined('MOODLE_INTERNAL') || die();

// Make sure the code being tested is accessible.
global $CFG;
require_once($CFG->dirroot . '/blocks/workflow/locallib.php');
require_once($CFG->dirroot . '/blocks/workflow/tests/lib.php');

class block_workflow_extra_notify_test extends block_workflow_testlib {
    public function test_extra_notify() {
        global $CFG, $DB;
        $now = time();
        $after5days = $this->get_days(5);
        $before5days = $this->get_days(5, 'before');
        $timestamp1 = $now - $before5days - 60; // Subtract one minute.
        $timestamp2 = $now - $after5days - 60; // Subtract one minute.

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        // Create a course object.
        $course1 = $generator->create_course(array('shortname' => 'M123-12J', 'startdate' => $timestamp1));
        $coursecontext1 = context_course::instance($course1->id);

        // Create another course object.
        $course2 = $generator->create_course(array('shortname' => 'K123-12J', 'startdate' => $timestamp2));
        $coursecontext2 = context_course::instance($course2->id);

        // Generate a vl_v_crs_version_pres table.
        $this->create_version_pres_tables();

        // Insert data to the above table.
        $courseshortname = 'M123-12J';
        $studentopendate = '2013-04-11';
        $DB->execute("INSERT INTO vl_v_crs_version_pres (vle_course_short_name, vle_student_open_date) " .
                "VALUES ('$courseshortname', '$studentopendate')");

        $courseondataloadtable = $DB->get_record_sql(
                'SELECT * FROM vl_v_crs_version_pres ' .
                'WHERE vle_course_short_name = ?', array($courseshortname), MUST_EXIST);

        // Create an email template
        $data  = new stdClass();
        $data->shortname = 'testing';
        $data->subject   = 'Workflow notification';
        $data->message   = 'This is a notification message';

        $email = new block_workflow_email();
        $email->create($data);

        // Create a new workflow object which applies to course.
        $stepoptions = array('extranotify' => 'course;startdate', 'extranotifyoffset' => $before5days,
                'onextranotifyscript' => 'email testing to manager');
        list($courseworkflow, $step1) = $this->create_a_workflow_with_one_step($stepoptions);

        // Required DB tables are not populated and therefore following methods return empty arrays.
        $stepoptions = array('extranotify', 'extranotifyoffset', 'onextranotifyscript');
        $activesteps = block_workflow_get_active_steps_with_fields_not_null($stepoptions);
        $this->assertEmpty($activesteps);

        // Add to context and check the step is active.
        $state1 = $courseworkflow->add_to_context($coursecontext1->id);
        $this->assertEquals($step1->id, $state1->stepid);
        $this->assertEquals(BLOCK_WORKFLOW_STATE_ACTIVE, $state1->state);

        // Get all active steps.
        $activesteps = block_workflow_get_active_steps_with_fields_not_null($stepoptions);
        $this->assertEquals(1, count($activesteps));
        $course1->vle_student_open_date = strtotime($courseondataloadtable->vle_student_open_date);

        // Create expected objects for active steps and test them against the actual objects.
        $expectedactivesteps = $this->create_expected_active_step($state1, $step1, 'course', $course1, 0, 'extranotify');
        $this->assertEquals($expectedactivesteps, $activesteps);

        // Add to context and check the step is active.
        $state2 = $courseworkflow->add_to_context($coursecontext2->id);
        $this->assertEquals($step1->id, $state1->stepid);
        $this->assertEquals(BLOCK_WORKFLOW_STATE_ACTIVE, $state1->state);

        // Get all active steps.
        $activesteps = block_workflow_get_active_steps_with_fields_not_null($stepoptions);
        $this->assertEquals(2, count($activesteps));

        // Create expected objects for active steps and test them against the actual objects.
        $expectedactivesteps += $this->create_expected_active_step($state2, $step1, 'course', $course2, 0, 'extranotify');
        $this->assertEquals($expectedactivesteps, $activesteps);

        // Check relevant fields in 'block_workflow_step_states' table before sending extra notification.
        $state = $DB->get_record('block_workflow_step_states', array('id' => $state1->id));
        $this->assertEquals(BLOCK_WORKFLOW_STATE_ACTIVE, $state->state);

        // Get ready active steps and finish them automatically.
        block_workflow_send_extra_notification();

        // Check relevant fields in 'block_workflow_step_states' table after finishing automatically.
        $stateafterfinish = $DB->get_record('block_workflow_step_states', array('id' => $state1->id));
        $this->assertEquals(BLOCK_WORKFLOW_STATE_ACTIVE, $stateafterfinish->state);
    }
}
