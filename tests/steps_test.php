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
 * Workflow block test unit for step class.
 *
 * @package   block_workflow
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group block_workflow
 */

defined('MOODLE_INTERNAL') || die();

// Include our test library so that we can use the same mocking system for all tests.
global $CFG;
require_once(dirname(__FILE__) . '/lib.php');

class block_workflow_steps_test extends block_workflow_testlib {
    public function test_step_validation() {
        // Create a new workflow.
        $workflow = $this->create_workflow();

        // Create a new step.
        $step = new block_workflow_step();
        $data = new stdClass();

        // Validate Step Creation.
        // Missing workflowid.
        $this->expect_exception_without_halting('block_workflow_invalid_step_exception',
                $step, 'create_step', $data);
        $data->workflowid = $workflow->id;

        // Missing name.
        $this->expect_exception_without_halting('block_workflow_invalid_step_exception',
                $step, 'create_step', $data);
        // Empty name.
        $data->name = '';
        $this->expect_exception_without_halting('block_workflow_invalid_step_exception',
                $step, 'create_step', $data);
        $data->name = 'step';

        // Missing instructions.
        $this->expect_exception_without_halting('block_workflow_invalid_step_exception',
            $step, 'create_step', $data);
        $data->instructions = '';

        // Invalid workflowid.
        $data->workflowid = -1;
        $this->expect_exception_without_halting('block_workflow_invalid_workflow_exception',
                $step, 'create_step', $data);
        $data->workflowid = $workflow->id;

        // Invalid setting.
        $data->badfield = 'baddata';
        $this->expect_exception_without_halting('block_workflow_invalid_step_exception',
                $step, 'create_step', $data);
        unset($data->badfield);

        // A bad onactivescript.
        $data->onactivescript = 'baddata';
        $this->expect_exception_without_halting('block_workflow_invalid_command_exception',
                $step, 'create_step', $data);
        unset($data->onactivescript);

        // A bad oncompletescript.
        $data->oncompletescript = 'baddata';
        $this->expect_exception_without_halting('block_workflow_invalid_command_exception',
                $step, 'create_step', $data);
        unset($data->oncompletescript);
    }

    public function test_step_validation_2() {
        // Create a new workflow.
        $workflow = $this->create_workflow();

        // Create a new step.
        $step = new block_workflow_step();

        $data = new stdClass();
        $data->workflowid = $workflow->id;
        $data->name = 'step';
        $data->instructions = '';

        // Should now work.
        $step->create_step($data);

        // And the workflow now has two steps.
        $this->assertEquals(count($workflow->steps()), 2);

        // The workflow already had a step, this should be stepno two.
        $this->assertEquals($step->stepno, 2);

        // Now check that we can create a step before step one.
        $step->create_step($data, -1);
        $this->assertEquals($step->stepno, 1);

        // And the workflow now has three steps.
        $this->assertEquals(count($workflow->steps()), 3);

        // Now check that we can create a new step after step 1, and before step 2 (shifting step 2->3).
        $step->create_step($data, 1);
        // I.e. This is step 2.
        $this->assertEquals($step->stepno, 2);

        // And the workflow now has 4 steps.
        $this->assertEquals(count($workflow->steps()), 4);

        // Now check that we can create a new step before step 2, and after step 1.
        $step->create_step($data, -2);
        // So this is step 2 again.
        $this->assertEquals($step->stepno, 2);

        // And the workflow now has 5 steps.
        $this->assertEquals(count($workflow->steps()), 5);

        // Test atendgobacktostep is set correctly when deleting steps.
        // First set the atendgobacktostep to 5.
        $update = new stdClass();
        $update->atendgobacktostep = 5;
        $workflow->update($update);
        $this->assertEquals($workflow->atendgobacktostep, 5);

        // Remove step 1.
        $s1 = new block_workflow_step();
        $s1->load_workflow_stepno($workflow->id, 1);
        $s1->delete();

        // The field atendgobacktostep should now be 4.
        $workflow->load_workflow($workflow->id);
        $this->assertEquals($workflow->atendgobacktostep, 4);

        // Remove step 4.
        $s4 = new block_workflow_step();
        $s4->load_workflow_stepno($workflow->id, 4);
        $s4->delete();

        // The field atendgobacktostep should now be 3.
        $workflow->load_workflow($workflow->id);
        $this->assertEquals($workflow->atendgobacktostep, 3);

        // Validate step updates.
        $data = new stdClass();
        $step = new block_workflow_step();
        $step->load_workflow_stepno($workflow->id, 1);

        // A bad workflowid.
        $data->workflowid = -1;
        $this->expect_exception_without_halting('block_workflow_invalid_workflow_exception',
                $step, 'update_step', $data);
        unset($data->workflowid);

        // Invalid setting.
        $data->badfield = 'baddata';
        $this->expect_exception_without_halting('block_workflow_invalid_step_exception',
                $step, 'update_step', $data);
        unset($data->badfield);

        // A bad onactivescript.
        $data->onactivescript = 'baddata';
        $this->expect_exception_without_halting('block_workflow_invalid_command_exception',
                $step, 'update_step', $data);
        unset($data->onactivescript);

        // A bad oncompletescript.
        $data->oncompletescript = 'baddata';
        $this->expect_exception_without_halting('block_workflow_invalid_command_exception',
                $step, 'update_step', $data);
        unset($data->oncompletescript);
    }

    public function test_step_validation_3() {
        // Create a new workflow.
        $workflow = $this->create_workflow();

        // Create a new step.
        $step = new block_workflow_step();

        $data = new stdClass();
        $data->workflowid = $workflow->id;
        $data->name = 'step';
        $data->instructions = '';

        // Should now work.
        $step->create_step($data);

        // And the workflow now has two steps.
        $this->assertEquals(count($workflow->steps()), 2);

        // The workflow already had a step, this should be stepno two.
        $this->assertEquals($step->stepno, 2);

        // Now check that we can create a step before step one.
        $step->create_step($data, -1);
        $this->assertEquals($step->stepno, 1);

        // And the workflow now has three steps.
        $this->assertEquals(count($workflow->steps()), 3);

        // Now check that we can create a new step after step 1, and before step 2 (shifting step 2->3).
        $step->create_step($data, 1);
        // I.e. This is step 2.
        $this->assertEquals($step->stepno, 2);

        // And the workflow now has 4 steps.
        $this->assertEquals(count($workflow->steps()), 4);

        // Now check that we can create a new step before step 2, and after step 1.
        $step->create_step($data, -2);
        // So this is step 2 again.
        $this->assertEquals($step->stepno, 2);

        // And the workflow now has 5 steps.
        $this->assertEquals(count($workflow->steps()), 5);

        // Test atendgobacktostep is set correctly when deleting steps.
        // First set the atendgobacktostep to 5.
        $update = new stdClass();
        $update->atendgobacktostep = 5;
        $workflow->update($update);
        $this->assertEquals($workflow->atendgobacktostep, 5);

        // Remove step 1.
        $s1 = new block_workflow_step();
        $s1->load_workflow_stepno($workflow->id, 1);
        $s1->delete();

        // The field atendgobacktostep should now be 4.
        $workflow->load_workflow($workflow->id);
        $this->assertEquals($workflow->atendgobacktostep, 4);

        // Remove step 4.
        $s4 = new block_workflow_step();
        $s4->load_workflow_stepno($workflow->id, 4);
        $s4->delete();

        // The field atendgobacktostep should now be 3.
        $workflow->load_workflow($workflow->id);
        $this->assertEquals($workflow->atendgobacktostep, 3);

        // Validate step updates.
        $data = new stdClass();
        $step = new block_workflow_step();
        $step->load_workflow_stepno($workflow->id, 1);

        // And a valid update.
        $data->name = 'Hello';
        $step->update_step($data);
        // Assert that the update did happen.
        $this->assertEquals($data->name, $step->name);
    }

    /**
     * Test toggling of block_workflow_step roles
     * - Ensure that we can add a role
     * - Ensure that we get the right result from step->roles()
     * - Ensure that we can add a second role
     * - Ensure that we get the right result from step->roles()
     * - Ensure that we can remove the first role
     * - Ensure that we get the right result from step->roles()
     * - Ensure that we can remove the second role
     * - Ensure that we get the right result from step->roles()
     */
    public function test_step_roles() {
        global $DB;
        // Create a new workflow.
        $data = new stdClass();
        $data->shortname            = 'courseworkflow';
        $data->name                 = 'First Course Workflow';
        $data->appliesto            = 'course';
        $data->obsolete             = 0;
        $data->description          = 'This is a test workflow applying to a course for the unit test';
        $data->descriptionformat    = FORMAT_HTML;

        // Create a new workflow object.
        $workflow = new block_workflow_workflow();

        // The method create_workflow will return a completed workflow object.
        $workflow->create_workflow($data);

        // Retrieve the first step.
        $step = new block_workflow_step();
        $step->load_workflow_stepno($workflow->id, 1);

        // Find the ID for the manager role.
        $managerid = $DB->get_field('role', 'id', array('shortname' => 'manager'));

        // Enable the role.
        $return = $step->toggle_role($managerid);
        $this->assertEquals('integer', gettype($return));

        // Retrieve a list of all of the roles.
        $return = $step->roles();
        $this->assertEquals('array', gettype($return));

        // We've only added one role.
        $this->assertEquals(count($return), 1);

        // Grab the first (and only entry) and check that it's roleid
        // matches the manager role's id.
        $thisrole = array_shift($return);
        $this->assertEquals($thisrole->id, $managerid);

        // Add a second role.
        $teacherid = $DB->get_field('role', 'id', array('shortname' => 'teacher'));

        // Enable the second role.
        $return = $step->toggle_role($teacherid);
        $this->assertEquals('integer', gettype($return));

        // Retrieve a list of all of the roles again.
        $return = $step->roles();
        $this->assertEquals('array', gettype($return));

        // There should now be two roles listed.
        $this->assertEquals(count($return), 2);

        // Disable the manager again.
        $return = $step->toggle_role($managerid);
        $this->assertTrue($return);

        // Retrieve a list of all of the roles again.
        $return = $step->roles();
        $this->assertEquals('array', gettype($return));

        // There should now be only one role listed.
        $this->assertEquals(count($return), 1);

        // Grab the first (and only entry) and check that it's now the role
        // matches the teacher role's id.
        $thisrole = array_shift($return);
        $this->assertEquals($thisrole->id, $teacherid);

        // Finally remove the teacher role.
        $return = $step->toggle_role($teacherid);
        $this->assertTrue((bool)$return);

        // Retrieve a list of all of the roles again.
        $return = $step->roles();
        $this->assertEquals('array', gettype($return));

        // There should now be no roles listed.
        $this->assertEquals(count($return), 0);
    }

    public function test_script_parse() {
        // Create a new workflow and step.
        $workflow = $this->create_workflow(false);
        $step     = $this->create_step($workflow);

        // Giving a two-line script (with a comment) should give us one
        // command and no errors.
        $script = "# This is a comment\nassignrole editingteacher to teacher";

        $command = $step->parse_script($script);

        // There should be one command.
        $this->assertEquals(count($command->commands), 1);

        // And no errors.
        $this->assertEquals(count($command->errors), 0);
    }

    public function test_script_validation() {
        // Create a new workflow and step.
        $workflow = $this->create_workflow(false);
        $step     = $this->create_step($workflow);

        // Try with an invalid script.
        $script = 'bad script';

        // The method is_script_valid should return false.
        $this->assertFalse($step->is_script_valid($script));

        // The method require_script_valid should throw an exception.
        $this->expect_exception_without_halting('block_workflow_invalid_command_exception',
                $step, 'require_script_valid', $script);

        // The method get_validation_errors should give us one error.
        $errors = $step->get_validation_errors($script);
        $this->assertEquals(count($errors), 1);

        // And a valid script.
        $script = 'assignrole editingteacher to teacher';

        // The method is_script_valid should return true.
        $this->assertTrue($step->is_script_valid($script));

        // The method require_script_valid should return true.
        $this->assertTrue($step->require_script_valid($script));

        // The method get_validation_errors should give us no errors.
        $errors = $step->get_validation_errors($script);
        $this->assertEquals(count($errors), 0);
    }

    public function test_step_commands() {
        // Create a new workflow.
        $data = new stdClass();
        $data->shortname            = 'courseworkflow';
        $data->name                 = 'First Course Workflow';
        $data->appliesto            = 'course';
        $data->obsolete             = 0;
        $data->description          = 'This is a test workflow applying to a course for the unit test';
        $data->descriptionformat    = FORMAT_HTML;

        // Create a new workflow object.
        $workflow = new block_workflow_workflow();
        $workflow->create_workflow($data);

        // Retrieve the first workflow step.
        $step = new block_workflow_step();
        $step->load_workflow_stepno($workflow->id, 1);

        // Check that it's a step.
        $this->assertInstanceOf('block_workflow_step', $step);

        // The default script is just a comment, first validate it and verify that we have no valid commands returned.
        $commands = $step->validate_script($step->onactivescript);
        $this->assertEquals(count($commands->commands), 0);
        $commands = $step->validate_script($step->oncompletescript);
        $this->assertEquals(count($commands->commands), 0);

        // Now create a new email email as we'll want to test the e-mail script options.
        $data = new stdClass();
        $data->message      = 'Example Body';
        $data->shortname    = 'testemail';
        $data->subject      = 'Example Subject';

        $email = new block_workflow_email();
        $return = $email->create($data);

        // Check that the return value is also a block_workflow_email.
        $this->assertInstanceOf('block_workflow_email', $return);

        // The create function should also reload the object into $email too.
        $this->assertSame($return, $email);

        // Update the step script to email our new testemail to the manager role.
        $update = new stdClass();
        $update->onactivescript = 'email testemail to manager';
        $return = $step->update_step($update);

        // Check that the return value is also a block_workflow_step.
        $this->assertInstanceOf('block_workflow_step', $return);

        // The update function should also reload the object into $step too.
        $this->assertSame($return, $step);

        // Check that the script still validates and now has 1 command.
        $commands = $step->validate_script($step->onactivescript);
        $this->assertEquals(count($commands->commands), 1);
        $commands = $step->validate_script($step->oncompletescript);
        $this->assertEquals(count($commands->commands), 0);

        // The method expectException seems to break things here and stop processing after catching...
        // Now attempt to create an invalid script. This should throw a  block_workflow_invalid_command_exception.
        $update = new stdClass();
        $update->onactivescript = 'invalidcommand';
        $this->expect_exception_without_halting('block_workflow_invalid_command_exception', $step, 'update_step', $update);

        // And a valid command with no arguments.
        $update = new stdClass();
        $update->onactivescript = 'email';
        $this->expect_exception_without_halting('block_workflow_invalid_command_exception', $step, 'update_step', $update);

        // And a valid command using an invalid email.
        $update = new stdClass();
        $update->onactivescript = 'email noemail to manager';
        $this->expect_exception_without_halting('block_workflow_invalid_command_exception', $step, 'update_step', $update);

        // And a valid command and email, to an invalid role.
        $update = new stdClass();
        $update->onactivescript = 'email testemail to norole';
        $this->expect_exception_without_halting('block_workflow_invalid_command_exception', $step, 'update_step', $update);
    }

    public function test_next_step_end() {
        // Create a new workflow.
        $workflow   = $this->create_workflow(false);

        // And add a step to that workflow.
        $firststep  = $this->create_step($workflow);

        // And add another step.
        $secondstep = $this->create_step($workflow);

        // Confirm that the workflow will end.
        $this->assertNull($workflow->atendgobacktostep);

        // Get the next step for the first step.
        // This should return the second step.
        $getnext = $firststep->get_next_step();
        $this->assertInstanceOf('block_workflow_step', $getnext);
        $this->compare_step($secondstep, $getnext);

        // Get the next step for the second step.
        // This should return false.
        $getnext = $secondstep->get_next_step();
        $this->assertFalse($getnext);
    }

    public function test_next_step_loop() {
        // Create a new workflow.
        $workflow   = $this->create_workflow(false);

        // And add a step to that workflow.
        $firststep  = $this->create_step($workflow);

        // And add another step.
        $secondstep = $this->create_step($workflow);

        // Confirm that the workflow will end.
        $this->assertNull($workflow->atendgobacktostep);

        // Set the atendgobacktostep to step one and confirm.
        $workflow->atendgobacktostep(1);
        $this->assertEquals($workflow->atendgobacktostep, 1);

        $secondstep = new block_workflow_step($secondstep->id);

        // Get the next step for the first step.
        // This should return the second step.
        $getnext = $firststep->get_next_step();
        $this->assertInstanceOf('block_workflow_step', $getnext);
        $this->compare_step($secondstep, $getnext);

        // Get the next step for the second step.
        // This should return the first step.
        $getnext = $secondstep->get_next_step();
        $this->assertInstanceOf('block_workflow_step', $getnext);
        $this->compare_step($firststep, $getnext);
    }

    public function test_set_workflow() {
        $step = new block_workflow_step();
        $step->set_workflow(1);
        $this->assertEquals($step->workflowid, 1);

        // Attempting to set again should throw an exception.
        $this->expect_exception_without_halting('block_workflow_invalid_step_exception', $step,
                'set_workflow', 1);
    }

    public function test_get_all_users_and_their_roles() {
        global $DB;
         $this->resetAfterTest(true);

        // Create roles
        $roleids = $DB->get_records_menu('role', null, '', 'shortname, id');
        $roles = $DB->get_records('role');

        $rolenames = array();
        $rolenames['manager'] = 'Manager';
        $rolenames['coursecreator'] = 'Course creater';
        $rolenames['editingteacher'] = 'Teacher';
        $rolenames['teacher'] = 'Non-editing teacher';
        $rolenames['student'] = 'Student';

        // Create a new workflow.
        $data = new stdClass();
        $data->shortname            = 'username role workflow';
        $data->name                 = 'User name in each role Workflow';
        $data->appliesto            = 'course';
        $data->obsolete             = 0;
        $data->description          = 'User name in each role workflow applying to a course for the unit test';
        $data->descriptionformat    = FORMAT_HTML;
        $workflow = new block_workflow_workflow();
        $workflow->create_workflow($data);

        $generator = $this->getDataGenerator();

        // Create users.
        $maxnumberofusers = 5;
        $users = array();
        for ($index = 1; $index < ($maxnumberofusers + 1); $index++) {
            $users[$index] = $generator->create_user(array('username' => 'user' . $index));
        }
        // Create a course.
        $course = $generator->create_course(array('shortname' => 'MK123-12J'));
        $coursecontext = context_course::instance($course->id);

        // Users one to 5 get following roles.
        // user1 is a manager.
        // user2 is a coursecreater.
        // user3 is a editingteacher.
        // user4 is a teacher.
        // user5 is a student.
        $manualenrol = enrol_get_plugin('manual');
        $manualenrol->add_default_instance($course);
        $instance1 = $DB->get_record('enrol', array('enrol' => 'manual', 'courseid' => $course->id));

        foreach ($users as $i => $user ) {
            $manualenrol->enrol_user($instance1,  $user->id, $roleids[$roles[$i]->shortname]);
        }
        $expectedroles = array();

        $expectedusers = array();
        foreach ($users as $key => $user) {
            $user->roles = array();
            $user->roles[] = $rolenames[$roles[$key]->shortname];
            $expectedusers[$user->id] = $user;
        }

        // Add workflow to context
        $state = $workflow->add_to_context($coursecontext->id);

        $stepstate = new block_workflow_step_state();
        $users = $stepstate->get_all_users_and_their_roles($roles, $coursecontext);

        $this->assertEquals('array', gettype($users));
        $this->assertEquals('array', gettype($expectedusers));

        $this->assertSame(sort($expectedusers), sort($users));

        return;
    }
}
