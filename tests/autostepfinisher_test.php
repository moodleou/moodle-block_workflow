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
 * Workflow block test for autofinish fuctionality.
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
require_once($CFG->dirroot . '/blocks/workflow/cronlib.php');
require_once(dirname(__FILE__) . '/../locallib.php');

class auto_finish_steps_test extends block_workflow_automatic_step_finisher {

    public function get_all_active_steps() {
        return parent::get_all_active_steps();
    }

    public function get_ready_autofinish_steps($activesteps) {
        return parent::get_ready_autofinish_steps($activesteps);
    }

    public function is_ready_for_autofinish($activestep, $now) {
        return parent::is_ready_for_autofinish($activestep, $now);
    }

    public function finish_steps_automatically($readyautofinishsteps) {
        return parent::finish_steps_automatically($readyautofinishsteps);
    }
    public function get_relevant_date_field($courseshortname, $field) {
        return parent::get_relevant_date_field($courseshortname, $field);
    }

}

class block_workflow_automatic_step_finisher_test extends advanced_testcase {
    protected $stepfinisher;

    protected function setUp() {
        $this->stepfinisher = new auto_finish_steps_test();
    }

    protected function tearDown() {
        $this->stepfinisher = null;
    }

    protected function create_version_pres_tables() {
        global $DB;
        // Set that we have dataload tables.
        set_config('hasdataloadtables', 1);

        // Check nobody's trying to test on a 'real' database.
        if ($DB->record_exists_sql("SELECT 1 FROM information_schema.tables " .
                "WHERE table_name='vl_c_crs_version_pres_a'")) {
            throw new Exception('You cannot run phpunit tests on a database that ' .
                    'contains vl_c_crs_version_pres_a table; automated and manual ' .
                    'testing might need to be on different databases');
        }

        // Create the table if it doesn't exist. NOTE we are not using Moodle
        // database manager because the table (actually it's normally a view)
        // is not prefixed.
        if (!$DB->record_exists_sql("SELECT 1 FROM information_schema.tables " .
                "WHERE table_name='vl_v_crs_version_pres'")) {

            $createsql = "
                CREATE TABLE vl_v_crs_version_pres
                (
                    course_code character varying(7) NOT NULL DEFAULT ' '::character varying,
                    course_version_num character(2) NOT NULL DEFAULT ' '::bpchar,
                    pres_code character(3) NOT NULL DEFAULT ' '::bpchar,
                    pres_code_5 character(5) NOT NULL DEFAULT ' '::bpchar,
                    vle_course_short_name character varying(15) NOT NULL DEFAULT ' '::character varying,
                    vle_control_course character(1) NOT NULL DEFAULT ' '::bpchar,
                    vle_link_creation_date date,
                    vle_student_open_date date,
                    vle_student_close_date date,
                    vle_tutor_open_date date,
                    vle_tutor_close_date date,
                    e_tmas_permitted character(1) NOT NULL DEFAULT ' '::bpchar,
                    assmnt_strategy_cnfltn_desc character varying(200) NOT NULL DEFAULT ' '::character varying,
                    assmnt_strategy_oca_desc character varying(500) NOT NULL DEFAULT ' '::character varying,
                    assmnt_strategy_substn_desc character varying(200) NOT NULL DEFAULT ' '::character varying,
                    assmnt_strategy_oes_desc character varying(500) NOT NULL DEFAULT ' '::character varying,
                    assmnt_strategy_threshold_desc character varying(600) NOT NULL DEFAULT ' '::character varying,
                    pres_start_date timestamp without time zone,
                    pres_finish_date timestamp without time zone,
                    vle_course_page_in_stud_home character(1) NOT NULL DEFAULT ' '::bpchar,
                    full_course_title character varying(70) NOT NULL DEFAULT ' '::character varying
                )";
            $DB->execute($createsql);
        }

        // Clear the table.
        $DB->execute("TRUNCATE vl_v_crs_version_pres");
    }

    private function get_days($days, $beforeafter = 'after') {
        if ($beforeafter === 'before') {
            return -($days * 24 * 60 * 60);
        }
        return ($days * 24 * 60 * 60);
    }

    /**
     * Creates a workflow with one step and returns the workflow object and the step object.
     * @param int $autofinishoffset
     * @param string $autofinish
     * @param string $appliesto
     */
    private function create_a_workflow_with_one_step($autofinishoffset,
                            $autofinish = 'course;startdate', $appliesto = 'course') {
        // Create a new workflow object.
        $workflow = new block_workflow_workflow();
        $data = new stdClass();
        $data->shortname            = $appliesto . 'workflow';
        $data->name                 = 'First ' .  $appliesto . ' workflow';
        $data->appliesto            = $appliesto;
        $data->obsolete             = 0;
        $data->description          = 'This is a test workflow applying to a ' . $appliesto . ' for the unit test';
        $data->descriptionformat    = FORMAT_HTML;
        $workflow->create_workflow($data);

        // When creating a workflow, the initial step will have automatically been created.
        // Retrieve the list of steps.
        $steps = $workflow->steps();
        $step1 = array_pop($steps);
        $step1->autofinish = $autofinish;
        $step1->autofinishoffset = $autofinishoffset;

        // Update current step.
        $newstep = new block_workflow_step($step1->id);
        $newstep->update_step($step1);
        return array($workflow, $step1);
    }

    /**
     * Create expected object for one active step and returns an array which contains the expected object.
     * @param object $state
     * @param object $step
     * @param object $workflow
     * @param object $course
     * @param object $module
     */
    private function get_expected_active_step($state, $step, $appliesto, $course, $cmid = 0) {
        return array($state->id => (object)array(
                        'stateid' => $state->id,
                        'stepid' => $step->id,
                        'workflowid' => $step->workflowid,
                        'appliesto' => $appliesto,
                        'stepname' => $step->name,
                        'autofinish' => $step->autofinish,
                        'autofinishoffset' => $step->autofinishoffset,
                        'courseid' => ($course ? $course->id : null),
                        'courseshortname' => ($course ? $course->shortname : null),
                        'moduleid' => $cmid)
                    );
    }

    public function test_automatic_step_finisher() {
        global $CFG, $DB;
        $now = time();
        $after5days = $this->get_days(5);
        $before5days = $this->get_days(5, 'before');
        $timestamp1 = $now - $after5days - 60; // Subtract one minute.
        $timestamp2 = $now - $before5days - 60; // Subtract one minute.

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        // Create a course object.
        $course1 = $generator->create_course(array('shortname' => 'M123-12J', 'startdate' => $timestamp2));
        $coursecontext1 = context_course::instance($course1->id);

        // Create a quiz object.
        $quiz1 = $generator->create_module('quiz', array('course' => $course1->id, 'timeopen' => $timestamp1));
        $quizcontext1 = context_module::instance($quiz1->cmid);

        // Create a externalquiz object.
        $externalquiz1 = $generator->create_module('externalquiz', array('course' => $course1->id, 'timeopen' => $timestamp1));
        $externalquizcontext1 = context_module::instance($externalquiz1->cmid);

        // Create another course object.
        $course2 = $generator->create_course(array('shortname' => 'K123-12J', 'startdate' => $timestamp1));
        $coursecontext2 = context_course::instance($course2->id);

        // Generate a vl_v_crs_version_pres table.
        $this->create_version_pres_tables();

        // Insert data to the above table
        $courseshortname = 'M123-12J';
        $studentopendate = '2013-04-11';
         $DB->execute("INSERT INTO vl_v_crs_version_pres (vle_course_short_name, vle_student_open_date) " .
                 "VALUES ('$courseshortname', '$studentopendate')");

        $courseondataloadtable = $DB->get_record_sql(
                'SELECT * FROM vl_v_crs_version_pres ' .
                'WHERE vle_course_short_name = ?', array($courseshortname), MUST_EXIST);

        // Create a new workflow object which applies to course.
        list($courseworkflow, $step1) = $this->create_a_workflow_with_one_step($after5days);

        // Required DB tables are not populated and therefore following methods return empty arrays.
        $activesteps = $this->stepfinisher->get_all_active_steps();
        $readysteps = $this->stepfinisher->get_ready_autofinish_steps($activesteps);

        $this->assertEmpty($activesteps);
        $this->assertEmpty($readysteps);

        // Add to context and check the step is active.
        $state1 = $courseworkflow->add_to_context($coursecontext1->id);
        $this->assertEquals($step1->id, $state1->stepid);
        $this->assertEquals(BLOCK_WORKFLOW_STATE_ACTIVE, $state1->state);

        // Get all active steps and test if we get them all.
        $activesteps = $this->stepfinisher->get_all_active_steps();
        $this->assertEquals(1, count($activesteps));

        // Get all active steps. We have populated.
        $readysteps = $this->stepfinisher->get_ready_autofinish_steps($activesteps);
        $this->assertNotEmpty($activesteps);

        $course1->vle_student_open_date = strtotime($courseondataloadtable->vle_student_open_date);

        // Create expected objects for active steps and test them against the actual objects.
        $expectedactivesteps = $this->get_expected_active_step($state1, $step1, 'course', $course1, '');
        $this->assertEquals($expectedactivesteps, $activesteps);

        // Add to context and check the step is active.
        $state2 = $courseworkflow->add_to_context($coursecontext2->id);
        $this->assertEquals($step1->id, $state1->stepid);
        $this->assertEquals(BLOCK_WORKFLOW_STATE_ACTIVE, $state1->state);

        // Get all active steps and test if we get them all.
        $activesteps = $this->stepfinisher->get_all_active_steps();
        $this->assertEquals(2, count($activesteps));

        // Create expected objects for active steps and test them against the actual objects.
        $expectedactivesteps += $this->get_expected_active_step($state2, $step1, 'course', $course2, '');
        $this->assertEquals($expectedactivesteps, $activesteps);

        // Create a new workflow object which applies to quiz.216
        list($quizworkflow, $step1q) = $this->create_a_workflow_with_one_step($before5days, 'quiz;timeopen', 'quiz');

        // Add to context and check if the step is active.
        $state1q = $quizworkflow->add_to_context($quizcontext1->id);
        $this->assertEquals($step1q->id, $state1q->stepid);
        $this->assertEquals(BLOCK_WORKFLOW_STATE_ACTIVE, $state1q->state);

        // Create expected objects for active steps and test them against the actual objects.
        $expectedactivesteps += $this->get_expected_active_step($state1q, $step1q, 'quiz', null, $quiz1->id);
        $activesteps = $this->stepfinisher->get_all_active_steps();
        $this->assertEquals(3, count($activesteps));
        $this->assertEquals($expectedactivesteps, $activesteps);

        // Create a new workflow object which applies to externalquiz.
        list($externalquizworkflow, $step1eq) = $this->create_a_workflow_with_one_step($before5days, 'externalquiz;timeopen', 'externalquiz');

        // Add to context and check if the step is active.
        $state1eq = $externalquizworkflow->add_to_context($externalquizcontext1->id);
        $this->assertEquals($step1eq->id, $state1eq->stepid);
        $this->assertEquals(BLOCK_WORKFLOW_STATE_ACTIVE, $state1eq->state);

        // Create expected objects for active steps and test them against the actual objects.
        $expectedactivesteps += $this->get_expected_active_step($state1eq, $step1eq, 'externalquiz', null, $externalquiz1->id);
        $activesteps = $this->stepfinisher->get_all_active_steps();
        $this->assertEquals(4, count($activesteps));
        $this->assertEquals(count($expectedactivesteps), count($activesteps));
        $this->assertEquals($expectedactivesteps, $activesteps);

        // Check relevant fields in 'block_workflow_step_states' table before finishing automatically.
        $statebeforefinish = $DB->get_record('block_workflow_step_states', array('stepid' => $state1q->stepid));
        $this->assertEquals(BLOCK_WORKFLOW_STATE_ACTIVE, $statebeforefinish->state);
        $this->assertEmpty($statebeforefinish->comment);

        // Get ready active steps and finish them automatically.
        $readysteps = $this->stepfinisher->get_ready_autofinish_steps($activesteps);
        $finishedsteps = $this->stepfinisher->finish_steps_automatically($readysteps);

        // Check relevant fields in 'block_workflow_step_states' table after finishing automatically.
        $stateafterfinish = $DB->get_record('block_workflow_step_states', array('stepid' => $state1q->stepid));
        $this->assertEquals(BLOCK_WORKFLOW_STATE_COMPLETED, $stateafterfinish->state);
        $this->assertNotEmpty($stateafterfinish->comment);

        // Check that we have finished the ready active steps and therefore we have
        // reduced the number of all active steps.
        $newactivesteps = $this->stepfinisher->get_all_active_steps();
        $this->assertEquals(1, count($newactivesteps));
        $readysteps = $this->stepfinisher->get_ready_autofinish_steps($newactivesteps);
        $this->assertEmpty($readysteps);

        // Check that we get the relevant date field.
        // Check that we convert the date format to timestamp correctly.
        $datafield = $this->stepfinisher->get_relevant_date_field($course1->shortname, 'vle_student_open_date');
        $this->assertEquals(strtotime('2013-04-11'), $datafield);
    }
}
