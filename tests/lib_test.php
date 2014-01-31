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
 * Workflow block test unit for locallib.php
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

class block_workflow_lib_test extends block_workflow_testlib {

    /**
     * Test that each of the defined variables are set correctly
     * - BLOCK_WORKFLOW_STATE_ACTIVE
     * - BLOCK_WORKFLOW_STATE_COMPLETED
     * - BLOCK_WORKFLOW_STATE_ABORTED
     * - BLOCK_WORKFLOW_ENABLED
     * - BLOCK_WORKFLOW_OBSOLETE
     */
    public function test_defines() {
        $this->assertEquals(BLOCK_WORKFLOW_STATE_ACTIVE,     'active');
        $this->assertEquals(BLOCK_WORKFLOW_STATE_COMPLETED,  'completed');
        $this->assertEquals(BLOCK_WORKFLOW_STATE_ABORTED,    'aborted');
        $this->assertEquals(BLOCK_WORKFLOW_ENABLED,          0);
        $this->assertEquals(BLOCK_WORKFLOW_OBSOLETE,         1);
    }

    public function test_workflow_validation() {
        // Create a new workflow.
        $data = new stdClass();
        $workflow = new block_workflow_workflow();

        // Currently missing a shortname.
        $this->expect_exception_without_halting('block_workflow_invalid_workflow_exception',
                $workflow, 'create_workflow', $data);

        // And a name.
        $data->shortname            = 'courseworkflow';
        $this->expect_exception_without_halting('block_workflow_invalid_workflow_exception',
                $workflow, 'create_workflow', $data);

        // And now has an invalid appliesto.
        $data->name                 = 'First Course Workflow';
        $data->appliesto            = 'baddata';
        $this->expect_exception_without_halting('block_workflow_invalid_workflow_exception',
                $workflow, 'create_workflow', $data);

        // And now an invalid obsolete status.
        $data->appliesto            = 'course';
        $data->obsolete             = -1;
        $this->expect_exception_without_halting('block_workflow_invalid_workflow_exception',
                $workflow, 'create_workflow', $data);

        // And now specify an atendgobacktostep.
        $data->obsolete             = 0;
        $data->atendgobacktostep    = 9;
        $this->expect_exception_without_halting('block_workflow_invalid_workflow_exception',
                $workflow, 'create_workflow', $data);

        // And now a random field.
        unset($data->atendgobacktostep);
        $data->badfield             = 'baddata';
        $this->expect_exception_without_halting('block_workflow_invalid_workflow_exception',
                $workflow, 'create_workflow', $data);
    }

    public function test_workflow_validation2() {
        // Create a new workflow.
        $data = new stdClass();
        $workflow = new block_workflow_workflow();

        $data->shortname            = 'courseworkflow';
        $data->name                 = 'First Course Workflow';
        $data->appliesto            = 'course';
        $data->obsolete             = 0;

        // It should now create.
        $workflow->create_workflow($data);
        $this->compare_workflow($data, $workflow);

        // Test uniqueness.
        $data = new stdClass();
        $data->shortname            = 'courseworkflow';
        $data->name                 = 'First Course Workflow';
        $data->appliesto            = 'course';
        $data->obsolete             = 0;
        $data->description          = 'This is a test workflow applying to a course for the unit test';
        $data->descriptionformat    = FORMAT_PLAIN;

        // This has the same shortname, but a different name.
        $data->name                 = 'differentname';
        $this->expect_exception_without_halting('block_workflow_invalid_workflow_exception',
                $workflow, 'create_workflow', $data);

        // And now a different shortname, and the same name.
        $data->shortname            = 'somethingdifferent';
        $data->name                 = 'First Course Workflow';
        $this->expect_exception_without_halting('block_workflow_invalid_workflow_exception',
                $workflow, 'create_workflow', $data);
    }

    public function test_workflow_validation3() {
        // Create a new workflow.
        $data = new stdClass();
        $workflow = new block_workflow_workflow();

        $data->shortname            = 'courseworkflow';
        $data->name                 = 'First Course Workflow';
        $data->appliesto            = 'course';
        $data->obsolete             = 0;
        $workflow->create_workflow($data);

        // And try to make a duplicate. The names should be automatically updated.
        $workflow->create_workflow($data, true, true);

        // Verify that they have 1 appended.
        $this->assertEquals($workflow->shortname, 'courseworkflow1');
        $this->assertEquals($workflow->name, 'First Course Workflow1');

        // And try again with an incremented number.
        $data->shortname            = $workflow->shortname;
        $data->name                 = $workflow->name;
        $workflow->create_workflow($data, true, true);

        // Verify that they're different.
        $this->assertEquals($workflow->shortname, 'courseworkflow2');
        $this->assertEquals($workflow->name, 'First Course Workflow2');

        // Test update_workflow.
        // We're testing on courseworkflow2.
        $data = new stdClass();

        // Update with the same shortname works.
        $data->shortname = 'courseworkflow2';
        $workflow->update($data);

        // Check with a used shortname.
        $data->shortname = 'courseworkflow';
        $this->expect_exception_without_halting('block_workflow_invalid_workflow_exception',
                $workflow, 'update', $data);
        unset($data->shortname);

        // Invalid appliesto.
        $data->appliesto = 'baddata';
        $this->expect_exception_without_halting('block_workflow_invalid_workflow_exception',
                $workflow, 'update', $data);
        unset($data->appliesto);

        // Invalid atendgobackto.
        $data->atendgobacktostep = 10;
        $this->expect_exception_without_halting('block_workflow_invalid_step_exception',
                $workflow, 'update', $data);
        unset($data->atendgobacktostep);

        // Invalid obsolete.
        $data->obsolete = -1;
        $this->expect_exception_without_halting('block_workflow_invalid_workflow_exception',
                $workflow, 'update', $data);
        unset($data->obsolete);

        // Random settings.
        $data->badfield = 'baddata';
        $this->expect_exception_without_halting('block_workflow_invalid_workflow_exception',
                $workflow, 'update', $data);
        unset($data->badfield);
    }

    /**
     * Test some of the basic workflow actions including:
     * - creating a new workflow;
     * - checking the default step created when a new workflow is created;
     * - adding a step and checking it's data;
     * - cloning that step and checking it's data; and
     * - re-ordering the steps and checking the resultant data.
     */
    public function test_workflow() {
        // Create a new workflow object.
        $workflow = new block_workflow_workflow();

        // Check that an exception is thrown when trying to load an invalid
        // workflow by id.
        $this->expect_exception_without_halting('block_workflow_invalid_workflow_exception',
                $workflow, 'load_workflow', -1);

        // Create a new workflow.
        $data = new stdClass();
        $data->shortname            = 'courseworkflow';
        $data->name                 = 'First Course Workflow';
        $data->appliesto            = 'course';
        $data->obsolete             = 0;
        $data->description          = 'This is a test workflow applying to a course for the unit test';
        $data->descriptionformat    = FORMAT_PLAIN;

        // The method create_workflow will return a completed workflow object.
        $return = $workflow->create_workflow($data);

        // Test that we still have a block_workflow_workflow.
        $this->assertInstanceOf('block_workflow_workflow', $workflow);

        // The create function should also reload the object into $email too.
        $this->assertSame($return, $workflow);

        // Check that we have an id.
        $this->assertNotNull($workflow->id);

        // Test that the constructor loads the workflow properly when
        // passed the workflow's id.
        $workflow = new block_workflow_workflow($workflow->id);

        // Test that we still have a block_workflow_workflow.
        $this->assertInstanceOf('block_workflow_workflow', $workflow);

        // Check that an exception is thrown when trying to load an invalid
        // workflow by id.
        $this->expect_exception_without_halting('block_workflow_invalid_workflow_exception',
                $workflow, 'load_workflow', -1);

        // Check that an exception is thrown when trying to load an invalid
        // workflow by shortname.
        $this->expect_exception_without_halting('block_workflow_invalid_workflow_exception',
                $workflow, 'load_workflow_from_shortname', 'invalidshortname');

        // Test that we can get the workflow by it's shortname.
        $return = $workflow->load_workflow_from_shortname($data->shortname);

        // Test that we still have a block_workflow_workflow.
        $this->assertInstanceOf('block_workflow_workflow', $return);

        // The create function should also reload the object into $email too.
        $this->assertSame($return, $workflow);
        // Check that each field is equal.
        $this->assertEquals($workflow->shortname,            $data->shortname);
        $this->assertEquals($workflow->name,                 $data->name);
        $this->assertEquals($workflow->description,          $data->description);
        $this->assertEquals($workflow->descriptionformat,    $data->descriptionformat);
        $this->assertEquals($workflow->obsolete,             $data->obsolete);
        $this->assertEquals($workflow->appliesto,            $data->appliesto);

        // Check that attempts to create another object with the same
        // shortname throw an error.
        $this->expect_exception_without_halting('block_workflow_invalid_workflow_exception', $workflow, 'create_workflow', $data);

    }
    public function test_workflow_toggle() {
        $workflow = new block_workflow_workflow();

        // Create a new workflow.
        $data = new stdClass();
        $data->shortname            = 'courseworkflow';
        $data->name                 = 'First Course Workflow';
        $data->appliesto            = 'course';
        $data->obsolete             = 0;
        $data->description          = 'This is a test workflow applying to a course for the unit test';
        $data->descriptionformat    = FORMAT_PLAIN;

        // The method create_workflow will return a completed workflow object.
        $return = $workflow->create_workflow($data);

        // Toggle the obsolete flag.
        // First confirm that the flag is currently set to ENABLED.
        $this->assertEquals($workflow->obsolete, BLOCK_WORKFLOW_ENABLED);

        // Toggle it and confirm.
        $workflow->toggle();
        $this->assertEquals($workflow->obsolete, BLOCK_WORKFLOW_OBSOLETE);

        // Toggle it and confirm.
        $workflow->toggle();
        $this->assertEquals($workflow->obsolete, BLOCK_WORKFLOW_ENABLED);

        // Check that the context is correct (CONTEXT_COURSE).
        $this->assertEquals($workflow->context(), CONTEXT_COURSE);
    }

    public function test_workflow_steps() {
        // Create a new workflow.
        $data = new stdClass();
        $data->shortname            = 'courseworkflow';
        $data->name                 = 'First Course Workflow';
        $data->description          = 'This is a test workflow applying to a course for the unit test';

        // Create a new workflow object.
        $workflow = new block_workflow_workflow();

        // The method create_workflow will return a completed workflow object.
        $workflow->create_workflow($data);

        // When creating a workflow, the initial step will have automatically been created.
        // Retrieve the list of steps.
        $steps  = $workflow->steps();

        // Check that we only have one step at this point.
        $this->assertEquals(count($steps), 1);

        // Retrieve the first step, and check that it isn't just a null value.
        $step = array_pop($steps);
        $this->assertNotNull($step);

        // Test that we have a stdClass.
        $this->assertInstanceOf('stdClass', $step);

        // Check that we have an id.
        $this->assertNotNull($step->id);

        // And check that the values are acceptable.
        $this->assertEquals($step->name,                 get_string('defaultstepname',           'block_workflow'));
        $this->assertEquals($step->instructions,         get_string('defaultstepinstructions',   'block_workflow'));
        $this->assertEquals($step->instructionsformat,   FORMAT_PLAIN);
        $this->assertEquals($step->stepno,               1);
        $this->assertEquals($step->onactivescript,       get_string('defaultonactivescript',     'block_workflow'));
        $this->assertEquals($step->oncompletescript,     get_string('defaultoncompletescript',   'block_workflow'));

        // Create a new step in the workflow.
        $nsdata = new stdClass();
        $nsdata->workflowid         = $workflow->id;
        $nsdata->name               = 'Second Step';
        $nsdata->instructions       = 'New Instructions';
        $nsdata->instructionsformat = FORMAT_PLAIN;
        $nsdata->onactivescript     = '';
        $nsdata->oncompletescript   = '';

        $newstep = new block_workflow_step();
        $return = $newstep->create_step($nsdata);

        // The create function should also reload the object into $email too.
        $this->assertSame($return, $newstep);

        // The new step should have a stepno of 2 automatically provisioned.
        $this->assertEquals($newstep->stepno, 2);
        $this->compare_step($nsdata, $newstep);

        // Clone another step from the second step.
        $clone = $newstep->clone_step($newstep->id);
        $this->assertEquals($clone->stepno, 3);
        $this->compare_step($nsdata, $clone);

        // Check that we now have three steps.
        $this->assertEquals(count($workflow->steps()), 3);

        // Swap the orders of steps one and two.
        $step = new block_workflow_step($step->id);
        $return = $newstep->swap_step_with($step);
        $this->assertSame($return, $newstep);

        // The returned step should now be stepno 1.
        $this->assertSame((int)$newstep->stepno, 1);

        // Reload the step we've swapped with, and check that it's stepno 2.
        $step = new block_workflow_step($step->id);
        $this->assertEquals($step->stepno, 2);

        // Change the stepno that the workflow loops back to at the end.
        // First to something invalid.
        $this->expect_exception_without_halting('block_workflow_invalid_workflow_exception',
                $workflow, 'atendgobacktostep', -1);
        // Confirm that it's still set to null.
        $this->assertNull($workflow->atendgobacktostep);
    }

    public function test_workflow_steps_2() {
        // Create a new workflow.
        $data = new stdClass();
        $data->shortname            = 'courseworkflow';
        $data->name                 = 'First Course Workflow';
        $data->description          = 'This is a test workflow applying to a course for the unit test';

        // Create a new workflow object.
        $workflow = new block_workflow_workflow();

        // The method create_workflow will return a completed workflow object.
        $workflow->create_workflow($data);

        // When creating a workflow, the initial step will have automatically been created.
        // Retrieve the list of steps.
        $steps  = $workflow->steps();

        // Check that we only have one step at this point.
        $this->assertEquals(count($steps), 1);

        // Retrieve the first step, and check that it isn't just a null value.
        $step = array_pop($steps);
        $this->assertNotNull($step);

        // Test that we have a stdClass.
        $this->assertInstanceOf('stdClass', $step);

        // Create a new step in the workflow.
        $nsdata = new stdClass();
        $nsdata->workflowid         = $workflow->id;
        $nsdata->name               = 'Second Step';
        $nsdata->instructions       = 'New Instructions';
        $nsdata->instructionsformat = FORMAT_PLAIN;
        $nsdata->onactivescript     = '';
        $nsdata->oncompletescript   = '';

        $newstep = new block_workflow_step();
        $return = $newstep->create_step($nsdata);

        // The create function should also reload the object into $email too.
        $this->assertSame($return, $newstep);

        // The new step should have a stepno of 2 automatically provisioned.
        $this->assertEquals($newstep->stepno, 2);
        $this->compare_step($nsdata, $newstep);

        // Clone another step from the second step.
        $clone = $newstep->clone_step($newstep->id);
        $this->assertEquals($clone->stepno, 3);
        $this->compare_step($nsdata, $clone);

        // Check that we now have three steps.
        $this->assertEquals(count($workflow->steps()), 3);

        // Swap the orders of steps one and two.
        $step = new block_workflow_step($step->id);
        $return = $newstep->swap_step_with($step);
        $this->assertSame($return, $newstep);

        // The returned step should now be stepno 1.
        $this->assertSame((int)$newstep->stepno, 1);

        // Reload the step we've swapped with, and check that it's stepno 2.
        $step = new block_workflow_step($step->id);
        $this->assertEquals($step->stepno, 2);

        // Then to something valid.
        $workflow->atendgobacktostep(2);
        $this->assertEquals($workflow->atendgobacktostep, 2);

        // And then to null again.
        $workflow->atendgobacktostep(null);
        $this->assertNull($workflow->atendgobacktostep);

        // Check whether we can get the next stepid.
        $next = $step->get_next_step();
        $this->assertEquals($next->stepno, $step->stepno + 1);

        // The field atendgobacktostep is set to null, so $next should assert false.
        $final = $next->get_next_step();
        $this->assertFalse($final);

        // Test renumbering the steps.
        // Renumber the steps.
        $workflow->renumber_steps();

        // Confirm that the steps are now 1, 2, 3.
        $i = 1;
        foreach ($workflow->steps() as $s) {
            $this->assertEquals($s->stepno, $i++);
        }

        // Break the numbering and renumber again by using higher numbers.
        $update = new stdClass();
        $update->stepno = 10;
        $step->update_step($update);

        $workflow->renumber_steps();
        // Confirm that the steps are now 1, 2, 3 again.
        $i = 1;
        foreach ($workflow->steps() as $s) {
            $this->assertEquals($s->stepno, $i++);
        }

        // Clone a step again.
        $clone = $step->clone_step($step->id);

        // And delete the clone.
        $clone->delete();

        // Double check that the step has gone.
        $test = new block_workflow_step();
        $this->expect_exception_without_halting('block_workflow_invalid_step_exception',
                $step, 'load_step', $clone->id);

        // Giving a bogus stepid to load_step should throw an exception.
        $step = new block_workflow_step();
        $this->expect_exception_without_halting('block_workflow_invalid_step_exception',
                $step, 'load_step', -1);

        // As should an invalid workflowid/stepno combination.
        $this->expect_exception_without_halting('block_workflow_invalid_step_exception',
                $step, 'load_workflow_stepno', -1, -1);
    }

    public function test_workflow_extended_exception() {
        // Create a new workflow.
        $data = new stdClass();
        $data->shortname            = 'courseworkflow';
        $data->name                 = 'First Course Workflow';
        $data->description          = 'This is a test workflow applying to a course for the unit test';

        // Create a new workflow object.
        $workflow = new block_workflow_workflow();

        // The method create_workflow will return a completed workflow object.
        $workflow->create_workflow($data);

        // When creating a workflow, the initial step will have automatically been created.
        // Retrieve the list of steps.
        $steps  = $workflow->steps();

        // Check that we only have one step at this point.
        $this->assertEquals(count($steps), 1);

        // Grab the first step.
        $s1 = array_shift($steps);

        // And load the step properly.
        $step = new block_workflow_step($s1->id);

        // We shouldn't be able to delete the step at this point.
        $deletable = $step->is_deletable();
        $this->assertFalse($deletable);

        // And require_deletable should throw an exception.
        $this->expect_exception_without_halting('block_workflow_exception',
                $step, 'require_deletable');

        // As should delete.
        $this->expect_exception_without_halting('block_workflow_exception',
                $step, 'delete');

        // Try to create a step.
        // Create a new step in the workflow.
        $newstep = new block_workflow_step();

        $nsdata = new stdClass();
        // We're currently missing a workflowid.
        $this->expect_exception_without_halting('block_workflow_invalid_step_exception',
                $newstep, 'create_step', $nsdata);

        $nsdata->workflowid         = $workflow->id;
        // Now we're missing a name.
        $this->expect_exception_without_halting('block_workflow_invalid_step_exception',
                $newstep, 'create_step', $nsdata);

        $nsdata->name               = 'Second Step';
        // Now we're missing instructions.
        $this->expect_exception_without_halting('block_workflow_invalid_step_exception',
                $newstep, 'create_step', $nsdata);
    }
    public function test_workflow_extended() {
        // Create a new workflow.
        $data = new stdClass();
        $data->shortname            = 'courseworkflow';
        $data->name                 = 'First Course Workflow';
        $data->description          = 'This is a test workflow applying to a course for the unit test';

        // Create a new workflow object.
        $workflow = new block_workflow_workflow();

        // The method create_workflow will return a completed workflow object.
        $workflow->create_workflow($data);

        $steps  = $workflow->steps();

        // Check that we only have one step at this point.
        $this->assertEquals(count($steps), 1);

        // Grab the first step.
        $s1 = array_shift($steps);

        // And load the step properly.
        $step = new block_workflow_step($s1->id);

        // Create a new step in the workflow.
        $newstep = new block_workflow_step();

        $nsdata = new stdClass();
        $nsdata->workflowid         = $workflow->id;
        $nsdata->name               = 'Second Step';

        $nsdata->instructions       = 'New Instructions';
        $nsdata->instructionsformat = FORMAT_PLAIN;
        $nsdata->onactivescript     = '';
        $nsdata->oncompletescript   = '';

        // This should actually succeed.
        $newstep->create_step($nsdata);

        // The new step should have a stepno of 2 automatically provisioned.
        $this->assertEquals($newstep->stepno, 2);

        // And compare the rest of the step.
        $this->compare_step($nsdata, $newstep);

        // Lets remove the first step -- this will now work.
        $step->delete();
    }

    public function test_workflow_clone_exception() {
        // Create a new workflow.
        $data = new stdClass();
        $data->shortname            = 'courseworkflow';
        $data->name                 = 'First Course Workflow';
        $data->description          = 'This is a test workflow applying to a course for the unit test';

        // Create a new workflow object.
        $workflow = new block_workflow_workflow();

        // The method create_workflow will return a completed workflow object.
        $workflow->create_workflow($data);

        // When creating a workflow, the initial step will have automatically been created.
        // Retrieve the list of steps.
        $steps  = $workflow->steps();

        // Check that we only have one step at this point.
        $this->assertEquals(count($steps), 1);

        // Create a new step in the workflow.
        $nsdata = new stdClass();
        $nsdata->workflowid         = $workflow->id;
        $nsdata->name               = 'Second Step';
        $nsdata->instructions       = 'New Instructions';
        $nsdata->instructionsformat = FORMAT_PLAIN;
        $nsdata->onactivescript     = '';
        $nsdata->oncompletescript   = '';

        $newstep = new block_workflow_step();
        $return = $newstep->create_step($nsdata);

        // Now we'll try cloning the workflow - first with no changes.
        $newdata = new stdClass();
        $this->expect_exception_without_halting('block_workflow_invalid_workflow_exception',
                'block_workflow_workflow', 'clone_workflow', $workflow->id, $newdata);
    }

    public function test_workflow_clone() {
        // Create a new workflow.
        $data = new stdClass();
        $data->shortname            = 'courseworkflow';
        $data->name                 = 'First Course Workflow';
        $data->description          = 'This is a test workflow applying to a course for the unit test';

        // Create a new workflow object.
        $workflow = new block_workflow_workflow();

        // The method create_workflow will return a completed workflow object.
        $workflow->create_workflow($data);

        // When creating a workflow, the initial step will have automatically been created.
        // Retrieve the list of steps.
        $steps  = $workflow->steps();

        // Check that we only have one step at this point.
        $this->assertEquals(count($steps), 1);

        // Create a new step in the workflow.
        $nsdata = new stdClass();
        $nsdata->workflowid         = $workflow->id;
        $nsdata->name               = 'Second Step';
        $nsdata->instructions       = 'New Instructions';
        $nsdata->instructionsformat = FORMAT_PLAIN;
        $nsdata->onactivescript     = '';
        $nsdata->oncompletescript   = '';

        $newstep = new block_workflow_step();
        $return = $newstep->create_step($nsdata);

        // And then changing the shortname.
        $newdata = new stdClass();
        $newdata->shortname         = 'clone';
        $clone = block_workflow_workflow::clone_workflow($workflow->id, $newdata);
        $cloneid = $clone->id;

        // Then remove the clone.
        $clone->delete();

        // And confirm that it's gone.
        $tmp = new block_workflow_workflow();
        $this->expect_exception_without_halting('block_workflow_invalid_workflow_exception',
                $tmp, '__construct', $cloneid);

        // And then faking the form submission editor part too.
        $newdata->description_editor = array(
            'text'      => 'Example text',
            'format'    => FORMAT_PLAIN,
        );
        $clone = block_workflow_workflow::clone_workflow($workflow->id, $newdata);

        // And remove them both.
        $clone->delete();

    }

    public function test_course_workflow() {
        $workflow = $this->create_workflow();

        // Retrieve a list of all workflows available to courses.
        $list = block_workflow_workflow::available_workflows('course');

        // We should have one workflow available.
        $this->assertEquals(count($list), 1);

        // And that available workflow should match our the workflow we've just created.
        $first = array_shift($list);
        $this->assertEquals($workflow->id, $first->id);
        $this->assertEquals($workflow->shortname, $first->shortname);
        $this->assertEquals($workflow->name, $first->name);
        $this->assertEquals($workflow->description, $first->description);
        $this->assertEquals($workflow->descriptionformat, $first->descriptionformat);
        $this->assertEquals($workflow->appliesto, $first->appliesto);
        $this->assertEquals($workflow->atendgobacktostep, $first->atendgobacktostep);
        $this->assertEquals($workflow->obsolete, $first->obsolete);

        // Attempt to assign this workflow to our course
        // add_to_context returns a block_workflow_step_state.
        $state = $workflow->add_to_context($this->contextid);
        $this->assertInstanceOf('block_workflow_step_state', $state);

        // Trying to add it again will throw a block_workflow_exception.
        $this->expect_exception_without_halting('block_workflow_exception',
                $workflow, 'add_to_context', $this->contextid);
    }

    public function test_course_workflow_2() {
        $workflow = $this->create_workflow();

        // Retrieve a list of all workflows available to courses.
        $list = block_workflow_workflow::available_workflows('course');

        // We should have one workflow available.
        $this->assertEquals(count($list), 1);

        // And that available workflow should match our the workflow we've just created.
        $first = array_shift($list);
        $this->assertEquals($workflow->id, $first->id);
        $this->assertEquals($workflow->shortname, $first->shortname);
        $this->assertEquals($workflow->name, $first->name);
        $this->assertEquals($workflow->description, $first->description);
        $this->assertEquals($workflow->descriptionformat, $first->descriptionformat);
        $this->assertEquals($workflow->appliesto, $first->appliesto);
        $this->assertEquals($workflow->atendgobacktostep, $first->atendgobacktostep);
        $this->assertEquals($workflow->obsolete, $first->obsolete);

        // Attempt to assign this workflow to our course
        // add_to_context returns a block_workflow_step_state.
        $state = $workflow->add_to_context($this->contextid);
        $this->assertInstanceOf('block_workflow_step_state', $state);

        // Jump to no step at all (to abort the workflow).
        $state->jump_to_step();

        // And check that we don't get an active step.
        $step = new block_workflow_step();
        $this->expect_exception_without_halting('block_workflow_not_assigned_exception',
                    $step, 'load_active_step', $this->contextid);

        // And we should now be able to add it back.
            $state = $workflow->add_to_context($this->contextid);
            $this->assertInstanceOf('block_workflow_step_state', $state);

            // It should be returned when loading contexts.
            $clist = $workflow->load_context_workflows($this->contextid);
            $this->assertEquals(count($clist), 1);

            // And the workflow should be in use once.
            $inusetimes = block_workflow_workflow::in_use_by($workflow->id);
            $this->assertEquals($inusetimes, 1);

            // And active once too.
            $inusetimes = block_workflow_workflow::in_use_by($workflow->id, true);
            $this->assertEquals($inusetimes, 1);

            // We should be able to grab a list of step_states for this context too.
            $stepstates = $workflow->step_states($this->contextid);

            // And this has one record -- only one step.
            $this->assertEquals(count($stepstates), 1);

            // Grab the active step.
            $state = new block_workflow_step_state();
            $state->load_active_state($this->contextid);

            // Update the comment on it.
            $state->update_comment('Sample Comment');

            // Verify our comment updated.
            $this->assertEquals($state->comment, 'Sample Comment');

            // And we can't delete it.
            $deletable = $workflow->is_deletable();
            $this->assertFalse($deletable);

            // The method require_deletable should throw an exception.
            $this->expect_exception_without_halting('block_workflow_exception',
                    $workflow, 'require_deletable');

            // We should be able to remove the workflow from the context.
            $workflow->remove_workflow($this->contextid);

            // And confirm again.
            $step = new block_workflow_step();
            $this->expect_exception_without_halting('block_workflow_not_assigned_exception',
                    $step, 'load_active_step', $this->contextid);

            // We can't remove it again -- it's not in use.
            $this->expect_exception_without_halting('block_workflow_not_assigned_exception',
                    $workflow, 'remove_workflow', $this->contextid);
    }

    public function test_activity_workflow() {
        // Create a new workflow.
        $data = new stdClass();
        $data->shortname            = 'activityworkflow';
        $data->name                 = 'Activity Workflow';
        $data->appliesto            = 'quiz';
        $data->obsolete             = 0;
        $data->description          = 'Quiz Workflow';
        $data->descriptionformat    = FORMAT_PLAIN;

        // Create a new workflow object.
        $workflow = new block_workflow_workflow();
        $workflow->create_workflow($data);

        // Ensure that the object is as we submitted it.
        $this->compare_workflow($data, $workflow);

        // And check the return for $workflow->context() == CONTEXT_MODULE.
        $this->assertEquals($workflow->context(), CONTEXT_MODULE);
    }

    public function test_appliesto_list() {
        $list = block_workflow_appliesto_list();
        $this->assertEquals('array', gettype($list));
        $this->assertNotEquals(count($list), 0);
    }

    public function test_editor_options() {
        $format = block_workflow_editor_options();
        $this->assertEquals($format['maxfiles'], 0);
    }

    public function test_editor_format() {
        $this->assertEquals(block_workflow_editor_format(FORMAT_HTML),   get_string('format_html', 'block_workflow'));
        $this->assertEquals(block_workflow_editor_format(FORMAT_PLAIN),  get_string('format_plain', 'block_workflow'));
        $this->assertEquals(block_workflow_editor_format(-1),            get_string('format_unknown', 'block_workflow'));

        // Check the editor_format used for imports.
        $this->assertEquals(block_workflow_convert_editor_format(block_workflow_editor_format(FORMAT_HTML)), FORMAT_HTML);
        $this->assertEquals(block_workflow_convert_editor_format(block_workflow_editor_format(FORMAT_PLAIN)), FORMAT_PLAIN);
        $this->expect_exception_without_halting('block_workflow_exception',
                null, 'block_workflow_convert_editor_format', 'baddata');
    }

    public function test_appliesto_string() {
        // Test that we get the correct strings from block_workflow_appliesto for both of it's routes.
        $this->assertEquals(block_workflow_appliesto('course'), get_string('course'));
        $this->assertEquals(block_workflow_appliesto('quiz'), get_string('pluginname', 'mod_quiz'));
    }
}
