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
    /**
     * Parses the provided arguments and processes them based on the given step and state.
     *
     * @param array $args The arguments to be parsed.
     * @param mixed $step The step to be processed.
     * @param mixed|null $state Optional. The current state, if any. Defaults to null.
     * @return void
     */
    public function execute($args, $state) {
        global $DB;

        $data = $this->parse($args, $state->step(), $state);

        $record = new stdClass();
        $column = $data->column;
        $record->$column = $data->value;

        $existing = $DB->get_record($data->table, ['id' => $data->cm->instance]);
        $record->id = $existing->id;
        $DB->update_record($data->table, $record);
    }
}
