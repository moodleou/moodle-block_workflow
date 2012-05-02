<?php

/**
 * Form for handling workflow cloning
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}

require_once(dirname(__FILE__) . '/locallib.php');
require_once($CFG->libdir . '/formslib.php');

class clone_workflow extends moodleform {
    function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('cloneworkflow', 'block_workflow'));

        // shortname
        $mform->addElement('text', 'shortname', get_string('shortname', 'block_workflow'), array('maxlength' => 255));
        $mform->setType('shortname', PARAM_TEXT);
        $mform->addRule('shortname', null, 'required', null, 'client');
        $mform->addRule('shortname', null, 'maxlength', 255);


        // name
        $mform->addElement('text', 'name', get_string('name', 'block_workflow'), array('maxlength' => 255));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', null, 'maxlength', 255);

        // description_editor
        $mform->addElement('editor',   'description_editor',  get_string('description', 'block_workflow'),
                block_workflow_editor_options());
        $mform->addRule('description_editor', null, 'required', null, 'client');
        $mform->setType('description_editor', PARAM_RAW);

        $mform->addElement('static',   'appliesto',           get_string('appliesto', 'block_workflow'));

        // workflowid
        $mform->addElement('hidden',   'workflowid');
        $mform->setType('workflowid', PARAM_INT);

        $this->add_action_buttons(true, get_string('clone', 'block_workflow'));
    }

    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if(!empty($data['shortname'])){
            $workflow = new block_workflow_workflow();
            try {
                $workflow->load_workflow_from_shortname($data['shortname']);
                $errors['shortname'] = get_string('shortnametaken', 'block_workflow', $workflow->name);
            }
            catch (block_workflow_invalid_workflow_exception $e) {}
        }
        return $errors;
    }
}
