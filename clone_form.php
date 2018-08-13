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
 * Form for handling workflow cloning
 *
 * @package   block_workflow
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/locallib.php');
require_once($CFG->libdir . '/formslib.php');


class clone_workflow extends moodleform {
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('cloneworkflow', 'block_workflow'));

        // Shortname.
        $mform->addElement('text', 'shortname', get_string('shortname', 'block_workflow'), array('size' => 80, 'maxlength' => 255));
        $mform->setType('shortname', PARAM_TEXT);
        $mform->addRule('shortname', null, 'required', null, 'client');
        $mform->addRule('shortname', null, 'maxlength', 255);

        // Name.
        $mform->addElement('text', 'name', get_string('name', 'block_workflow'), array('size' => 80, 'maxlength' => 255));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', null, 'maxlength', 255);

        // Description_editor.
        $mform->addElement('editor',   'description_editor',  get_string('description', 'block_workflow'),
                block_workflow_editor_options());
        $mform->addRule('description_editor', null, 'required', null, 'client');
        $mform->setType('description_editor', PARAM_RAW);

        $mform->addElement('static',   'appliesto',           get_string('appliesto', 'block_workflow'));

        // Workflowid.
        $mform->addElement('hidden',   'workflowid');
        $mform->setType('workflowid', PARAM_INT);

        $this->add_action_buttons(true, get_string('clone', 'block_workflow'));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (!empty($data['shortname'])) {
            $workflow = new block_workflow_workflow();
            try {
                $workflow->load_workflow_from_shortname($data['shortname']);
                $errors['shortname'] = get_string('shortnametaken', 'block_workflow', $workflow->name);
            } catch (block_workflow_invalid_workflow_exception $e) { // @codingStandardsIgnoreLine
                // Ignore errors here.
            }
        }
        return $errors;
    }
}
