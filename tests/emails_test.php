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
 * Workflow block test unit for the email class.
 *
 * @package   block_workflow
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group block_workflow
 */

namespace block_workflow;

use block_workflow_email;
use stdClass;
use block_workflow_step;

defined('MOODLE_INTERNAL') || die();

// Include our test library so that we can use the same mocking system for all tests.
global $CFG;
require_once(dirname(__FILE__) . '/lib.php');

class emails_test extends \block_workflow_testlib {
    public function test_email_validation() {
        $data  = new stdClass();
        $email = new block_workflow_email();

        // Attempt to create an email with various types of bad data.
        // Currently missing a shortname.
        $this->expect_exception_without_halting('block_workflow_invalid_email_exception',
                $email, 'create', $data);

        // Now an empty shortname.
        $data->shortname = '';
        $this->expect_exception_without_halting('block_workflow_invalid_email_exception',
                $email, 'create', $data);
        $data->shortname    = 'shortname';

        // Now a missing message.
        $this->expect_exception_without_halting('block_workflow_invalid_email_exception',
                $email, 'create', $data);
        $data->message      = 'Example Message';

        // Now a missing subject.
        $this->expect_exception_without_halting('block_workflow_invalid_email_exception',
                $email, 'create', $data);
        $data->subject      = 'Example Subject';

        // Now we've got an extra field.
        $data->badfield     = 'baddata';
        $this->expect_exception_without_halting('block_workflow_invalid_email_exception',
                $email, 'create', $data);
        unset($data->badfield);
    }

    public function test_email_create() {
        $data  = new stdClass();
        $data->shortname    = 'shortname';
        $data->subject      = 'Example Subject';
        $data->message      = 'Example Message';

        $email = new block_workflow_email();

        // It should now work.
        $return = $email->create($data);

        // The variable $email should match the entered data.
        $this->compare_email($data, $email, array('id'));

        // Check that the return value is also a block_workflow_email.
        $this->assertInstanceOf('block_workflow_email', $return);

        // The create function should also reload the object into $email too.
        $this->assertInstanceOf('block_workflow_email', $email);

        // And the two should match.
        $this->compare_email($data, $email, array());

        // And trying to create another email with the same (valid) data should result in an
        // exception because the shortname is already in use.
        $this->expect_exception_without_halting('block_workflow_invalid_email_exception',
                $email, 'create', $data);

        // Check validation on e-mail updates.
        $data = new stdClass();

        // Try giving it a bad field.
        $data->badfield = 'baddata';
        $this->expect_exception_without_halting('block_workflow_invalid_email_exception',
                $email, 'update', $data);

        // Remove the badfield and give it a new shortname.
        unset($data->badfield);
        $data->shortname = 'newshortname';
    }

    public function test_email_update() {
        $data  = new stdClass();
        $data->shortname    = 'shortname';
        $data->subject      = 'Example Subject';
        $data->message      = 'Example Message';

        $email = new block_workflow_email();

        // It should now work.
        $return = $email->create($data);

        // The variable $email should match the entered data.
        $this->compare_email($data, $email, array('id'));

        // Check that the return value is also a block_workflow_email.
        $this->assertInstanceOf('block_workflow_email', $return);

        // The create function should also reload the object into $email too.
        $this->assertInstanceOf('block_workflow_email', $email);

        // And the two should match.
        $this->compare_email($data, $email, array());

        // Check validation on e-mail updates.
        $data->shortname = 'newshortname';
        $updated = $email->update($data);
        $this->assertEquals($updated->shortname, 'newshortname');

        // And swap it back.
        $data->shortname = 'shortname';
        $updated = $email->update($data);
        $this->assertEquals($updated->shortname, 'shortname');
    }

    public function test_email_update_validation_exception() {
        $email = $this->create_email();
        $data  = new stdClass();

        // Try giving it a bad field.
        $data->badfield = 'baddata';
        $this->expect_exception_without_halting('block_workflow_invalid_email_exception',
                $email, 'update', $data);
        unset($data->badfield);
    }

    public function test_email_update_validation() {
        $email = $this->create_email();
        $data  = new stdClass();
        // And change the shortname.
        $data->shortname = 'newshortname';
        $email->update($data);
        $this->compare_email($data, $email);
    }

    public function test_email_duplicate_shortnames_exception() {
        // Create an e-mail with shortname 'shortname'.
        $this->create_email('shortname');
        $email = new block_workflow_email();

        // Create an object for the insert -- this shares the same shortname.
        $data = new StdClass();
        $data->shortname = 'shortname';
        $data->message   = 'message';
        $data->subject   = 'subject';

        $this->expect_exception_without_halting('block_workflow_invalid_email_exception',
                $email, 'create', $data);
    }

    public function test_email_duplicate_shortnames() {
        // Create an e-mail with shortname 'shortname'.
        $this->create_email('shortname');
        $email = new block_workflow_email();

        // Create an object for the insert -- this shares the same shortname.
        $data = new StdClass();
        $data->subject   = 'subject';
        $data->message   = 'message';
        $data->shortname = 'newshortname';
        $email->create($data);

        // We shouldn't be able to update this to match the first mail either.
        $data->shortname = 'shortname';
        $this->expect_exception_without_halting('block_workflow_invalid_email_exception',
                $email, 'update', $data);
    }

    public function test_email_loading() {
        // Create a new e-mail.
        $email = $this->create_email('shortname');

        // Test loading the email by it's various loading methods.

        // Using the constructor.
        $reloader = new block_workflow_email($email->id);

        // Using the load_email_id.
        $reloader->load_email_id($email->id);

        // And the shortname.
        $result = $reloader->load_email_shortname($email->shortname);
        $this->assertInstanceOf('block_workflow_email', $result);

        $result = $reloader->require_email_shortname($email->shortname);
        $this->assertInstanceOf('block_workflow_email', $result);

        // And with bad data.
        $this->expect_exception_without_halting('block_workflow_invalid_email_exception',
                $reloader, '__construct', -1);

        $this->expect_exception_without_halting('block_workflow_invalid_email_exception',
                $reloader, 'load_email_id', -1);

        $result = $reloader->load_email_shortname('invalidshortname');
        $this->assertFalse((bool)$result);
        $this->expect_exception_without_halting('block_workflow_invalid_email_exception',
                $reloader, 'require_email_shortname', 'invalidshortname');

    }

    public function test_email_listing() {
        // Initially we should have no emails.
        $list = block_workflow_email::load_emails();
        $this->assertEquals(count($list), 0);

        // Create a new e-mail.
        $emailone = $this->create_email('shortname');

        // Now 1.
        $list = block_workflow_email::load_emails();
        $this->assertEquals(count($list), 1);

        // Create another new e-mail.
        $emailtwo = $this->create_email('inewshortname');

        // Now 2.
        $list = block_workflow_email::load_emails();
        $this->assertEquals(count($list), 2);

        // And deleting the e-mail should give us one again.
        $emailone->delete();

        // Now 1.
        $list = block_workflow_email::load_emails();
        $this->assertEquals(count($list), 1);
    }

    public function test_email_not_used() {
        // Create a new e-mail.
        $email = $this->create_email('shortname');

        // Check that we get that e-mail back.
        $list = block_workflow_email::load_emails();
        $this->assertEquals(count($list), 1);

        // The template should not be in use.
        $check = array_shift($list);
        $this->assertEquals($check->activecount, 0);
        $this->assertEquals($check->completecount, 0);

        // We'll add this to a workflow so grab one, and a step.
        $workflow   = $this->create_workflow();
        $steps      = $workflow->steps();
        $s1         = array_shift($steps);
        $step       = new block_workflow_step($s1->id);

        // Add the email to an onactivescript.
        $data = new stdClass();
        $data->onactivescript = 'email shortname to teacher';
        $step->update_step($data);

        // Check that it's marked as used.
        $list = block_workflow_email::load_emails();
        $check = array_shift($list);
        $this->assertEquals($check->activecount, 1);
        $this->assertEquals($check->completecount, 0);

        // The used_count (accurate count) should be 1.
        $count = $email->used_count();
        $this->assertEquals($count, 1);

        // This shouldn't be deletable.
        $deletable = $email->is_deletable();
        $this->assertFalse((bool)$deletable);

        // Check that we throw an exception.
        $this->expect_exception_without_halting('block_workflow_exception',
                $email, 'require_deletable');

        // And that we through an exception when actually trying to delete it.
        $this->expect_exception_without_halting('block_workflow_exception',
                $email, 'delete');

        // Add the email to an oncompletescript and remove from the onactivescript.
        $data = new stdClass();
        $data->oncompletescript = 'email shortname to teacher';
        $data->onactivescript   = '';
        $step->update_step($data);

        // Check that it's marked as used.
        $list = block_workflow_email::load_emails();
        $check = array_shift($list);
        $this->assertEquals($check->activecount, 0);
        $this->assertEquals($check->completecount, 1);

        // The used_count (accurate count) should be 1.
        $count = $email->used_count();
        $this->assertEquals($count, 1);

        // This shouldn't be deletable.
        $deletable = $email->is_deletable();
        $this->assertFalse((bool)$deletable);

        // Check that we throw an exception.
        $this->expect_exception_without_halting('block_workflow_exception',
                $email, 'require_deletable');

        // And that we through an exception when actually trying to delete it.
        $this->expect_exception_without_halting('block_workflow_exception',
                $email, 'delete');

        // Adding to both should produce the same results.
        $data = new stdClass();
        $data->oncompletescript = 'email shortname to teacher';
        $data->onactivescript   = 'email shortname to teacher';
        $step->update_step($data);

        // Check that it's marked as used.
        $list = block_workflow_email::load_emails();
        $check = array_shift($list);
        $this->assertEquals($check->activecount, 1);
        $this->assertEquals($check->completecount, 1);

        // The used_count (accurate count) should be 1.
        $count = $email->used_count();
        $this->assertEquals($count, 2);

        // This shouldn't be deletable.
        $deletable = $email->is_deletable();
        $this->assertFalse((bool)$deletable);

        // Check that we throw an exception.
        $this->expect_exception_without_halting('block_workflow_exception',
                $email, 'require_deletable');

        // And that we through an exception when actually trying to delete it.
        $this->expect_exception_without_halting('block_workflow_exception',
                $email, 'delete');

        // And removing from all scripts should make everything deletable.
        $data = new stdClass();
        $data->oncompletescript = '';
        $data->onactivescript   = '';
        $step->update_step($data);

        // Check that it's marked as used.
        $list = block_workflow_email::load_emails();
        $check = array_shift($list);
        $this->assertEquals($check->activecount,   0);
        $this->assertEquals($check->completecount, 0);

        // The used_count (accurate count) should be 0.
        $count = $email->used_count();
        $this->assertEquals($count, 0);

        // This shouldn't be deletable.
        $this->assertTrue($email->is_deletable());
        $this->assertTrue($email->require_deletable());

        // And we shouldn't throw an exception when deleting.
        $email->delete();
    }
}
