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

namespace block_workflow\local\forms;

// Make sure this isn't being directly accessed.
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Block workflow comment form.
 *
 * @package block_workflow
 * @copyright 2023 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class comment_form extends \moodleform {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $editoroptions = [
            'maxfiles' => 0,
            'autosave' => false,
        ];
        // Tiny need the pre-fix 'id_'.
        $mform->addElement(
            'editor',
            'comment_editor',
            get_string('commentlabel', 'block_workflow'),
            ['id' => 'id_wkf-comment-editor'],
            $editoroptions
        );
        $mform->setType('commentext', PARAM_RAW);
        $mform->addElement('button', 'wfk-submit', $this->_customdata['inputLabel']);
    }
}
