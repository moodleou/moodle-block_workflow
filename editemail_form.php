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
 * Email edit form
 *
 * @package   block_workflow
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once(dirname(__FILE__) . '/locallib.php');
require_once($CFG->libdir . '/formslib.php');

class email_edit extends moodleform {
    protected function definition() {
        $mform = $this->_form;
        $state = $this->_customdata['state'];
        $mform->addElement('header', 'general', get_string('emailsettings', 'block_workflow'));

        $editoroptions = $this->_customdata['editoroptions'];

        // Template data.
        $mform->addElement('text',      'shortname',    get_string('shortname', 'block_workflow'));
        $mform->setType('shortname', PARAM_TEXT);
        $mform->addRule('shortname', null, 'required', null, 'client');
        $mform->addRule('shortname', null, 'maxlength', 255);
        $mform->addRule('shortname', null, 'alphanumeric');

        $mform->addElement('text',      'subject',      get_string('emailsubject', 'block_workflow'));
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', null, 'required', null, 'client');

        $mform->addElement('editor',  'message', get_string('emailmessage', 'block_workflow'),
                block_workflow_editor_options());
        $mform->addRule('message', null, 'required', null, 'client');
        $mform->setType('message', PARAM_RAW);

        $mform->addElement('hidden', 'emailid');
        $mform->setType('emailid', PARAM_INT);
        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (isset($data['shortname'])) {
            $email = new block_workflow_email();
            if ($email->load_email_shortname($data['shortname'])) {
                if ($email->id != $data['emailid']) {
                    $errors['shortname']= get_string('shortnametakenemail', 'block_workflow', $data['shortname']);
                }
            }
        }
        return $errors;
    }
}
