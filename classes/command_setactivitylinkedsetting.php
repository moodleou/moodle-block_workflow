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

defined('MOODLE_INTERNAL') || die();


/**
 * The command to set an activity setting that is stored in a linked table.
 * For example, the quizaccess_honestycheck setting is stored in a table
 * with columns id, quizid and honestycheckrequired. There is a row in this table
 * with honestycheckrequired when the quiz requires the check, and no row when
 * it is not required.
 *
 * The command takes the form
 *     setactivitylinkedsetting quizaccess_honestycheck by quizid set honestycheckrequired 1.
 * or
 *     setactivitylinkedsetting quizaccess_honestycheck by quizid clear.
 * That is "setactivitylinkedsetting {tablename} by {foreignkeycolumnname}" followed
 * by either "clear" or "set {field1} {value1} {field2} {value2} ...".
 *
 * @package    block_workflow
 * @copyright  2012 the Open University.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
class block_workflow_command_setactivitylinkedsetting extends block_workflow_command {
    /** @var string used to indicate that the script action is to set values. */
    const SET = 'set';
    /** @var string used to indicate that the script action is to set values. */
    const CLEAR = 'clear';

    public function parse($args, $step, $state = null) {
        global $DB;
        $dbman = $DB->get_manager();

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

        // Break down the line.
        $line = preg_split('/[\s+]/', $args);

        // Check that the table exists.
        $data->table = array_shift($line);
        if (!$dbman->table_exists($data->table)) {
            $data->errors[] = get_string('invalidappliestotable', 'block_workflow', $workflow->appliesto);
            return $data;
        }

        if (!$this->check_next_word_is('by', array_shift($line), $data)) {
            return $data;
        }

        // Get the column and check that it exists.
        $data->fkcolumn = array_shift($line);
        if (!$dbman->field_exists($data->table, $data->fkcolumn)) {
            $data->errors[] = get_string('invalidactivitysettingcolumn', 'block_workflow', $data->fkcolumn);
            return $data;
        }

        switch (array_shift($line)) {
            case self::CLEAR:
                $data->action = self::CLEAR;
                if (!empty($line)) {
                    $data->errors[] = get_string('invalidclearmustendcommand', 'block_workflow');
                    return $data;
                }
                break;

            case self::SET:
                $data->action = self::SET;
                break;

            default:
                $data->errors[] = get_string('invalidwordnotclearorset', 'block_workflow');
                return $data;
        }

        if ($data->action == self::SET) {
            $data->toset = array();
            while ($line) {
                $column = array_shift($line);
                if (!$dbman->field_exists($data->table, $column)) {
                    $data->errors[] = get_string('invalidactivitysettingcolumn', 'block_workflow', $column);
                    return $data;
                }

                if (empty($line)) {
                    $data->errors[] = get_string('invalidmissingvalue', 'block_workflow', $column);
                    return $data;
                }

                $data->toset[$column] = array_shift($line);
            }
        }

        return $data;
    }

    public function execute($args, $state) {
        global $DB;

        $data = $this->parse($args, $state->step(), $state);

        if ($data->action == self::CLEAR) {
            $DB->delete_records($data->table, array($data->fkcolumn => $data->cm->instance));
            return;
        }

        $existingrow = $DB->get_record($data->table, array($data->fkcolumn => $data->cm->instance));
        if ($existingrow) {
            $row = new stdClass();
            $row->id = $existingrow->id;
        } else {
            $row = new stdClass();
            $row->{$data->fkcolumn} = $data->cm->instance;
        }
        foreach ($data->toset as $column => $value) {
            $row->$column = $value;
        }

        if ($existingrow) {
            $DB->update_record($data->table, $row);
        } else {
            $DB->insert_record($data->table, $row);
        }
    }
}
