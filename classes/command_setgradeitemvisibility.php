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
 * Workflow script command to change whether a grade item is visible.
 *
 * @package   block_workflow
 * @copyright 2017 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');


/**
 * The command to set the visibility of the grade item that the workflow is assigned to
 *
 * @package    block
 * @subpackage workflow
 * @copyright 2017 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
class block_workflow_command_setgradeitemvisibility extends block_workflow_command {
    public function parse($args, $step, $state = null) {
        $data = new stdClass();
        $data->errors = array();

        // Check that this step workflow relates to an activity.
        if (!parent::is_activity($step->workflow())) {
            $data->errors[] = get_string('notanactivity', 'block_workflow', 'setgradeitemvisibility');
            return $data;
        }

        if (!plugin_supports('mod', $step->workflow()->appliesto, FEATURE_GRADE_HAS_GRADE)) {
            $data->errors[] = get_string('notgradesupported', 'block_workflow', 'setgradeitemvisibility');
            return $data;
        };

        if (plugin_supports('mod', $step->workflow()->appliesto, FEATURE_CONTROLS_GRADE_VISIBILITY)) {
            $data->errors[] = get_string('notcontrollablegradeitem', 'block_workflow', 'setgradeitemvisibility');
            return $data;
        };

        // Check for the correct visibility option.
        if ($args === 'hidden') {
            $data->visibility = 0;
        } else if ($args === 'visible') {
            $data->visibility = 1;
        } else {
            $data->errors[] = get_string('invalidvisibilitysetting', 'block_workflow', $args);
        }

        // Check that the workflow is valid for the given state and context.
        if ($state) {
            $data->cm = get_coursemodule_from_id($state->step()->workflow()->appliesto,
                    $state->context()->instanceid);
        }
        return $data;
    }

    public function execute($args, $state) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/lib/grade/grade_item.php');
        $data = $this->parse($args, $state->step(), $state);
        if (!$data) {
            return;
        }
        // Set grade_items visibility.
        $gradeitems = grade_item::fetch_all(array('courseid' => $data->cm->course, 'itemtype' => 'mod',
                'itemmodule' => $data->cm->modname, 'iteminstance' => $data->cm->instance));
        if ($gradeitems) {
            foreach ($gradeitems as $gradeitem) {
                if ($gradeitem->hidden === $data->visibility) {
                    continue;
                }
                $gradeitem->set_hidden($data->visibility);
            }
        }
    }
}
