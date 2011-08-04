<?php

/**
 * Form for editing steps
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

class step_edit extends moodleform {
    function definition() {
        $mform = $this->_form;
        $state = $this->_customdata['state'];

        $mform->addElement('header', 'general', get_string('stepsettings', 'block_workflow'));

        // Step data
        $mform->addElement('text', 'name', get_string('name', 'block_workflow'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', null, 'maxlength', 255);

        $mform->addElement('editor',   'instructions_editor', get_string('instructions', 'block_workflow'),
                block_workflow_editor_options());
        $mform->setType('instructions_editor', PARAM_RAW);
        $mform->addRule('instructions_editor', null, 'required', null, 'client');

        // Scripts
        $scriptoptions = array('cols' => 80, 'rows' => 8);
        $mform->addElement('textarea', 'onactivescript', get_string('onactivescript', 'block_workflow'), $scriptoptions);
        $mform->setType('onactivescript', PARAM_RAW);

        $mform->addElement('textarea', 'oncompletescript', get_string('oncompletescript', 'block_workflow'), $scriptoptions);
        $mform->setType('oncompletescript', PARAM_RAW);

        // IDs
        $mform->addElement('hidden', 'stepid');
        $mform->setType('stepid', PARAM_INT);
        $mform->addElement('hidden', 'workflowid');
        $mform->setType('workflowid', PARAM_INT);

        // Before or after
        $mform->addElement('hidden', 'beforeafter');
        $mform->setType('beforeafter', PARAM_INT);

        $this->add_action_buttons();
    }

    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $step = new block_workflow_step($data['stepid']);

        // If the workflowid was specified, this step has not yet been created.
        // We need to set the workflow temporarily (it'll be overwritten
        // shortly anyway) for script validation to succeed
        if ($data['workflowid']) {
            $step->set_workflow($data['workflowid']);
        }


        if (isset($data['onactivescript'])) {
            // Validate the onactivescript
            $script = $step->validate_script($data['onactivescript']);
            if ($script->errors) {
                // Only display the first error
                $errors['onactivescript'] = get_string('invalidscript', 'block_workflow', $script->errors[0]);
            }
        }

        if (isset($data['oncompletescript'])) {
            // Validate the oncompletescript
            $script = $step->validate_script($data['oncompletescript']);
            if ($script->errors) {
                // Only display the first error
                $errors['oncompletescript'] = get_string('invalidscript', 'block_workflow', $script->errors[0]);
            }
        }

        return $errors;
    }
}
