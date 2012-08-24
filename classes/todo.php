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
 * Defines the class representing one todo list item for a step.
 *
 * @package   block_workflow
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');


/**
 * A class describing and handling actions for todo list items
 *
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @property-read int       $id                 The ID of the todo item
 * @property-read int       $stepid             The ID of the step associated with this todo item
 * @property-read string    $task               A short description task
 * @property-read int       $obsolete           The visibility of this workflow
 */
class block_workflow_todo {
    public $id;
    public $stepid;
    public $task;
    public $obsolete;

    private $step = null;

    /**
     * Constructor to obtain a todo item
     *
     * See documentation for {@link load_by_id} for further information.
     *
     * @param int $id The ID of the todo item to load
     * @return Object The workflow
     */
    public function __construct($id = null) {
        if ($id) {
            $this->load_by_id($id);
        }
    }

    /**
     * Private function to overload the current class instance with a
     * todo object
     *
     * @param   stdClass $todo Database record to overload into the
     *          object instance
     * @return  The instantiated block_workflow_todo object
     * @access  private
     */
    private function _load($todo) {
        $this->id       = $todo->id;
        $this->stepid   = $todo->stepid;
        $this->task     = $todo->task;
        $this->obsolete = $todo->obsolete;
        return $this;
    }

    /**
     * A list of expected settings for an todo task
     *
     * @return  array   The list of available settings
     */
    public function expected_settings() {
        return array(
            'id',
            'stepid',
            'task',
            'obsolete'
        );
    }

    /**
     * Load a todo given it's ID
     *
     * @param   int $id The ID of the todo to load
     * @return  The instantiated block_todo_todo object
     * @throws  block_workflow_invalid_todo_exception if the id is not found
     */
    public function load_by_id($id) {
        global $DB;
        $todo = $DB->get_record('block_workflow_step_todos', array('id' => $id));
        if (!$todo) {
            throw new block_workflow_invalid_todo_exception(get_string('invalidtodo', 'block_workflow'));
        }
        return $this->_load($todo);
    }

    /**
     * Function to create a new todo item
     *
     * @param   stdClass $todo containing the stepid, and task. Any
     *          obsolete value is ignored and set to BLOCK_WORKFLOW_ENABLED
     * @return  The newly created block_workflow_todo object
     * @throws  block_workflow_invalid_todo_exception if no task was specified
     * @throws  block_workflow_invalid_step_exception if no stepid was specified or the todo specified was invalid
     */
    public function create_todo($todo) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        // Check that we have a task.
        if (!isset($todo->task)) {
            $transaction->rollback(new block_workflow_invalid_todo_exception(get_string('tasknotspecified', 'block_workflow')));
        }

        // Ensure that a stepid was specified.
        if (!isset($todo->stepid)) {
            $transaction->rollback(new block_workflow_invalid_todo_exception(get_string('invalidstepid', 'block_workflow')));
        }

        // Ensure that the stepid related to a valid step.
        try {
            new block_workflow_step($todo->stepid);
        } catch (Exception $e) {
            $transaction->rollback($e);
        }

        // Set the obsolete value.
        $todo->obsolete = BLOCK_WORKFLOW_ENABLED;

        // Check that each of the submitted fields is a valid field.
        $expectedsettings = $this->expected_settings();
        foreach ((array) $todo as $k => $v) {
            if (!in_array($k, $expectedsettings)) {
                $transaction->rollback(new block_workflow_invalid_todo_exception(get_string('invalidfield', 'block_workflow', $k)));
            }
        }

        // Create the todo.
        $todo->id = $DB->insert_record('block_workflow_step_todos', $todo);

        // Finished with the transaction.
        $transaction->allow_commit();

        // Reload the object using the returned step id and return it.
        return $this->load_by_id($todo->id);
    }

    /**
     * Update the current todo with the data provided
     *
     * @param   stdClass $data A stdClass containing the fields to update
     *          for this todo. The id cannot be changed, or specified in
     *          this data set
     * @return  An update block_workflow_todo record as returned by
     *          {@link load_by_id}.
     */
    public function update_todo($data) {
        global $DB;

        // Retrieve the id for the current todo.
        $data->id = $this->id;

        $transaction = $DB->start_delegated_transaction();

        // Don't allow the stepid to be updated.
        if (isset($data->stepid) && ($data->stepid != $this->stepid)) {
            $transaction->rollback(new block_workflow_invalid_todo_exception(
                    get_string('todocannotchangestepid', 'block_workflow')));
        }

        // Check that each of the submitted fields is a valid field.
        $expectedsettings = $this->expected_settings();
        foreach ((array) $data as $k => $v) {
            if (!in_array($k, $expectedsettings)) {
                $transaction->rollback(new block_workflow_invalid_todo_exception(get_string('invalidfield', 'block_workflow', $k)));
            }
        }

        // Update the record.
        $DB->update_record('block_workflow_step_todos', $data);

        $transaction->allow_commit();

        // Return the updated todo object.
        return $this->load_by_id($data->id);
    }

    /**
     * remove the current todo
     *
     * This will also remove any associated todo_done records
     *
     * @return void
     */
    public function delete_todo() {
        global $DB;
        $transaction = $DB->start_delegated_transaction();

        // First remove any todo_done records.
        $DB->delete_records('block_workflow_todo_done', array('steptodoid' => $this->id));

        // Then remove the actual todo.
        $DB->delete_records('block_workflow_step_todos', array('id' => $this->id));

        $transaction->allow_commit();
    }

    /**
     * Clone an existing todo. If a stepid is specified, attach it to
     * that stepid instead of the same stepid as the current todo item.
     *
     * clone_todo returns a new object without altering the currently
     * loaded object.
     *
     * @param   int $srcid The ID of the todo item to clone
     * @param   int $stepid of the step to place this todo into. If
     *          no stepid is specified, the todo is placed within the same
     *          step as the source
     * @return  The newly created block_workflow_todo object
     * @static
     */
    public static function clone_todo($srcid, $stepid = null) {
        global $DB;
        $transaction = $DB->start_delegated_transaction();

        // Retrieve the source, and clone it.
        $src = new block_workflow_todo($srcid);
        $dst = new stdClass();

        // Copy the source based on the allowed settings.
        foreach ($src->expected_settings() as $k) {
            $dst->$k = $src->$k;
        }

        // Unset the id on the target.
        unset($dst->id);

        // If a new stepid was specified, then use it instead.
        if ($stepid) {
            $dst->stepid = $stepid;
        }

        // Ensure that obsolete is set.
        $dst->obsolete = ($dst->obsolete) ? 1 : 0;

        // Create the entry.
        $newtodo = new block_workflow_todo();
        $newtodo->create_todo($dst);

        // Allow the transaction at this stage, and return the newly
        // created object.
        $transaction->allow_commit();

        return $newtodo->load_by_id($newtodo->id);
    }

    /**
     * Toggle the obsolete flag for the current todo
     *
     * @return  An update block_workflow_todo record as returned by {@link load_todo}.
     */
    public function toggle() {
        global $DB;

        $todoid  = $this->id;

        $update = new stdClass();
        $update->id = $todoid;

        // Switch the obsolete state of the todo.
        if ($this->obsolete == BLOCK_WORKFLOW_ENABLED) {
            $update->obsolete = BLOCK_WORKFLOW_OBSOLETE;
        } else {
            $update->obsolete = BLOCK_WORKFLOW_ENABLED;
        }

        // Update the record.
        $DB->update_record('block_workflow_step_todos', $update);

        // Return the updated todo object.
        return $this->load_by_id($todoid);
    }

    /**
     * Toggle the obsolete state for a todo.
     *
     * @param   int $todoid The ID of the todo to toggle
     * @return  An update block_workflow_todo record as returned by {@link load_todo}.
     */
    public static function toggle_task($todoid) {
        $todo = new block_workflow_todo($todoid);;
        return $todo->toggle();
    }

    /**
     * Return the step associated with this step_todo
     *
     * @return  block_workflow_step The step that this task is associated with
     */
    public function step() {
        if ($this->step === null) {
            $this->step = new block_workflow_step($this->stepid);
        }
        return $this->step;
    }

}
