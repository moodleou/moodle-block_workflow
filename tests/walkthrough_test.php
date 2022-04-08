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
 * Test the workflow by creating a course and a workflow, stepping through
 * the steps, and checking that the righ things happen.
 *
 * @package   block_workflow
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group block_workflow
 */

namespace block_workflow;

use block_workflow_workflow;
use block_workflow_step;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once(dirname(__FILE__) . '/../locallib.php');


/**
 * Test the workflow by creating a course and a workflow, stepping through
 * the steps, and checking that the righ things happen.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class walkthrough_test extends \advanced_testcase {

    /**
     * Test some of the basic workflow actions including:
     * - creating a new workflow;
     * - checking the default step created when a new workflow is created;
     * - adding a step and checking it's data;
     * - cloning that step and checking it's data; and
     * - re-ordering the steps and checking the resultant data.
     */
    public function test_course_workflow() {
        global $DB;
        $this->resetAfterTest(true);

        $roleids = $DB->get_records_menu('role', null, '', 'shortname,id');

        // Create a course ready to test with.
        $generator = $this->getDataGenerator();
        $egstudent = $generator->create_user(array('username' => 'egstudent'));
        $u2 = $generator->create_user(array('username' => 'u2'));

        $course = $generator->create_course(array('shortname' => 'X943-12K'));
        $coursecontext = \context_course::instance($course->id);

        $manualenrol = enrol_get_plugin('manual');
        $manualenrol->add_default_instance($course);
        $instance1 = $DB->get_record('enrol', array('enrol' => 'manual', 'courseid' => $course->id));
        $manualenrol->enrol_user($instance1, $egstudent->id, $roleids['student']);

        // Create a new workflow object.
        $workflow = new block_workflow_workflow();

        // Create a new workflow.
        $data = new stdClass();
        $data->shortname            = 'courseworkflow';
        $data->name                 = 'First course workflow';
        $data->appliesto            = 'course';
        $data->obsolete             = 0;
        $data->description          = 'This is a test workflow applying to a course for the unit test';
        $data->descriptionformat    = FORMAT_HTML;

        // Create_workflow will return a completed workflow object.
        $workflow->create_workflow($data);

        // When creating a workflow, the initial step will have automatically been created.
        // Retrieve the list of steps.
        $steps  = $workflow->steps();
        $step = array_pop($steps);
        $step1 = new block_workflow_step($step->id);

        // Update the first step to have some scripts.
        $step->onactivescript = 'assignrole teacher to student';
        $step1->update_step($step);

        // Create a new step in the workflow.
        $nsdata = new stdClass();
        $nsdata->workflowid         = $workflow->id;
        $nsdata->name               = 'Second step';
        $nsdata->instructions       = 'New instructions';
        $nsdata->instructionsformat = FORMAT_HTML;
        $nsdata->onactivescript     = 'setcoursevisibility visible';
        $nsdata->oncompletescript   = 'setcoursevisibility hidden';

        $newstep = new block_workflow_step();
        $step2 = $newstep->create_step($nsdata);

        // Add the workflow to our course (returns the block_workflow_step_state).
        $this->setUser($egstudent);
        $state = $workflow->add_to_context($coursecontext->id);

        // Check the right step is active.
        $this->assertEquals($step1->id, $state->stepid);
        $this->assertEquals(BLOCK_WORKFLOW_STATE_ACTIVE, $state->state);

        // Verify that student is now a teacher - i.e. that step 1's onactivescript ran.
        $this->assertTrue($DB->record_exists('role_assignments', array(
                    'userid' => $egstudent->id,
                    'roleid' => $roleids['teacher'],
                    'contextid' => $coursecontext->id,
                    'component' => 'block_workflow',
                )));

        // Hide the course, so we can test if the onactivescript runs.
        $DB->set_field('course', 'visible', '0', array('id' => $course->id));

        // Finish the first step.
        $state2 = $state->finish_step('Comment on task 1', FORMAT_HTML);

        // Verify step 1 is complete.
        $this->assertEquals($step1->id, $state->stepid);
        $this->assertEquals(BLOCK_WORKFLOW_STATE_COMPLETED, $state->state);

        // Verify step 2 is active.
        $this->assertEquals($step2->id, $state2->stepid);
        $this->assertEquals(BLOCK_WORKFLOW_STATE_ACTIVE, $state2->state);

        // Verify the role assignment from task 1 was removed.
        $this->assertFalse($DB->record_exists('role_assignments', array(
                    'userid' => $egstudent->id,
                    'roleid' => $roleids['teacher'],
                    'contextid' => $coursecontext->id,
                    'component' => 'block_workflow',
                )));

        // Verify the start script from task 2 ran.
        $this->assertTrue((bool) $DB->get_field('course', 'visible', array('id' => $course->id)));
        // The next line is redundant unless the previous assert failed, which
        // was the case at one time.
        $DB->set_field('course', 'visible', 1, array('id' => $course->id));

        // Jump back to step 1.
        $state1again = $state2->jump_to_step(null, $step1->id);

        // Verify step 2 is active.
        $this->assertEquals($step2->id, $state2->stepid);
        $this->assertEquals(BLOCK_WORKFLOW_STATE_ABORTED, $state2->state);

        // Check the right step is active.
        $this->assertEquals($step->id, $state1again->stepid);
        $this->assertEquals(BLOCK_WORKFLOW_STATE_ACTIVE, $state1again->state);

        // Verify that student is now a teacher - i.e. that step 1's onactivescript ran.
        $this->assertTrue($DB->record_exists('role_assignments', array(
                    'userid' => $egstudent->id,
                    'roleid' => $roleids['teacher'],
                    'contextid' => $coursecontext->id,
                    'component' => 'block_workflow',
                )));

        // Verify that step 2's oncomplete script did not run.
        $this->assertTrue((bool) $DB->get_field('course', 'visible', array('id' => $course->id)));

        // Now hide the course so we can tell if the onactive scripts runs in a minute.
        $DB->set_field('course', 'visible', 0, array('id' => $course->id));

        // Finish both steps, and hence the workflow.
        $state2again = $state1again->finish_step('Updated comment on task 1', FORMAT_HTML);

        // Verify that step 2's onactive script ran.
        $this->assertTrue((bool) $DB->get_field('course', 'visible', array('id' => $course->id)));

        $results = $state2again->finish_step('Comment on task 2', FORMAT_HTML);

        // Check no active step was returned.
        $this->assertSame(false, $results);

        // Check that step 2's oncomplete script ran.
        $this->assertFalse((bool) $DB->get_field('course', 'visible', array('id' => $course->id)));

        // Start the workflow yet again.
        $state = $workflow->add_to_context($coursecontext->id);

        // Verify that student is now a teacher - i.e. that step 1's onactivescript ran.
        $this->assertTrue($DB->record_exists('role_assignments', array(
                    'userid' => $egstudent->id,
                    'roleid' => $roleids['teacher'],
                    'contextid' => $coursecontext->id,
                    'component' => 'block_workflow',
                )));
    }
}
