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
 * Workflow block
 *
 * @package   block_workflow
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once(dirname(__FILE__) . '/locallib.php');
require_once($CFG->libdir . '/formslib.php');

class import_workflow extends moodleform {
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('workflowimport', 'block_workflow'));

        $mform->addElement('filepicker', 'importfile', get_string('importfile', 'block_workflow'),
                null, array('accepted_type' => '*.xml'));
        $mform->addRule('importfile', null, 'required', null, 'client');

        $this->add_action_buttons(true, get_string('importworkflow', 'block_workflow'));
    }
}

