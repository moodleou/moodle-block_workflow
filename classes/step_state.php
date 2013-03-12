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
 * Defines a class representing the current state of a step.
 *
 * @package   block_workflow
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');


/**
 * state class
 *
 * Class for handling workflow state operations
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @property-read int       $id                 The ID of the step_state
 * @property-read int       $stepid             The ID of the step associated with this state
 * @property-read int       $contextid          The ID of the context associated with this state
 * @property-read string    $state              The current state of this step (aborted, active, or completed)
 * @property-read int       $timemodified       The time that this step_state was last modified
 * @property-read string    $comment            The formatted comment of the step
 * @property-read int       $commentformat      The format of the comment field
 */
class block_workflow_step_state {
    private $step  = null;
    private $todos = null;

    public $id;
    public $stepid;
    public $contextid;
    public $state;
    public $timemodified;
    public $comment;
    public $commentformat;

    /**
     * Constructor to obtain a step_state
     *
     * See documentation for {@link load_state} for further information.
     *
     * @param   int $stateid The ID of the step_state to load
     * @return  Object The step_state
     */
    public function __construct($stateid = null) {
        if ($stateid) {
            $this->load_state($stateid);
        }
    }

    /**
     * Private function to overload the current class instance with a
     * step_state object
     *
     * @param stdClass $state Database record to overload into the
     * object instance
     * @return  The instantiated block_workflow_step_state object
     * @access private
     */
    private function _load($state) {
        $this->id               = $state->id;
        $this->stepid           = $state->stepid;
        $this->contextid        = $state->contextid;
        $this->state            = $state->state;
        $this->timemodified     = $state->timemodified;
        $this->comment          = $state->comment;
        $this->commentformat    = $state->commentformat;

        return $this;
    }

    /**
     * Load a state given it's ID
     *
     * @param   int $stateid The ID of the state to load
     * @return  The instantiated block_workflow_step_state object
     * @throws  block_workflow_step_states if the id is not found
     */
    public function load_state($stateid) {
        global $DB;
        $state = $DB->get_record('block_workflow_step_states', array('id' => $stateid));
        if (!$state) {
            throw new block_workflow_exception(get_string('invalidstate', 'block_workflow'));
        }
        return $this->_load($state);
    }

    /**
     * Load an active state given a contextid
     *
     * @param   int $contextid The ID of the state to load
     * @return  The instantiated block_workflow_step_state object or false
     */
    public function load_active_state($contextid) {
        global $DB;
        $state = $DB->get_record('block_workflow_step_states', array(
                'contextid' => $contextid, 'state' => BLOCK_WORKFLOW_STATE_ACTIVE));
        if (!$state) {
            return false;
        }
        return $this->_load($state);
    }

    /**
     * Convenience function to require that an active state be present
     *
     * @param   int $contextid The ID of the state to load
     * @return  The instantiated block_workflow_step_state object
     * @throws  block_workflow_not_assigned_exception if the context has no
     *          state assigned
     */
    public function require_active_state($contextid) {
        if (!$this->load_active_state($contextid)) {
            throw new block_workflow_not_assigned_exception(get_string('noactiveworkflow', 'block_workflow'));
        }
        return $this;
    }

    /**
     * Retrieve the state for the specified context and step id
     *
     * @param   $contextid  The ID of the context
     * @param   $stepid     The ID of the step to load
     * @return  An instantiated block_workflow_step_state object
     * @throws block_workflow_not_assigned_exception if no state is found for the specified
     *                      contextid and stepid combination
     */
    public function load_context_step($contextid, $stepid) {
        global $DB;
        $state = $DB->get_record('block_workflow_step_states', array(
                'contextid' => $contextid, 'stepid' => $stepid));
        if (!$state) {
            throw new block_workflow_not_assigned_exception(get_string('invalidstate', 'block_workflow'));
        }
        return $this->_load($state);
    }

    /**
     * Return the step associated with this step_state
     *
     * @return  block_workflow_step The step that this state is associated with
     */
    public function step() {
        if ($this->step === null) {
            $this->step = new block_workflow_step($this->stepid);
        }
        return $this->step;
    }

    /**
     * Return the context associated with this step_state
     *
     * @return Context Object The context that this state is associated with
     */
    public function context() {
        return get_context_instance_by_id($this->contextid);
    }

    /**
     * Update the comment for the currently loaded step_state
     *
     * @param   string  $newcomment The text of the new comment
     * @param   int     $newcommentformat The format of the new comment
     * @return  An update block_workflow_step_state record as returned by
     *          {@link load_state}.
     */
    public function update_comment($newcomment, $newcommentformat = FORMAT_PLAIN) {
        global $DB;
        $transaction = $DB->start_delegated_transaction();

        $state = new stdClass();
        $state->id              = $this->id;
        $state->comment         = $newcomment;
        $state->commentformat   = $newcommentformat;
        $state->timemodified    = time();

        // Update the record.
        $DB->update_record('block_workflow_step_states', $state);
        $transaction->allow_commit();

        // Return the updated step_state object.
        return $this->load_state($this->id);
    }

    /**
     * Update the status for the currently loaded step_state
     *
     * @param   string  $newstatus The new status
     * @return  An update block_workflow_step_state record as returned by
     *          {@link load_state}.
     */
    public function change_status($newstatus) {
        global $DB, $USER;
        $transaction = $DB->start_delegated_transaction();

        // Make a record of the change.
        $change = new stdClass;
        $change->stepstateid    = $this->id;
        $change->newstate       = $newstatus;
        $change->userid         = $USER->id;
        $change->timestamp      = time();
        $DB->insert_record('block_workflow_state_changes', $change);

        // Make the change.
        $state = new stdClass;
        $state->id              = $this->id;
        $state->timemodified    = $change->timestamp;
        $state->state           = $newstatus;
        $DB->update_record('block_workflow_step_states', $state);

        // Update the current state.
        $this->load_state($this->id);

        // Unassign any role assignments created for this workflow.
        switch ($this->state) {
            case BLOCK_WORKFLOW_STATE_ABORTED:
            case BLOCK_WORKFLOW_STATE_COMPLETED:
                role_unassign_all(array('component' => 'block_workflow', 'itemid' => $this->id));
                break;
            default:
                break;
        }

        // Request that any required scripts be processed.
        $this->step()->process_script($this);

        $transaction->allow_commit();

        // This is a workaround for a limitation of the message_send system.
        // This must be called outside of a transaction.
        block_workflow_command_email::message_send();

        // Return the updated step_state object.
        return $this->load_state($this->id);
    }

    /**
     * Taking a comment, mark a step as finished, and if applicable, move on to the next step.
     *
     * @param   string  $newcomment         The updated comment
     * @param   integer $newcommentformat   The format of the updated comment
     * @return  mixed   The next state or false if there is none
     */
    public function finish_step($newcomment, $newcommentformat = FORMAT_PLAIN) {
        global $DB, $USER;
        $transaction = $DB->start_delegated_transaction();

        // Update the comment.
        $this->update_comment($newcomment, $newcommentformat);

        // Change the status.
        $this->change_status(BLOCK_WORKFLOW_STATE_COMPLETED);

        // Move to the next step for this workflow.
        if ($nextstep = $this->step()->get_next_step()) {
            try {
                // Try and load an existing state to change status for.
                $nextstate = new block_workflow_step_state();
                $nextstate->load_context_step($this->contextid, $nextstep->id);

            } catch (block_workflow_not_assigned_exception $e) {
                // No step_state for this step on this context so create a new state.
                $newstate = new stdClass;
                $newstate->stepid           = $nextstep->id;
                $newstate->contextid        = $this->contextid;
                $newstate->state            = BLOCK_WORKFLOW_STATE_ACTIVE;
                $newstate->timemodified     = time();
                $newstate->comment          = '';
                $newstate->commentformat    = 1;
                $newstate->id = $DB->insert_record('block_workflow_step_states', $newstate);
                $nextstate = new block_workflow_step_state($newstate->id);
            }

            $nextstate->previouscomment = $this->comment; // Hack alert!
            $nextstate->change_status(BLOCK_WORKFLOW_STATE_ACTIVE);
        }

        $transaction->allow_commit();

        // This is a workaround for a limitation of the message_send system.
        // This must be called outside of a transaction.
        block_workflow_command_email::message_send();

        // Return the new state.
        if ($nextstep) {
            return $nextstate;
        }
        return false;
    }

    /**
     * Jump to the specified step for the specified contextid
     *
     * @param   integer $contextid  The ID of the context to jump steps for
     * @param   integer $newstepid  The ID of the new step to jump to
     * @return  mixed   The next state or false if there is none
     */
    public function jump_to_step($contextid = null, $newstepid = null) {
        global $DB, $USER;
        $transaction = $DB->start_delegated_transaction();

        if ($contextid) {
            $state = new block_workflow_step_state();
            $state->require_active_state($contextid);
        } else {
            $state = $this;
        }

        // Change the status of the current step, if there is one.
        if ($state->id) {
            $state->change_status(BLOCK_WORKFLOW_STATE_ABORTED);
        }

        // If the newstepid wasn't specified, we're just aborting the current step.
        if (!$newstepid) {
            // Commit the transaction.
            $transaction->allow_commit();

            // This is a workaround for a limitation of the message_send system.
            // This must be called outside of a transaction.
            block_workflow_command_email::message_send();

            return;
        }

        // Move to the specified step for this workflow.
        try {
            // Try and load an existing state to change status for.
            $nextstate = new block_workflow_step_state();
            $nextstate->load_context_step($this->contextid, $newstepid);

        } catch (block_workflow_not_assigned_exception $e) {
            // No step_state for this step on this context so create a new state.
            $newstate = new stdClass;
            $newstate->stepid           = $newstepid;
            $newstate->contextid        = $state->contextid;
            $newstate->state            = BLOCK_WORKFLOW_STATE_ACTIVE;
            $newstate->timemodified     = time();
            $newstate->comment          = '';
            $newstate->commentformat    = 1;
            $newstate->id = $DB->insert_record('block_workflow_step_states', $newstate);
            $nextstate = new block_workflow_step_state($newstate->id);
        }

        $a = new stdClass();
        $a->fromstep = $state->step()->name;
        $a->comment = $state->comment;
        $nextstate->previouscomment =
                get_string('jumptostepcommentaddition', 'block_workflow', $a); // Hack alert!
        $nextstate->change_status(BLOCK_WORKFLOW_STATE_ACTIVE);

        $transaction->allow_commit();

        // This is a workaround for a limitation of the message_send system.
        // This must be called outside of a transaction.
        block_workflow_command_email::message_send();

        // Return a reference to the new state.
        return $nextstate;
    }

    /**
     * Return the list of todo tasks belonging to this state with their current status
     *
     * @return  Array of stdClass objects as returned by the database
     *          abstraction layer
     */
    public function todos() {
        global $DB;

        if ($this->todos === null) {
            $sql = 'SELECT todos.*, done.timestamp, done.userid, done.id AS doneid
                    FROM {block_workflow_step_todos} AS todos
                    LEFT JOIN {block_workflow_todo_done} AS done ON done.steptodoid = todos.id AND done.stepstateid = ?
                    WHERE todos.stepid = ? AND todos.obsolete = 0
                    ORDER BY todos.id';
            $this->todos = $DB->get_records_sql($sql, array($this->id, $this->stepid));
        }
        return $this->todos;
    }

    /**
     * Toggle the completed status of a task for a step state
     * @param   int     $todoid  The ID of the task
     * @return  boolean The new state of the task
     */
    public function todo_toggle($todoid) {
        global $DB, $USER;
        $transaction = $DB->start_delegated_transaction();

        // Try and pick up the current task.
        $todo = $DB->get_record('block_workflow_todo_done', array('stepstateid' => $this->id, 'steptodoid' => $todoid));
        if ($todo) {
            // Remove the current record. There is no past history at present.
            $DB->delete_records('block_workflow_todo_done', array('id' => $todo->id));
            $transaction->allow_commit();
            return false;
        } else {
            // Mark the step as completed.
            $tododone = new stdClass();
            $tododone->stepstateid  = $this->id;
            $tododone->steptodoid   = $todoid;
            $tododone->userid       = $USER->id;
            $tododone->timestamp    = time();
            $DB->insert_record('block_workflow_todo_done', $tododone);
            $transaction->allow_commit();
            return true;
        }
    }

    /**
     * Return a set of changes for the specified or current state
     *
     * @return  mixed
     * The database results, or null if no result was found
     */
    public static function state_changes($stateid) {
        global $DB;
        $sql = 'SELECT changes.*, ' . $DB->sql_fullname('u.firstname', 'u.lastname') . ' AS username
                FROM {block_workflow_state_changes} AS changes
                INNER JOIN {user} AS u ON u.id = changes.userid
                WHERE changes.stepstateid = ?
                ORDER BY changes.timestamp DESC';
        return $DB->get_records_sql($sql, array($stateid));
    }
}
