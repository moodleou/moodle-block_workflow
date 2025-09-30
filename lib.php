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
 * Library for block workflow
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 *
 * @package   block_workflow
 * @copyright 2023 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_workflow\local\forms\comment_form;

/**
 * Function to get comment form fragment.
 *
 * @param array $args Arguments for form.
 * @return string HTML content.
 */
function block_workflow_output_fragment_commentform(array $args): string {
    $mform = new comment_form(null, ['inputLabel' => $args['inputLabel']]);
    return html_writer::div($mform->render(), 'block-workflow-panel');
}
