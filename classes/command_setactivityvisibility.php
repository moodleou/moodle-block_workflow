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
 * Workflow script command to change whether an activity is visible.
 *
 * @package   block_workflow
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');


/**
 * The command to set the visibility of the activity that the workflow is assigned to
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
class block_workflow_command_setactivityvisibility extends block_workflow_command {
    public function parse($args, $step, $state = null) {
        $data = new stdClass();
        $data->errors = array();

        // Check that this step workflow relatees to an activity
        if (!parent::is_activity($step->workflow())) {
            $data->errors[] = get_string('notanactivity', 'block_workflow', 'setactivityvisibility');
        }

        // Check for the correct visibility option
        if ($args == 'hidden') {
            $data->visibility = 0;
        }
        else if ($args == 'visible') {
            $data->visibility = 1;
        }
        else {
            $data->errors[] = get_string('invalidvisibilitysetting', 'block_workflow', $args);
        }

        // Check that the workflow is valid for the given state and context
        if ($state) {
            $data->context  = $state->context();
            $data->step     = $state->step();
            $data->workflow = $data->step->workflow();
            $data->cm       = get_coursemodule_from_id($data->workflow->appliesto, $data->context->instanceid);
        }

        return $data;
    }
    public function execute($args, $state) {
        global $CFG;
        require_once($CFG->dirroot . '/course/lib.php');
        $data = $this->parse($args, $state->step(), $state);

        // Change the visibility
        set_coursemodule_visible($data->cm->id, $data->visibility);
    }
}
