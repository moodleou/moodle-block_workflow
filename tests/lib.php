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
 * Workflow block test helper code.
 *
 * @package   block_workflow
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group block_workflow
 */

defined('MOODLE_INTERNAL') || die();

// Make sure the code being tested is accessible.
require_once($CFG->dirroot . '/blocks/workflow/locallib.php');


class block_workflow_testing_context_hack extends context_system {
    public static function clear_context_caches($testdb) {

        // We need to reset the contexcache.
        context_helper::reset_caches();

        // And unset the systemcontext stored in it.
        $record = new stdClass();
        $record->contextlevel = CONTEXT_SYSTEM;
        $record->instanceid   = 0;
        $record->depth        = 1;
        $record->path         = null; // Not known before insert.
        $record->id = $testdb->insert_record('context', $record);
        $record->path         = '/' . $record->id;
        $testdb->update_record('context', $record);

        context::$systemcontext = new context_system($record);
    }
}

abstract class block_workflow_testlib extends advanced_testcase {

    // Add code coverage for the libraries.
    public static $includecoverage = array('blocks/workflow/locallib.php');
    // The test course's ID, and contextid for future reference.
    protected $courseid;
    protected $contextid;
    protected $roles;
    protected $users;

    /**
     * Set up the database and basic data for testing
     *
     * This will also call {@link parent::setUp}
     */
    public function setUp() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        parent::setUp();

        $generator->create_category();
        // Create a default course category.
        $cat = new stdClass();
        $cat->name          = 'Test cat';
        $cat->parent        = 0;
        $cat->depth         = 1;
        $cat->coursecount   = 1;
        $cat->id            = $DB->insert_record('course_categories', $cat);
        $cat->path          = '/' . $cat->id;
        $DB->update_record('course_categories', $cat);

        // Create a default course.
        $generator->create_course();
        $course = new stdClass();
        $course->category   = $cat->id;
        $course->fullname   = 'Testing workflow course';
        $course->shortname  = 'TEST';
        $course->summary    = 'Test course used to test workflows';
        $course->id         = $DB->insert_record('course', $course);

        // Make a note of this as we'll probably use it a bit later.
        $this->courseid     = $course->id;

        // Create default group.
        $group = new stdClass();
        $group->courseid    = $course->id;
        $group->name        = 'TEST';
        $group->id          = $DB->insert_record('groups', $group);
        $generator->create_group($group);

        // Create the context for the course.
        $context = context_course::instance($course->id);
        $this->contextid = $context->id;

        // Create some roles.
        $roles = array(
            'manager'           => 'manager',
            'coursecreator'     => 'coursecreator',
            'teacher'           => 'teacher',
            'editingteacher'    => 'editingteacher',
            'student'           => 'student',
        );
        foreach ($roles as $shortname => $archetype) {
            $role = $DB->get_record('role', array('shortname' => $shortname));
            $this->roles[$shortname] = $role->id;
        }

        // Create some users.
        $users = array(
            'egmanager'         => 'manager',
            'egteacher'         => 'teacher',
            'egeditingteacher'  => 'editingteacher',
            'egcoursecreator'   => 'coursecreator',
            'egstudent'         => 'student',
        );
        foreach ($users as $username => $role) {
            $user = new StdClass;
            $user->username     = $username;
            $user->firstname    = $username;
            $user->lastname     = $username;
            $user->id = $DB->insert_record('user', $user);
            $assignment = new stdClass();
            $assignment->roleid = $this->roles[$role];
            $assignment->userid = $user->id;
            $assignment->contextid = $this->contextid;
            $DB->insert_record('role_assignments', $assignment);
            $this->users[$username] = $user->id;
        }

        $generator->get_plugin_generator('mod_quiz')->create_instance(['course' => $course->id]);
        $generator->get_plugin_generator('mod_chat')->create_instance(['course' => $course->id]);
    }

    /**
     * Compare a step with a stdClass object
     *
     * This will ignore the id and stepno which should be tested seperately
     *
     * @param   stdClass $source The stdClass to compare with. All fields present will be tested
     * @param   object   $step   The object to compare with using assertEquals
     * @param   array    $ignore    Which fields to ignore
     */
    protected function compare_step($source, $with, $ignore = array('id', 'stepno')) {
        // If we're given a step, then grab it's expected settings.
        if (is_a($source, 'block_workflow_step')) {
            $fields = $source->expected_settings();
        } else {
            $fields = array_keys((array) $source);
        }

        $this->compare_object($source, $with, $fields, $ignore);
    }

    /**
     * Compare a workflow with a stdClass object
     *
     * This will ignore the id and shortname which should be tested seperately
     *
     * @param   stdClass $source    The stdClass to compare with. All fields present will be tested
     * @param   object   $workflow  The object to compare with using assertEqualss
     * @param   array    $ignore    Which fields to ignore
     */
    protected function compare_workflow($source, $with, $ignore = array('id', 'shortname')) {
        // If we're given a workflow, then grab it's expected settings.
        if (is_a($source, 'block_workflow_workflow')) {
            $fields = $source->expected_settings();
        } else {
            $fields = array_keys((array) $source);
        }

        $this->compare_object($source, $with, $fields, $ignore);
    }

    /**
     * Compare an email with a stdClass object
     *
     * This will ignore the id which should be tested seperately
     *
     * @param   stdClass $source    The stdClass to compare with. All fields present will be tested
     * @param   object   $todo      The object to compare with using assertEqualss
     * @param   array    $ignore    Which fields to ignore
     */
    protected function compare_email($source, $with, $ignore = array('id', 'shortname')) {
        // If we're given a todo, then grab it's expected settings.
        if (is_a($source, 'block_workflow_email')) {
            $fields = $source->expected_settings();
        } else {
            $fields = array_keys((array) $source);
        }

        $this->compare_object($source, $with, $fields, $ignore);
    }

    /**
     * Compare a todo with a stdClass object
     *
     * This will ignore the id which should be tested seperately
     *
     * @param   stdClass $source    The stdClass to compare with. All fields present will be tested
     * @param   object   $todo      The object to compare with using assertEqualss
     * @param   array    $ignore    Which fields to ignore
     */
    protected function compare_todo($source, $with, $ignore = array('id', 'obsolete')) {
        // If we're given a todo, then grab it's expected settings.
        if (is_a($source, 'block_workflow_todo')) {
            $fields = $source->expected_settings();
        } else {
            $fields = array_keys((array) $source);
        }

        $this->compare_object($source, $with, $fields, $ignore);
    }

    protected function compare_object($source, $target, $fields, $ignore) {
        // Check each field.
        foreach ($fields as $name) {
            // Only process fields which aren't in the ignore array.
            if (!in_array($name, $ignore)) {
                $this->assertEquals($source->$name, $target->$name);
            }
        }
    }

    /**
     * Helper function to expect and Exception without halting further
     * tests
     *
     * @param   string  $et      The name of the exception class that we're expecting
     * @param   mixed   $class   The object that we're calling $command on,
     *                              or the name of the class to call in a static context
     * @param   string  $command The function to run on $object
     * @param   mixed   $args    Any arguments to pass to the function
     */
    protected function expect_exception_without_halting($et, $class = null, $command) {
        $args = func_get_args();
        array_shift($args);
        array_shift($args);
        array_shift($args);

        try {
            if ($class) {
                $func = array($class, $command);
            } else {
                $func = $command;
            }
            // Attempt to run $command on $class, passing it $args.
            call_user_func_array($func, $args);

            // This should have generated an exception.
            $this->fail('Expected ' . $et . ' exception');
        } catch (Exception $e) {
            $this->assertInstanceOf($et, $e);
        }
    }

    protected function create_workflow($createstep = true) {
        // Create a new workflow.
        $data = new stdClass();
        $data->shortname            = 'courseworkflow';
        $data->name                 = 'First Course Workflow';
        $data->description          = 'This is a test workflow applying to a course for the unit test';

        // Create a new workflow object.
        $workflow = new block_workflow_workflow();

        // The method create_workflow will return a completed workflow object.
        $workflow->create_workflow($data, $createstep);
        return $workflow;
    }

    protected function create_step($workflow) {
        // Create a new step.
        $step = new block_workflow_step();
        $data = new stdClass();
        $data->workflowid = $workflow->id;
        $data->name = 'STEP_ONE';
        $data->instructions = '';
        $step->create_step($data);
        return $step;
    }

    protected function create_email($shortname = 'TESTMAIL') {
        // Create a new todo.
        $email  = new block_workflow_email();
        $data   = new stdClass();
        $data->shortname   = $shortname;
        $data->message     = 'Example e-mail';
        $data->subject     = 'Example subject';
        $email->create($data);
        return $email;
    }

    protected function create_todo($step) {
        // Create a new todo.
        $todo = new block_workflow_todo();
        $data = new stdClass();
        $data->stepid   = $step->id;
        $data->task     = 'TASK ONE';
        $todo->create_todo($data);
        return $todo;
    }

    protected function assign_workflow($workflow) {
        global $DB;

        if ($workflow->appliesto == 'course') {
            // We can add the context to the course immediately.
            return $workflow->add_to_context($this->contextid);
        }

        // We've pre-created a module entry, and course module for each module.
        $module = $workflow->appliesto;
        $sql = "SELECT c.id
                FROM {" . $module . "} m
                INNER JOIN {course_modules} cm ON cm.instance = m.id
                INNER JOIN {context} c ON c.instanceid = cm.id
                INNER JOIN {modules} md ON md.id = cm.module
                WHERE md.name = ? AND cm.course = ? LIMIT 1";
        $instance = $DB->get_record_sql($sql, array($module, $this->courseid));

        // Create the activity for this type of workflow.
        return $workflow->add_to_context($instance->id);
    }

    protected function create_activity_workflow($appliesto, $createstep = true) {
        // Create a new workflow.
        $data = new stdClass();
        $data->shortname            = 'activityworkflow';
        $data->name                 = 'First Course Workflow';
        $data->description          = 'This is a test workflow applying to a course for the unit test';
        $data->appliesto            = $appliesto;

        // Create a new workflow object.
        $workflow = new block_workflow_workflow();

        // The method create_workflow will return a completed workflow object.
        $workflow->create_workflow($data, $createstep);
        return $workflow;
    }

    /**
     * Create vl_v_crs_version_pres table
     * @throws Exception
     */
    protected function create_version_pres_tables() {
        global $DB;

        if ($DB->get_dbfamily() !== 'postgres') {
            $this->markTestSkipped('This test only works with a Postgres database.');
        }

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

    protected function get_days($days, $beforeafter = 'after') {
        if ($beforeafter === 'before') {
            return -($days * 24 * 60 * 60);
        }
        return ($days * 24 * 60 * 60);
    }

    /**
     * Creates a workflow with one step and returns the workflow object and the step object.
     * @param int $offset, autofinshoffset or extranotifyoffset
     * @param string $type, 'autofinish' or 'extranotify
     * @param string $typevalue, the value for autofinish or extranotify filed
     * @param string $appliesto
     */
    protected function create_a_workflow_with_one_step($stepoptions, $appliesto = 'course') {

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
        if ($stepoptions) {
            foreach ($stepoptions as $key => $value) {
                $step1->$key = $value;
            }
        }

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
    protected function create_expected_active_step($state, $step, $appliesto, $course, $cmid = 0, $type = 'autofinish') {
        if ($type == 'autofinish') {
            return array($state->id => (object)array(
                    'stateid' => $state->id,
                    'stepid' => $step->id,
                    'state' => BLOCK_WORKFLOW_STATE_ACTIVE,
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
        return array($state->id => (object)array(
                'stateid' => $state->id,
                'stepid' => $step->id,
                'state' => BLOCK_WORKFLOW_STATE_ACTIVE,
                'workflowid' => $step->workflowid,
                'appliesto' => $appliesto,
                'stepname' => $step->name,
                'extranotify' => $step->extranotify,
                'extranotifyoffset' => "$step->extranotifyoffset",
                'courseid' => ($course ? $course->id : null),
                'courseshortname' => ($course ? $course->shortname : null),
                'moduleid' => $cmid)
        );
    }
}
