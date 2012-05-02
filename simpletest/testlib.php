<?php

/**
 * Workflow block test unit for locallib.php
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}

// Include our test library so that we can use the same mocking system for
// all tests
require_once(dirname(__FILE__) . '/lib.php');

class test_block_workflow_lib extends block_workflow_testlib {

    /**
     * Test that each of the defined variables are set correctly
     * - BLOCK_WORKFLOW_STATE_ACTIVE
     * - BLOCK_WORKFLOW_STATE_COMPLETED
     * - BLOCK_WORKFLOW_STATE_ABORTED
     * - BLOCK_WORKFLOW_ENABLED
     * - BLOCK_WORKFLOW_OBSOLETE
     */
    public function test_defines() {
        $this->assertEqual(BLOCK_WORKFLOW_STATE_ACTIVE,     'active');
        $this->assertEqual(BLOCK_WORKFLOW_STATE_COMPLETED,  'completed');
        $this->assertEqual(BLOCK_WORKFLOW_STATE_ABORTED,    'aborted');
        $this->assertEqual(BLOCK_WORKFLOW_ENABLED,          0);
        $this->assertEqual(BLOCK_WORKFLOW_OBSOLETE,         1);
    }

    public function test_workflow_validation() {
        // Create a new workflow
        $data = new stdClass();
        $workflow = new block_workflow_workflow();

        // Currently missing a shortname
        $this->expectExceptionWithoutHalting('block_workflow_invalid_workflow_exception',
                $workflow, 'create_workflow', $data);

        // And a name
        $data->shortname            = 'courseworkflow';
        $this->expectExceptionWithoutHalting('block_workflow_invalid_workflow_exception',
                $workflow, 'create_workflow', $data);


        // And now has an invalid appliesto
        $data->name                 = 'First Course Workflow';
        $data->appliesto            = 'baddata';
        $this->expectExceptionWithoutHalting('block_workflow_invalid_workflow_exception',
                $workflow, 'create_workflow', $data);

        // And now an invalid obsolete status
        $data->appliesto            = 'course';
        $data->obsolete             = -1;
        $this->expectExceptionWithoutHalting('block_workflow_invalid_workflow_exception',
                $workflow, 'create_workflow', $data);

        // And now specify an atendgobacktostep
        $data->obsolete             = 0;
        $data->atendgobacktostep    = 9;
        $this->expectExceptionWithoutHalting('block_workflow_invalid_workflow_exception',
                $workflow, 'create_workflow', $data);

        // And now a random field
        unset($data->atendgobacktostep);
        $data->badfield             = 'baddata';
        $this->expectExceptionWithoutHalting('block_workflow_invalid_workflow_exception',
                $workflow, 'create_workflow', $data);

        // It should now create
        unset($data->badfield);
        $workflow->create_workflow($data);
        $this->compare_workflow($data, $workflow);

        /**
         * Test uniqueness
         */
        $data = new stdClass();
        $data->shortname            = 'courseworkflow';
        $data->name                 = 'First Course Workflow';
        $data->appliesto            = 'course';
        $data->obsolete             = 0;
        $data->description          = 'This is a test workflow applying to a course for the unit test';
        $data->descriptionformat    = FORMAT_PLAIN;

        // This has the same shortname, but a different name
        $data->name                 = 'differentname';
        $this->expectExceptionWithoutHalting('block_workflow_invalid_workflow_exception',
                $workflow, 'create_workflow', $data);

        // and now a different shortname, and the same name
        $data->shortname            = 'somethingdifferent';
        $data->name                 = 'First Course Workflow';
        $this->expectExceptionWithoutHalting('block_workflow_invalid_workflow_exception',
                $workflow, 'create_workflow', $data);

        // And try to make the names unique
        $data->shortname            = 'courseworkflow';
        $workflow->create_workflow($data, true, true);

        // Verify that they have 1 appended
        $this->assertEqual($workflow->shortname, 'courseworkflow1');
        $this->assertEqual($workflow->name, 'First Course Workflow1');

        // And try again with an incremented number
        $data->shortname            = $workflow->shortname;
        $data->name                 = $workflow->name;
        $workflow->create_workflow($data, true, true);

        // Verify that they're different
        $this->assertEqual($workflow->shortname, 'courseworkflow2');
        $this->assertEqual($workflow->name, 'First Course Workflow2');

        /**
         * Test update_workflow
         */
        // We're testing on courseworkflow2
        $data = new stdClass();

        // Check with a used shortname
        $data->shortname = 'courseworkflow1';
        $this->expectExceptionWithoutHalting('block_workflow_invalid_workflow_exception',
                $workflow, 'update', $data);
        unset($data->shortname);

        // Invalid appliesto
        $data->appliesto = 'baddata';
        $this->expectExceptionWithoutHalting('block_workflow_invalid_workflow_exception',
                $workflow, 'update', $data);
        unset($data->appliesto);

        // Invalid atendgobackto
        $data->atendgobacktostep = 10;
        $this->expectExceptionWithoutHalting('block_workflow_invalid_step_exception',
                $workflow, 'update', $data);
        unset($data->atendgobacktostep);

        // Invalid obsolete
        $data->obsolete = -1;
        $this->expectExceptionWithoutHalting('block_workflow_invalid_workflow_exception',
                $workflow, 'update', $data);
        unset($data->obsolete);

        // Random settings
        $data->badfield = 'baddata';
        $this->expectExceptionWithoutHalting('block_workflow_invalid_workflow_exception',
                $workflow, 'update', $data);
        unset($data->badfield);

        // Update with the same shortname works
        $data->shortname = 'courseworkflow2';
        $workflow->update($data);
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
        // Create a new workflow object
        $workflow = new block_workflow_workflow();

        // Check that an exception is thrown when trying to load an invalid
        // workflow by id
        $this->expectExceptionWithoutHalting('block_workflow_invalid_workflow_exception',
                $workflow, 'load_workflow', -1);

        // Create a new workflow
        $data = new stdClass();
        $data->shortname            = 'courseworkflow';
        $data->name                 = 'First Course Workflow';
        $data->appliesto            = 'course';
        $data->obsolete             = 0;
        $data->description          = 'This is a test workflow applying to a course for the unit test';
        $data->descriptionformat    = FORMAT_PLAIN;

        // create_workflow will return a completed workflow object
        $return = $workflow->create_workflow($data);

        // Test that we still have a block_workflow_workflow
        $this->assertIsA($workflow, 'block_workflow_workflow');

        // The create function should also reload the object into $email too
        $this->assertIdentical($return, $workflow);

        // Check that we have an id
        $this->assertNotNull($workflow->id);

        // Test that the constructor loads the workflow properly when
        // passed the workflow's id
        $workflow = new block_workflow_workflow($workflow->id);

        // Test that we still have a block_workflow_workflow
        $this->assertIsA($workflow, 'block_workflow_workflow');

        // Check that an exception is thrown when trying to load an invalid
        // workflow by id
        $this->expectExceptionWithoutHalting('block_workflow_invalid_workflow_exception',
                $workflow, 'load_workflow', -1);

        // Check that an exception is thrown when trying to load an invalid
        // workflow by shortname
        $this->expectExceptionWithoutHalting('block_workflow_invalid_workflow_exception',
                $workflow, 'load_workflow_from_shortname', 'invalidshortname');

        // Test that we can get the workflow by it's shortname
        $return = $workflow->load_workflow_from_shortname($data->shortname);

        // Test that we still have a block_workflow_workflow
        $this->assertIsA($return, 'block_workflow_workflow');

        // The create function should also reload the object into $email too
        $this->assertIdentical($return, $workflow);
        // Check that each field is equal
        $this->assertEqual($workflow->shortname,            $data->shortname);
        $this->assertEqual($workflow->name,                 $data->name);
        $this->assertEqual($workflow->description,          $data->description);
        $this->assertEqual($workflow->descriptionformat,    $data->descriptionformat);
        $this->assertEqual($workflow->obsolete,             $data->obsolete);
        $this->assertEqual($workflow->appliesto,            $data->appliesto);

        /**
         * Check that attempts to create another object with the same
         * shortname throw an error
         */
        $this->expectExceptionWithoutHalting('block_workflow_invalid_workflow_exception', $workflow, 'create_workflow', $data);

        /**
         * Toggle the obsolete flag
         */
        // First confirm that the flag is currently set to ENABLED
        $this->assertEqual($workflow->obsolete, BLOCK_WORKFLOW_ENABLED);

        // Toggle it and confirm
        $workflow->toggle();
        $this->assertEqual($workflow->obsolete, BLOCK_WORKFLOW_OBSOLETE);

        // Toggle it and confirm
        $workflow->toggle();
        $this->assertEqual($workflow->obsolete, BLOCK_WORKFLOW_ENABLED);

        // Check that the context is correct (CONTEXT_COURSE)
        $this->assertEqual($workflow->context(), CONTEXT_COURSE);
    }

    public function test_workflow_steps() {
        // Create a new workflow
        $data = new stdClass();
        $data->shortname            = 'courseworkflow';
        $data->name                 = 'First Course Workflow';
        $data->description          = 'This is a test workflow applying to a course for the unit test';

        // Create a new workflow object
        $workflow = new block_workflow_workflow();

        // create_workflow will return a completed workflow object
        $workflow->create_workflow($data);

        // When creating a workflow, the initial step will have automatically been created.
        // Retrieve the list of steps
        $steps  = $workflow->steps();

        // Check that we only have one step at this point
        $this->assertEqual(count($steps), 1);

        // Retrieve the first step, and check that it isn't just a null value
        $step = array_pop($steps);
        $this->assertNotNull($step);

        // Test that we have a stdClass
        $this->assertIsA($step, 'stdClass');

        // Check that we have an id
        $this->assertNotNull($step->id);

        // And check that the values are acceptable
        $this->assertEqual($step->name,                 get_string('defaultstepname',           'block_workflow'));
        $this->assertEqual($step->instructions,         get_string('defaultstepinstructions',   'block_workflow'));
        $this->assertEqual($step->instructionsformat,   FORMAT_PLAIN);
        $this->assertEqual($step->stepno,               1);
        $this->assertEqual($step->onactivescript,       get_string('defaultonactivescript',     'block_workflow'));
        $this->assertEqual($step->oncompletescript,     get_string('defaultoncompletescript',   'block_workflow'));

        // Create a new step in the workflow
        $nsdata = new stdClass();
        $nsdata->workflowid         = $workflow->id;
        $nsdata->name               = 'Second Step';
        $nsdata->instructions       = 'New Instructions';
        $nsdata->instructionsformat = FORMAT_PLAIN;
        $nsdata->onactivescript     = '';
        $nsdata->oncompletescript   = '';

        $newstep = new block_workflow_step();
        $return = $newstep->create_step($nsdata);

        // The create function should also reload the object into $email too
        $this->assertIdentical($return, $newstep);

        // The new step should have a stepno of 2 automatically provisioned
        $this->assertEqual($newstep->stepno, 2);
        $this->compare_step($nsdata, $newstep);

        // Clone another step from the second step
        $clone = $newstep->clone_step($newstep->id);
        $this->assertEqual($clone->stepno, 3);
        $this->compare_step($nsdata, $clone);

        // Check that we now have three steps
        $this->assertEqual(count($workflow->steps()), 3);

        // Swap the orders of steps one and two
        $step = new block_workflow_step($step->id);
        $return = $newstep->swap_step_with($step);
        $this->assertIdentical($return, $newstep);

        // The returned step should now be stepno 1
        $this->assertEqual($newstep->stepno, 1);

        // Reload the step we've swapped with, and check that it's stepno 2
        $step = new block_workflow_step($step->id);
        $this->assertEqual($step->stepno, 2);

        // Change the stepno that the workflow loops back to at the end
        // First to something invalid
        $this->expectExceptionWithoutHalting('block_workflow_invalid_workflow_exception',
                $workflow, 'atendgobacktostep', -1);
        // confirm that it's still set to null
        $this->assertNull($workflow->atendgobacktostep);
        // then to something valid
        $workflow->atendgobacktostep(2);
        $this->assertEqual($workflow->atendgobacktostep, 2);

        // and then to null again
        $workflow->atendgobacktostep(null);
        $this->assertNull($workflow->atendgobacktostep);

        // Check whether we can get the next stepid
        $next = $step->get_next_step();
        $this->assertEqual($next->stepno, $step->stepno + 1);

        // atendgobacktostep is set to null, so $next should assert false
        $final = $next->get_next_step();
        $this->assertFalse($final);

        /**
         * Test renumbering the steps
         */
        // Renumber the steps
        $workflow->renumber_steps();

        // Confirm that the steps are now 1, 2, 3
        $i = 1;
        foreach ($workflow->steps() as $s) {
            $this->assertEqual($s->stepno, $i++);
        }

        // Break the numbering and renumber again by using higher numbers
        $update = new stdClass();
        $update->stepno = 10;
        $step->update_step($update);

        $workflow->renumber_steps();
        // Confirm that the steps are now 1, 2, 3 again
        $i = 1;
        foreach ($workflow->steps() as $s) {
            $this->assertEqual($s->stepno, $i++);
        }

        // Clone a step again
        $clone = $step->clone_step($step->id);

        // And delete the clone
        $clone->delete();

        // Double check that the step has gone
        $test = new block_workflow_step();
        $this->expectExceptionWithoutHalting('block_workflow_invalid_step_exception',
                $step, 'load_step', $clone->id);



        // Giving a bogus stepid to load_step should throw an exception
        $step = new block_workflow_step();
        $this->expectExceptionWithoutHalting('block_workflow_invalid_step_exception',
                $step, 'load_step', -1);

        // As should an invalid workflowid/stepno combination
        $this->expectExceptionWithoutHalting('block_workflow_invalid_step_exception',
                $step, 'load_workflow_stepno', -1, -1);
    }

    public function test_workflow_extended() {
        // Create a new workflow
        $data = new stdClass();
        $data->shortname            = 'courseworkflow';
        $data->name                 = 'First Course Workflow';
        $data->description          = 'This is a test workflow applying to a course for the unit test';

        // Create a new workflow object
        $workflow = new block_workflow_workflow();

        // create_workflow will return a completed workflow object
        $workflow->create_workflow($data);

        // When creating a workflow, the initial step will have automatically been created.
        // Retrieve the list of steps
        $steps  = $workflow->steps();

        // Check that we only have one step at this point
        $this->assertEqual(count($steps), 1);

        // Grab the first step
        $s1 = array_shift($steps);

        // And load the step properly
        $step = new block_workflow_step($s1->id);

        // We shouldn't be able to delete the step at this point
        $deletable = $step->is_deletable();
        $this->assertFalse($deletable);

        // And require_deletable should throw an exception
        $this->expectExceptionWithoutHalting('block_workflow_exception',
                $step, 'require_deletable');

        // As should delete
        $this->expectExceptionWithoutHalting('block_workflow_exception',
                $step, 'delete');

        /**
         * Try to create a step
         */

        // Create a new step in the workflow
        $newstep = new block_workflow_step();

        $nsdata = new stdClass();
        // We're currently missing a workflowid
        $this->expectExceptionWithoutHalting('block_workflow_invalid_step_exception',
                $newstep, 'create_step', $nsdata);

        $nsdata->workflowid         = $workflow->id;
        // Now we're missing a name
        $this->expectExceptionWithoutHalting('block_workflow_invalid_step_exception',
                $newstep, 'create_step', $nsdata);


        $nsdata->name               = 'Second Step';
        // Now we're missing instructions
        $this->expectExceptionWithoutHalting('block_workflow_invalid_step_exception',
                $newstep, 'create_step', $nsdata);

        $nsdata->instructions       = 'New Instructions';
        $nsdata->instructionsformat = FORMAT_PLAIN;
        $nsdata->onactivescript     = '';
        $nsdata->oncompletescript   = '';

        // This should actually succeed
        $newstep->create_step($nsdata);

        // The new step should have a stepno of 2 automatically provisioned
        $this->assertEqual($newstep->stepno, 2);

        // And compare the rest of the step
        $this->compare_step($nsdata, $newstep);

        // Lets remove the first step -- this will now work
        $step->delete();
    }

    public function test_workflow_clone() {
        // Create a new workflow
        $data = new stdClass();
        $data->shortname            = 'courseworkflow';
        $data->name                 = 'First Course Workflow';
        $data->description          = 'This is a test workflow applying to a course for the unit test';

        // Create a new workflow object
        $workflow = new block_workflow_workflow();

        // create_workflow will return a completed workflow object
        $workflow->create_workflow($data);

        // When creating a workflow, the initial step will have automatically been created.
        // Retrieve the list of steps
        $steps  = $workflow->steps();

        // Check that we only have one step at this point
        $this->assertEqual(count($steps), 1);

        // Create a new step in the workflow
        $nsdata = new stdClass();
        $nsdata->workflowid         = $workflow->id;
        $nsdata->name               = 'Second Step';
        $nsdata->instructions       = 'New Instructions';
        $nsdata->instructionsformat = FORMAT_PLAIN;
        $nsdata->onactivescript     = '';
        $nsdata->oncompletescript   = '';

        $newstep = new block_workflow_step();
        $return = $newstep->create_step($nsdata);

        // Now we'll try cloning the workflow - first with no changes
        $newdata = new stdClass();
        $this->expectExceptionWithoutHalting('block_workflow_invalid_workflow_exception',
                'block_workflow_workflow', 'clone_workflow', $workflow->id, $newdata);

        // And then changing the shortname
        $newdata->shortname         = 'clone';
        $clone = block_workflow_workflow::clone_workflow($workflow->id, $newdata);
        $cloneid = $clone->id;

        // Then remove the clone
        $clone->delete();

        // And confirm that it's gone
        $tmp = new block_workflow_workflow();
        $this->expectExceptionWithoutHalting('block_workflow_invalid_workflow_exception',
                $tmp, '__construct', $cloneid);

        // And then faking the form submission editor part too
        $newdata->description_editor = array(
            'text'      => 'Example text',
            'format'    => FORMAT_PLAIN,
        );
        $clone = block_workflow_workflow::clone_workflow($workflow->id, $newdata);

        // And remove them both
        $clone->delete();

    }

    public function test_course_workflow() {
        $workflow = $this->create_workflow();

        // Retrieve a list of all workflows available to courses:
        $list = $workflow->available_workflows('course');

        // We should have one workflow available
        $this->assertEqual(count($list), 1);

        // And that available workflow should match our the workflow we've just created
        $first = array_shift($list);
        $this->assertEqual($workflow->id, $first->id);
        $this->assertEqual($workflow->shortname, $first->shortname);
        $this->assertEqual($workflow->name, $first->name);
        $this->assertEqual($workflow->description, $first->description);
        $this->assertEqual($workflow->descriptionformat, $first->descriptionformat);
        $this->assertEqual($workflow->appliesto, $first->appliesto);
        $this->assertEqual($workflow->atendgobacktostep, $first->atendgobacktostep);
        $this->assertEqual($workflow->obsolete, $first->obsolete);

        // Attempt to assign this workflow to our course
        // add_to_context returns a block_workflow_step_state
        $state = $workflow->add_to_context($this->contextid);
        $this->assertIsA($state, 'block_workflow_step_state');

        // Trying to add it again will throw a block_workflow_exception
        $this->expectExceptionWithoutHalting('block_workflow_exception',
                $workflow, 'add_to_context', $this->contextid);

        // Jump to no step at all (to abort the workflow)
        $state->jump_to_step();

        // And check that we don't get an active step
        $step = new block_workflow_step();
        $this->expectExceptionWithoutHalting('block_workflow_not_assigned_exception',
                $step, 'load_active_step', $this->contextid);

        // And we should now be able to add it back
        $state = $workflow->add_to_context($this->contextid);
        $this->assertIsA($state, 'block_workflow_step_state');

        // It should be returned when loading contexts:
        $clist = $workflow->load_context_workflows($this->contextid);
        $this->assertEqual(count($clist), 1);

        // And the workflow should be in use once
        $inusetimes = $workflow->in_use_by();
        $this->assertEqual($inusetimes, 1);

        // And active once too
        $inusetimes = $workflow->in_use_by(null, true);
        $this->assertEqual($inusetimes, 1);

        // We should be able to grab a list of step_states for this context too
        $step_states = $workflow->step_states($this->contextid);

        // And this has one record -- only one step
        $this->assertEqual(count($step_states), 1);

        // Grab the active step
        $state = new block_workflow_step_state();
        $state->load_active_state($this->contextid);

        // Update the comment on it
        $state->update_comment('Sample Comment');

        // Verify our comment updated
        $this->assertEqual($state->comment, 'Sample Comment');

        // And we can't delete it
        $deletable = $workflow->is_deletable();
        $this->assertFalse($deletable);

        // require_deletable should throw an exception
        $this->expectExceptionWithoutHalting('block_workflow_exception',
                $workflow, 'require_deletable');

        // We should be able to remove the workflow from the context
        $workflow->remove_workflow($this->contextid);

        // And confirm again
        $step = new block_workflow_step();
        $this->expectExceptionWithoutHalting('block_workflow_not_assigned_exception',
                $step, 'load_active_step', $this->contextid);

        // We can't remove it again -- it's not in use
        $this->expectExceptionWithoutHalting('block_workflow_not_assigned_exception',
                $workflow, 'remove_workflow', $this->contextid);
    }

    public function test_activity_workflow() {
        // Create a new workflow
        $data = new stdClass();
        $data->shortname            = 'activityworkflow';
        $data->name                 = 'Activity Workflow';
        $data->appliesto            = 'quiz';
        $data->obsolete             = 0;
        $data->description          = 'Quiz Workflow';
        $data->descriptionformat    = FORMAT_PLAIN;

        // Create a new workflow object
        $workflow = new block_workflow_workflow();
        $workflow->create_workflow($data);

        // Ensure that the object is as we submitted it
        $this->compare_workflow($data, $workflow);

        // And check the return for $workflow->context() == CONTEXT_MODULE
        $this->assertEqual($workflow->context(), CONTEXT_MODULE);
    }

    public function test_appliesto_list() {
        $list = block_workflow_appliesto_list();
        $this->assertIsA($list, 'array');
        $this->assertNotEqual(count($list), 0);
    }

    public function test_editor_options() {
        $format = block_workflow_editor_options();
        $this->assertEqual($format['maxfiles'], 0);
    }

    public function test_editor_format() {
        $this->assertEqual(block_workflow_editor_format(FORMAT_HTML),   get_string('format_html', 'block_workflow'));
        $this->assertEqual(block_workflow_editor_format(FORMAT_PLAIN),  get_string('format_plain', 'block_workflow'));
        $this->assertEqual(block_workflow_editor_format(-1),            get_string('format_unknown', 'block_workflow'));

        // Check the editor_format used for imports
        $this->assertEqual(block_workflow_convert_editor_format(block_workflow_editor_format(FORMAT_HTML)), FORMAT_HTML);
        $this->assertEqual(block_workflow_convert_editor_format(block_workflow_editor_format(FORMAT_PLAIN)), FORMAT_PLAIN);
        $this->expectExceptionWithoutHalting('block_workflow_exception',
                null, 'block_workflow_convert_editor_format', 'baddata');

    }

    public function test_appliesto_string() {
        // Test that we get the correct strings from block_workflow_appliesto for both of it's routes
        $this->assertEqual(block_workflow_appliesto('course'), get_string('course'));
        $this->assertEqual(block_workflow_appliesto('quiz'), get_string('pluginname', 'mod_quiz'));
    }
}
