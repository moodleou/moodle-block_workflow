<?php

/**
 * Workflow edit form
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

class edit_workflow extends moodleform {
    function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('workflowsettings', 'block_workflow'));

        // Workflow base data
        $mform->addElement('text',     'shortname',           get_string('shortname', 'block_workflow'));
        $mform->setType('shortname', PARAM_TEXT);
        $mform->addRule('shortname', null, 'required', null, 'client');
        $mform->addRule('shortname', null, 'maxlength', 255);

        $mform->addElement('text',     'name',                get_string('name', 'block_workflow'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', null, 'maxlength', 255);

        $mform->addElement('editor',   'description_editor',  get_string('description', 'block_workflow'),
                block_workflow_editor_options());
        $mform->addRule('description_editor', null, 'required', null, 'client');
        $mform->setType('description_editor', PARAM_RAW);

        // What this workflow applies to.
        $appliesto = block_workflow_appliesto_list();
        $mform->addElement('select',   'appliesto',           get_string('appliesto', 'block_workflow'), $appliesto);
        $mform->setType('appliesto', PARAM_TEXT);
        if (!$this->_customdata['is_deletable']) {
            $mform->hardFreeze('appliesto');
        }

        // When reaching the end of the workflow, go back to
        $steplist = array();
        $steplist[null] = get_string('atendfinishworkflow', 'block_workflow');
        $finalstep = null;
        foreach ($this->_customdata['steps'] as $step) {
            $steplist[$step->stepno] = get_string('atendgobacktostepno', 'block_workflow', $step);
            $finalstep = $step;
        }

        if ($finalstep) {
            $mform->addElement('select',   'atendgobacktostep',
                    get_string('atendgobacktostep', 'block_workflow', $finalstep->stepno), $steplist);
            $mform->setType('atendgobacktostep', PARAM_INT);
        }

        // The current status of this workflow
        $enabledoptions = array();
        $enabledoptions['0'] = get_string('enabled', 'block_workflow');
        $enabledoptions['1'] = get_string('disabled', 'block_workflow');
        $mform->addElement('select',   'obsolete', get_string('status', 'block_workflow'), $enabledoptions);
        $mform->setDefault('obsolete', 1);
        $mform->setType('obsolete', PARAM_INT);

        $mform->addElement('hidden',   'workflowid');
        $mform->setType('workflowid', PARAM_INT);
        $this->add_action_buttons();
    }

    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (isset($data['shortname'])) {
            $workflow = new block_workflow_workflow();
            try {
                $workflow->load_workflow_from_shortname($data['shortname']);
                if ($workflow->id != $data['workflowid']) {
                    $errors['shortname']= get_string('shortnametaken', 'block_workflow', $workflow->name);
                }
            }
            catch (block_workflow_invalid_workflow_exception $e) {}
        }

        return $errors;
    }
}
