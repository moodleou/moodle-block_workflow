<?php

/**
 * Workflow block test unit for the email class in lib.php
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
require_once(dirname(__FILE__) . '/testlib.php');

class test_block_workflow_emails extends block_workflow_testlib {
    public function test_email_validation() {
        $data  = new stdClass();
        $email = new block_workflow_email();

        /**
         * Attempt to create an email with various types of bad data
         */
        // Currently missing a shortname
        $this->expectExceptionWithoutHalting('block_workflow_invalid_email_exception',
                $email, 'create', $data);

        // Now an empty shortname
        $data->shortname = '';
        $this->expectExceptionWithoutHalting('block_workflow_invalid_email_exception',
                $email, 'create', $data);
        $data->shortname    = 'shortname';

        // Now a missing message
        $this->expectExceptionWithoutHalting('block_workflow_invalid_email_exception',
                $email, 'create', $data);
        $data->message      = 'Example Message';


        // Now a missing subject
        $this->expectExceptionWithoutHalting('block_workflow_invalid_email_exception',
                $email, 'create', $data);
        $data->subject      = 'Example Subject';

        // Now we've got an extra field
        $data->badfield     = 'baddata';
        $this->expectExceptionWithoutHalting('block_workflow_invalid_email_exception',
                $email, 'create', $data);
        unset($data->badfield);

        // It should now work
        $return = $email->create($data);

        // $email should match the entered data
        $this->compare_email($data, $email, array('id'));

        // Check that the return value is also a block_workflow_email
        $this->assertIsA($return, 'block_workflow_email');

        // The create function should also reload the object into $email too
        $this->assertIsA($email, 'block_workflow_email');

        // And the two should match
        $this->compare_email($data, $email, array());

        // And trying to create another email with the same (valid) data should result in an
        // exception because the shortname is already in use
        $this->expectExceptionWithoutHalting('block_workflow_invalid_email_exception',
                $email, 'create', $data);

        /**
         * Check validation on e-mail updates
         */
        $data = new stdClass();

        // Try giving it a bad field
        $data->badfield = 'baddata';
        $this->expectExceptionWithoutHalting('block_workflow_invalid_email_exception',
                $email, 'update', $data);

        // Remove the badfield and give it a new shortname
        unset($data->badfield);
        $data->shortname = 'newshortname';
        $updated = $email->update($data);

        $this->assertEqual($updated->shortname, 'newshortname');

        // And swap it back
        $data->shortname = 'shortname';
        $updated = $email->update($data);
        $this->assertEqual($updated->shortname, 'shortname');

    }

    public function test_email_update_validation() {
        $email = $this->create_email();
        $data  = new stdClass();

        // Try giving it a bad field
        $data->badfield = 'baddata';
        $this->expectExceptionWithoutHalting('block_workflow_invalid_email_exception',
                $email, 'update', $data);
        unset($data->badfield);

        // And change the shortname
        $data->shortname = 'newshortname';
        $email->update($data);
        $this->compare_email($data, $email);
    }

    public function test_email_duplicate_shortnames() {
        // Create an e-mail with shortname 'shortname'
        $this->create_email('shortname');
        $email = new block_workflow_email();

        // Create an object for the insert -- this shares the same shortname
        $data = new StdClass();
        $data->shortname = 'shortname';
        $data->message   = 'message';
        $data->subject   = 'subject';

        $this->expectExceptionWithoutHalting('block_workflow_invalid_email_exception',
                $email, 'create', $data);

        // And change the shortname so that it works
        $data->shortname = 'newshortname';
        $email->create($data);

        // We shouldn't be able to update this to match the first mail either
        $data->shortname = 'shortname';
        $this->expectExceptionWithoutHalting('block_workflow_invalid_email_exception',
                $email, 'update', $data);
    }

    public function test_email_loading() {
        // Create a new e-mail
        $email = $this->create_email('shortname');

        /**
         * Test loading the email by it's various loading methods
         */

        // Using the constructor
        $reloader = new block_workflow_email($email->id);

        // Using the load_email_id
        $reloader->load_email_id($email->id);

        // And the shortname
        $result = $reloader->load_email_shortname($email->shortname);
        $this->assertIsA($result, 'block_workflow_email');

        $result = $reloader->require_email_shortname($email->shortname);
        $this->assertIsA($result, 'block_workflow_email');

        // And with bad data
        $this->expectExceptionWithoutHalting('block_workflow_invalid_email_exception',
                $reloader, '__construct', -1);

        $this->expectExceptionWithoutHalting('block_workflow_invalid_email_exception',
                $reloader, 'load_email_id', -1);

        $result = $reloader->load_email_shortname('invalidshortname');
        $this->assertFalse($result);
        $this->expectExceptionWithoutHalting('block_workflow_invalid_email_exception',
                $reloader, 'require_email_shortname', 'invalidshortname');

    }

    public function test_email_listing() {
        // Initially we should have no emails
        $list = block_workflow_email::load_emails();
        $this->assertEqual(count($list), 0);

        // Create a new e-mail
        $emailone = $this->create_email('shortname');

        // Now 1
        $list = block_workflow_email::load_emails();
        $this->assertEqual(count($list), 1);

        // Create another new e-mail
        $emailtwo = $this->create_email('inewshortname');

        // Now 2
        $list = block_workflow_email::load_emails();
        $this->assertEqual(count($list), 2);

        // And deleting the e-mail should give us one again
        $emailone->delete();

        // Now 1
        $list = block_workflow_email::load_emails();
        $this->assertEqual(count($list), 1);
    }

    public function test_email_not_used() {
        // Create a new e-mail
        $email = $this->create_email('shortname');

        // Check that we get that e-mail back
        $list = block_workflow_email::load_emails();
        $this->assertEqual(count($list), 1);

        // The template should not be in use
        $check = array_shift($list);
        $this->assertEqual($check->activecount, 0);
        $this->assertEqual($check->completecount, 0);


        /**
         * We'll add this to a workflow so grab one, and a step
         */
        $workflow   = $this->create_workflow();
        $steps      = $workflow->steps();
        $s1         = array_shift($steps);
        $step       = new block_workflow_step($s1->id);

        /**
         * Add the email to an onactivescript
         */
        $data = new stdClass();
        $data->onactivescript = 'email shortname to teacher';
        $step->update_step($data);
    
        // Check that it's marked as used
        $list = block_workflow_email::load_emails();
        $check = array_shift($list);
        $this->assertEqual($check->activecount, 1);
        $this->assertEqual($check->completecount, 0);

        // The used_count (accurate count) should be 1
        $count = $email->used_count();
        $this->assertEqual($count, 1);

        // This shouldn't be deletable
        $deletable = $email->is_deletable();
        $this->assertFalse($deletable);

        // Check that we throw an exception
        $this->expectExceptionWithoutHalting('block_workflow_exception',
                $email, 'require_deletable');

        // And that we through an exception when actually trying to delete it
        $this->expectExceptionWithoutHalting('block_workflow_exception',
                $email, 'delete');

        /**
         * Add the email to an oncompletescript and remove from the onactivescript
         */
        $data = new stdClass();
        $data->oncompletescript = 'email shortname to teacher';
        $data->onactivescript   = '';
        $step->update_step($data);
    
        // Check that it's marked as used
        $list = block_workflow_email::load_emails();
        $check = array_shift($list);
        $this->assertEqual($check->activecount, 0);
        $this->assertEqual($check->completecount, 1);

        // The used_count (accurate count) should be 1
        $count = $email->used_count();
        $this->assertEqual($count, 1);

        // This shouldn't be deletable
        $deletable = $email->is_deletable();
        $this->assertFalse($deletable);

        // Check that we throw an exception
        $this->expectExceptionWithoutHalting('block_workflow_exception',
                $email, 'require_deletable');

        // And that we through an exception when actually trying to delete it
        $this->expectExceptionWithoutHalting('block_workflow_exception',
                $email, 'delete');

        /**
         * Adding to both should produce the same results
         */
        $data = new stdClass();
        $data->oncompletescript = 'email shortname to teacher';
        $data->onactivescript   = 'email shortname to teacher';
        $step->update_step($data);
    
        // Check that it's marked as used
        $list = block_workflow_email::load_emails();
        $check = array_shift($list);
        $this->assertEqual($check->activecount, 1);
        $this->assertEqual($check->completecount, 1);

        // The used_count (accurate count) should be 1
        $count = $email->used_count();
        $this->assertEqual($count, 2);

        // This shouldn't be deletable
        $deletable = $email->is_deletable();
        $this->assertFalse($deletable);

        // Check that we throw an exception
        $this->expectExceptionWithoutHalting('block_workflow_exception',
                $email, 'require_deletable');

        // And that we through an exception when actually trying to delete it
        $this->expectExceptionWithoutHalting('block_workflow_exception',
                $email, 'delete');

        /**
         * And removing from all scripts should make everything deletable
         */
        $data = new stdClass();
        $data->oncompletescript = '';
        $data->onactivescript   = '';
        $step->update_step($data);
    
        // Check that it's marked as used
        $list = block_workflow_email::load_emails();
        $check = array_shift($list);
        $this->assertEqual($check->activecount,   0);
        $this->assertEqual($check->completecount, 0);

        // The used_count (accurate count) should be 0
        $count = $email->used_count();
        $this->assertEqual($count, 0);

        // This shouldn't be deletable
        $this->assertTrue($email->is_deletable());
        $this->assertTrue($email->require_deletable());

        // And we shouldn't throw an exception when deleting
        $email->delete();
    }
}
