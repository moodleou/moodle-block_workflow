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
 * Form for comments.
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once(dirname(__FILE__) . '/locallib.php');
require_once($CFG->libdir . '/formslib.php');

class state_editcomment extends moodleform {
    protected function definition() {
        $mform = $this->_form;
        $state = $this->_customdata['state'];

        $mform->addElement('header', 'general', get_string('updatecomment', 'block_workflow'));

        // Workflow and step information.
        $mform->addElement('static', 'workflowname',    get_string('workflow', 'block_workflow'));
        $mform->addElement('static', 'stepname',        get_string('step', 'block_workflow'));
        $mform->addElement('static', 'instructions',    get_string('instructions', 'block_workflow'));

        // The comment to update.
        $mform->addElement('editor', 'comment_editor', get_string('commentlabel', 'block_workflow'),
                block_workflow_editor_options());
        $mform->setType('comment_editor', PARAM_RAW);

        // The stateid (we need this).
        $mform->addElement('hidden', 'stateid');
        $mform->setType('stateid', PARAM_INT);

        $this->add_action_buttons(true, get_string('updatecomment', 'block_workflow'));
    }
}
