<?php

/**
 * Email edit form
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}

require_once(dirname(__FILE__) . '/lib.php');
require_once($CFG->libdir . '/formslib.php');

class email_edit extends moodleform {
    function definition() {
        $mform = $this->_form;
        $state = $this->_customdata['state'];

        $mform->addElement('header', 'general', get_string('emailsettings', 'block_workflow'));

        $textoptions    = array('cols' => 80, 'rows' => 8);
        // Template data
        $mform->addElement('text',      'shortname',    get_string('shortname', 'block_workflow'));
        $mform->setType('shortname', PARAM_TEXT);
        $mform->addRule('shortname', null, 'required', null, 'client');
        $mform->addRule('shortname', null, 'maxlength', 255);
        $mform->addRule('shortname', null, 'alphanumeric');

        $mform->addElement('text',      'subject',      get_string('emailsubject', 'block_workflow'));
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', null, 'required', null, 'client');

        $mform->addElement('textarea',  'message',      get_string('emailmessage', 'block_workflow'), $textoptions);
        $mform->setType('message', PARAM_TEXT);
        $mform->addRule('message', null, 'required', null, 'client');

        $mform->addElement('hidden',    'emailid');
        $mform->setType('id', PARAM_INT);
        $this->add_action_buttons();
    }

    function validation($data, $files) {
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
