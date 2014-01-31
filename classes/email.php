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
 * Defines the class representing a workflow email template.
 *
 * @package    block_workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');


/**
 * E-mail email class
 *
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @property-read int       $id                 The ID of the email
 * @property-read string    $message            The message of the e-mail email
 * @property-read string    $shortname          The shortname for the e-mail email
 * @property-read string    $subject            The subject of the e-mail email
 */
class block_workflow_email {
    public $id;
    public $message;
    public $shortname;
    public $subject;

    /**
     * Constructor to obtain an e-mail template
     *
     * See documentation for {@link load_email_id} for further information.
     *
     * @param   int $emailid The ID of the e-mail to load
     * @return  Object The e-mail
     */
    public function __construct($emailid = null) {
        if ($emailid) {
            $this->load_email_id($emailid);
        }
    }

    /**
     * Private function to overload the current class instance with a
     * email object
     *
     * @param   stdClass $email Database record to overload into the
     * object   instance
     * @return  The instantiated block_workflow_email object
     * @access  private
     */
    private function _load($email) {
        $this->id           = $email->id;
        $this->message      = $email->message;
        $this->shortname    = $email->shortname;
        $this->subject      = $email->subject;
        return $this;
    }

    /**
     * A list of expected settings for an email template
     *
     * @return  array   The list of available settings
     */
    public function expected_settings() {
        return array(
            'id',
            'message',
            'messageformat',
            'shortname',
            'subject'
        );
    }

    /**
     * Load a email given it's ID
     *
     * @param   int $id The ID of the email to load
     * @return  The instantiated block_workflow_email object
     * @throws  block_workflow_invalid_email_exception if the id is not found
     */
    public function load_email_id($id) {
        global $DB;
        $email = $DB->get_record('block_workflow_emails', array('id' => $id));
        if (!$email) {
            throw new block_workflow_invalid_email_exception(get_string('invalidid', 'block_workflow'));
        }
        return $this->_load($email);
    }

    /**
     * Load a email given it's shortname
     *
     * @param   string  $shortname The shortname of the email to load
     * @return  The instantiated block_workflow_email object or false if the email does not exist
     */
    public function load_email_shortname($shortname) {
        global $DB;
        $email = $DB->get_record('block_workflow_emails', array('shortname' => $shortname));
        if (!$email) {
            return false;
        }
        return $this->_load($email);
    }

    /**
     * Load a email given it's shortname
     *
     * @param   string  $shortname The shortname of the email to load
     * @return  The instantiated block_workflow_email object
     * @throws  block_workflow_invalid_email_exception if the id is not found
     */
    public function require_email_shortname($shortname) {
        $email = $this->load_email_shortname($shortname);
        if (!$email) {
            throw new block_workflow_invalid_email_exception(get_string('invalidemailshortname', 'block_workflow', $shortname));
        }
        return $email;
    }

    /**
     * Return a list of emails sorted by shortname
     *
     * We also try to determine the number of times that the template is in use in the various step
     * onactivescript and oncompletescript fields.
     *
     * @return  Array of stdClass objects as returned by the database
     *          abstraction layer
     * @throws  block_workflow_invalid_email_exception if the id is not found
     */
    public static function load_emails() {
        global $DB;
        $sql = "SELECT emails.*,
            (
                SELECT COUNT(activescripts.id)
                FROM {block_workflow_steps} AS activescripts
                WHERE " . $DB->sql_like('activescripts.onactivescript',
                        $DB->sql_concat(':email1', 'emails.shortname', ':to1'), false) . "
            ) AS activecount,
            (
                SELECT COUNT(completescripts.id)
                FROM {block_workflow_steps} AS completescripts
                WHERE " . $DB->sql_like('completescripts.oncompletescript',
                        $DB->sql_concat(':email2', 'emails.shortname', ':to2'), false) . "
            ) AS completecount
            FROM {block_workflow_emails} AS emails
            ORDER BY shortname ASC
        ";
        $params = array('email1' => '%email%', 'email2' => '%email%', 'to1' => '%to%', 'to2' => '%to%');
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Function to create a new email
     *
     * @param   stdClass $email containing the subject, message, and
     *          optionally obsolete option.
     * @return  The newly created block_workflow_email object
     */
    public function create($email) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        // Check whether a shortname was specified.
        if (!isset($email->shortname) || empty($email->shortname)) {
            $transaction->rollback(new block_workflow_invalid_email_exception('invalidshortname', 'block_workflow'));
        }

        // Check whether this shortname is already in use.
        if ($DB->get_record('block_workflow_emails', array('shortname' => $email->shortname))) {
            $transaction->rollback(new block_workflow_invalid_email_exception('shortnameinuse', 'block_workflow'));
        }

        // Require the message.
        if (!isset($email->message)) {
            $transaction->rollback(new block_workflow_invalid_email_exception('invalidmessage', 'block_workflow'));
        }

        // Require the subject.
        if (!isset($email->subject)) {
            $transaction->rollback(new block_workflow_invalid_email_exception('invalidsubject', 'block_workflow'));
        }

        // Check that each of the submitted fields is a valid field.
        $expectedsettings = $this->expected_settings();
        foreach ((array) $email as $k => $v) {
            if (!in_array($k, $expectedsettings)) {
                $transaction->rollback(new block_workflow_invalid_email_exception(
                        get_string('invalidfield', 'block_workflow', $k)));
            }
        }

        // Insert the new email.
        $email->id = $DB->insert_record('block_workflow_emails', $email);

        $transaction->allow_commit();

        // And load it again.
        return $this->load_email_id($email->id);
    }

    /**
     * Update the current email with the data provided
     *
     * @param   stdClass $data A stdClass containing the fields to update
     *          for this email. The id cannot be changed, or specified
     *          in this data set
     * @return  An update block_workflow_email record as returned by
     *          {@link load_email_id}.
     */
    public function update($data) {
        global $DB;

        // Retrieve the id for the current email.
        $data->id = $this->id;

        $transaction = $DB->start_delegated_transaction();

        // Check whether this shortname is already in use.
        if (isset($data->shortname) &&
                ($id = $DB->get_field('block_workflow_emails', 'id', array('shortname' => $data->shortname)))) {
            if ($id != $data->id) {
                $transaction->rollback(new block_workflow_invalid_email_exception('shortnameinuse', 'block_workflow'));
            }
        }

        // Check that each of the submitted fields is a valid field.
        $expectedsettings = $this->expected_settings();
        foreach ((array) $data as $k => $v) {
            if (!in_array($k, $expectedsettings)) {
                $transaction->rollback(new block_workflow_invalid_email_exception(
                        get_string('invalidfield', 'block_workflow', $k)));
            }
        }

        // Update the record.
        $DB->update_record('block_workflow_emails', $data);

        $transaction->allow_commit();

        // And load it again.
        return $this->load_email_id($this->id);
    }

    /**
     * Determine whether the currently loaded e-mail is in use or not, and thus whether it can be removed.
     *
     * @return  boolean Whether the e-mail may be deleted or not
     */
    public function is_deletable() {
        // Count the number of uses.
        $count = $this->used_count();

        return (!$count > 0);
    }

    /**
     * Convenience function to require that an email is deletable
     *
     * This is checked using {@link is_deletable}.
     *
     * @throws  block_workflow_exception If the email is currently in use
     */
    public function require_deletable() {
        if (!$this->is_deletable()) {
            throw new block_workflow_exception(get_string('cannotremoveonlystep', 'block_workflow'));
        }
        return true;
    }

    /**
     * Delete the currently loaded email
     *
     * We first check whether we can delete this e-mail using {@link require_deletable}.
     *
     * @return void
     */
    public function delete() {
        global $DB;

        // First check that we can delete this.
        $this->require_deletable();
        $DB->delete_records('block_workflow_emails', array('id' => $this->id));
    }

    /**
     * Accurately count the number of times the e-mail template is in use
     *
     * Please note that this is quite computationally expensive
     *
     * @return  integer             The number of times the template is in use
     */
    public function used_count() {
        global $DB;

        // Grab the count.
        $count = 0;

        // Count the uses in the activescripts.
        $sql = "SELECT activescripts.onactivescript AS script
                FROM {block_workflow_steps} AS activescripts
                WHERE " . $DB->sql_like('activescripts.onactivescript', '?', false);
        $activescripts = $DB->get_records_sql($sql, array('%email%' . $this->shortname . '%to%'));
        $count += $this->_used_count($activescripts);

        // Count the uses in the completescripts.
        $sql = "SELECT completescripts.oncompletescript AS script
                FROM {block_workflow_steps} AS completescripts
                WHERE " . $DB->sql_like('completescripts.oncompletescript', '?', false);
        $completescripts = $DB->get_records_sql($sql, array('%email%' . $this->shortname . '%to%'));
        $count += $this->_used_count($completescripts);

        // Return the tital usage count.
        return $count;
    }

    /**
     * Check the provided array of scripts whether the template is really in use
     *
     * @param   array   $scripts    An array of stdClass objects with a script value
     * @return  integer             The number of times the template is in use
     */
    private function _used_count($scripts) {
        // Keep track of the count.
        $count = 0;

        // Check each of the provided scripts.
        foreach ($scripts as $script) {
            $commands = block_workflow_step::parse_script($script->script);
            foreach ($commands->commands as $c) {
                if ($c->command == 'email') {
                    // For each e-mail command, process the command and get the shortname.
                    $class = block_workflow_command::create($c->classname);
                    $data = $class->parse($c->arguments, $this);
                    if ($data->email->shortname == $this->shortname) {
                        // Shortnames match so increment the count.
                        $count++;
                    }
                }
            }
        }

        return $count;
    }
}
