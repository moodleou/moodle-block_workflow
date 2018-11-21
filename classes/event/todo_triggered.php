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
 * Event implementation for workflow and todo is triggered
 *
 * @package   block_workflow
 * @copyright 2018 IT Kartellet ApS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_workflow\event;
defined('MOODLE_INTERNAL') || die();


/**
 * This event is triggered when the completed-status of a to-do item is triggered.
 *
 * The new value of the completed-status can be read from $event->other['completed']. (A boolean)
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - int todoid: The ID of the to-do item which has its completed-status changed.
 *      - int stepid: The ID of the step that this to-do item is part of.
 *      - int workflowid: The ID of the workflow this to-do item is part of.
 *      - boolean completed: The new completed-status of the to-do item.
 *      - string stepname: The name of the step that this to-do item is part of.
 *      - string todoname: The name of the to-do item which has its completed-status changed.
 *      - string workflowname: The name of the workflow this to-do item is part of.
 * }
 */
class todo_triggered extends \core\event\base {

    protected function init() {
        $this->data['objecttable'] = 'block_workflow_step_todos';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Convenience factory-method to create an event from a step_state object.
     *
     * @param \block_workflow_step_state $stepstate The step-state containing
     *      part of the data to create the event from.
     * @param int $todoid The ID of the to-do item being triggered.
     * @param boolean $completed The new completed-state of the to-do item.
     * @return \core\event\base
     */
    public static function create_from_step_state(\block_workflow_step_state $stepstate, $todoid, $completed) {
        $todo = new \block_workflow_todo($todoid);
        return self::create([
            'context' => $stepstate->context(),
            'objectid' => $todoid,
            'other' => [
                'todoid' => $todoid,
                'stepid' => $stepstate->stepid,
                'workflowid' => $stepstate->step()->workflowid,
                'completed' => $completed,
                'stepname' => $stepstate->step()->name,
                'todoname' => $todo->task,
                'workflowname' => $stepstate->step()->workflow()->name
            ]
        ]);
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventtodotriggered', 'block_workflow');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     * (Note: These texts are not stored in the database, but read every time
     * the log is shown, so they must be backwards compatible)
     *
     * @return string
     */
    public function get_description() {
        $completedtext = $this->other['completed'] ? "completed" : "not completed";
        return "The user with id '$this->userid' set the completed-state to '$completedtext' of the todo '" .
                $this->other['todoname'] . "' (id = " . $this->other['todoid'] . ") " .
                "of the step '" . $this->other['stepname'] . "' (id = " . $this->other['stepid'] . ").";
    }

    /**
     * Returns a Moodle URL where the event can be observed afterwards.
     * Can be null, if no valid location is present.
     *
     * @return null|\moodle_url
     */
    public function get_url() {
        return new \moodle_url('/blocks/workflow/overview.php',
                ['contextid' => $this->contextid, 'workflowid' => $this->other['workflowid']]);
    }

    /**
     * Custom validation.
     *
     * Here we check that the extra custom fields for this events
     * (described in the class phpdoc comment) were actually given as parameters to
     * the event when it was triggered.
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->other['todoid'])) {
            throw new \coding_exception('The \'todoid\' value must be set in \'other\' of the event.');
        }

        if (!isset($this->other['stepid'])) {
            throw new \coding_exception('The \'stepid\' value must be set in \'other\' of the event.');
        }

        if (!isset($this->other['workflowid'])) {
            throw new \coding_exception('The \'workflowid\' value must be set in \'other\' of the event.');
        }

        if (!isset($this->other['completed'])) {
            throw new \coding_exception('The \'completed\' value must be set in \'other\' of the event.');
        }

        if (!isset($this->other['stepname'])) {
            throw new \coding_exception('The \'stepname\' value must be set in \'other\' of the event.');
        }

        if (!isset($this->other['todoname'])) {
            throw new \coding_exception('The \'todoname\' value must be set in \'other\' of the event.');
        }

        if (!isset($this->other['workflowname'])) {
            throw new \coding_exception('The \'workflowname\' value must be set in \'other\' of the event.');
        }
    }
}
