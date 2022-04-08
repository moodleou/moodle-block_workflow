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
 * Workflow script commands to set an acitvity setting.
 *
 * @package   block_workflow
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * The command to set an activity setting setting for the activity that the
 * workflow is assigned to
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
class block_workflow_command_setactivitysetting extends block_workflow_command {
    public function parse($args, $step, $state = null) {
        global $DB;
        $data = new stdClass();
        $data->errors = array();

        $workflow = $step->workflow();

        // Check that this step workflow relatees to an activity.
        if (!parent::is_activity($workflow)) {
            $data->errors[] = get_string('notanactivity', 'block_workflow', 'setactivityvisibility');
            return $data;
        }

        if ($state) {
            $data->cm = get_coursemodule_from_id($workflow->appliesto, $state->context()->instanceid);
        }

        // We'll use the database_manager to check whether tables and fields exist.
        $dbman = $DB->get_manager();

        // Check that the $appliesto table exists.
        $data->table = $workflow->appliesto;
        if (!$dbman->table_exists($data->table)) {
            $data->errors[] = get_string('invalidappliestotable', 'block_workflow', $workflow->appliesto);
            return $data;
        }

        // Break down the line. It should be in the format:
        // column to value
        // where column is a column in the activity settings table.
        $line = preg_split('/[\s+]/', $args);

        // Get the column and check that it exists.
        $data->column = array_shift($line);
        if (!$dbman->field_exists($data->table, $data->column)) {
            $data->errors[] = get_string('invalidactivitysettingcolumn', 'block_workflow', $data->column);
            return $data;
        }

        // Shift off the 'to' component.
        $to = array_shift($line);
        if ($to !== 'to') {
            $data->errors[] = get_string('invalidsyntaxmissingto', 'block_workflow');
            return $data;
        }

        // What we'll be setting it to.
        $data->value = array_shift($line);

        return $data;
    }
    public function execute($args, $state) {
        global $DB;

        $data = $this->parse($args, $state->step(), $state);

        $record = new stdClass();
        $column = $data->column;
        $record->$column = $data->value;

        $existing = $DB->get_record($data->table, array('id' => $data->cm->instance));
        $record->id = $existing->id;
        $DB->update_record($data->table, $record);
    }
}
