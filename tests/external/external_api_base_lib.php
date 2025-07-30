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
use externallib_advanced_testcase;
use block_workflow_workflow;
use block_workflow_step;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * Mocking data and common function for all the web service tests.
 *
 * @package block_workflow
 * @copyright 2023 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external_api_base_lib extends externallib_advanced_testcase {

    /**
     * @var int|null ID of the first workflow state, or null if not set.
     */
    protected $state1id = null;
    /**
     * @var mixed|null Step 1 variable, initialized as null.
     */
    protected $step1 = null;
    /**
     * @var mixed|null Stores the second step in the workflow, or null if not set.
     */
    protected $step2 = null;
    /**
     * Example student user object for testing purposes.
     *
     * @var mixed|null
     */
    protected $egstudent = null;
    /**
     * @var stdClass|null The course object associated with the test, or null if not set.
     */
    protected $course = null;
    /**
     * @var int|null The ID of the student role, or null if not set.
     */
    protected $studenroleid = null;

    #[\Override]
    public function setUp(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        global $DB;

        $generator = $this->getDataGenerator();
        $this->course = $generator->create_course(['shortname' => 'X943-12K']);
        $coursecontext = \context_course::instance($this->course->id);

        $this->egstudent = $generator->create_user(['username' => 'egstudent']);

        $roleids = $DB->get_records_menu('role', null, '', 'shortname,id');
        $this->studenroleid = $roleids['student'];

        $manualenrol = enrol_get_plugin('manual');
        $manualenrol->add_default_instance($this->course);
        $instance1 = $DB->get_record('enrol', ['enrol' => 'manual', 'courseid' => $this->course->id]);
        $manualenrol->enrol_user($instance1, $this->egstudent->id, $roleids['student']);
        // Create a new workflow object.
        $workflow = new block_workflow_workflow();

        // Create a new workflow.
        $data = new \stdClass();
        $data->shortname = 'courseworkflow';
        $data->name = 'First course workflow';
        $data->appliesto = 'course';
        $data->obsolete = 0;
        $data->description = 'This is a test workflow applying to a course for the unit test';
        $data->descriptionformat = FORMAT_HTML;
        // Create_workflow will return a completed workflow object.
        $workflow->create_workflow($data);

        // When creating a workflow, the initial step will have automatically been created.
        // Retrieve the list of steps.
        $steps = $workflow->steps();
        $step = array_pop($steps);
        $this->step1 = new block_workflow_step($step->id);
        // Update the first step to have some scripts.
        $step->onactivescript = 'assignrole teacher to student';
        $this->step1->update_step($step);

        // Create a new step in the workflow.
        $nsdata = new \stdClass();
        $nsdata->workflowid = $workflow->id;
        $nsdata->name = 'Second step';
        $nsdata->instructions = 'New instructions';
        $nsdata->instructionsformat = FORMAT_HTML;
        $nsdata->onactivescript = 'setcoursevisibility visible';
        $nsdata->oncompletescript = 'setcoursevisibility hidden';

        $newstep = new block_workflow_step();
        $this->step2 = $newstep->create_step($nsdata);
        $state = $workflow->add_to_context($coursecontext->id);
        $state->update_comment('<p>Sample Comment</p>', FORMAT_HTML);
        $this->state1id = $state->id;
    }
}
