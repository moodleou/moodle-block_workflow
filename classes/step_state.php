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
    /**
     * @var ?int $step The current step of the workflow. Null if not set.
     */
    private $step  = null;
    /**
     * @var ?array $todos The list of tasks or actions associated with the step. Null if not set.
     */
    private $todos = null;

    /**
     * @var int $id The unique identifier for the step state.
     */
    public $id;
    /**
     * @var int $stepid The ID of the workflow step associated with this state.
     */
    public $stepid;
    /**
     * @var int $contextid The context ID where this workflow step state is applied.
     */
    public $contextid;
    /**
     * @var string $state The current state of the workflow step (e.g., 'inprogress', 'completed').
     */
    public $state;
    /**
     * @var int $timemodified The timestamp of the last modification to this step state.
     */
    public $timemodified;
    /**
     * @var ?string $comment Optional comment associated with the step state.
     */
    public $comment;
    /**
     * @var int The format of the comment (e.g., plain text, HTML).
     */
    public $commentformat;

    /**
     * @var ?string occasionally, so that it can be used in messges, we need
     * to store the comment from the previous step as well. (A bit hacky, but ...).
     */
    public $previouscomment = null;

    /** @var ?int format of $previouscomment, if present. */
    public $previouscommentformat = null;

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
     */
    private function load($state) {
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
        $state = $DB->get_record('block_workflow_step_states', ['id' => $stateid]);
        if (!$state) {
            throw new block_workflow_exception(get_string('invalidstate', 'block_workflow'));
        }
        return $this->load($state);
    }

    /**
     * Load an active state given a contextid
     *
     * @param   int $contextid The ID of the state to load
     * @return  The instantiated block_workflow_step_state object or false
     */
    public function load_active_state($contextid) {
        global $DB;
        $state = $DB->get_record(
            'block_workflow_step_states',
            ['contextid' => $contextid, 'state' => BLOCK_WORKFLOW_STATE_ACTIVE]
        );
        if (!$state) {
            return false;
        }
        return $this->load($state);
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
        $state = $DB->get_record('block_workflow_step_states', ['contextid' => $contextid, 'stepid' => $stepid]);
        if (!$state) {
            throw new block_workflow_not_assigned_exception(get_string('invalidstate', 'block_workflow'));
        }
        return $this->load($state);
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
        return context::instance_by_id($this->contextid);
    }

    /**
     * Update the comment for the currently loaded step_state
     *
     * @param   string  $newcomment The text of the new comment
     * @param   int     $newcommentformat The format of the new comment
     * @return  An update block_workflow_step_state record as returned by
     *          {@link load_state}.
     */
    public function update_comment($newcomment, $newcommentformat) {
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
        $change = new stdClass();
        $change->stepstateid    = $this->id;
        $change->newstate       = $newstatus;
        $change->userid         = $USER->id;
        $change->timestamp      = time();
        $DB->insert_record('block_workflow_state_changes', $change);

        // Make the change.
        $state = new stdClass();
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
                role_unassign_all(['component' => 'block_workflow', 'itemid' => $this->id]);
                break;
            default:
                break;
        }

        // Request that any required scripts be processed.
        switch ($state->state) {
            case BLOCK_WORKFLOW_STATE_ACTIVE:
                $this->step()->process_script($this, $this->step()->onactivescript);
                break;
            case BLOCK_WORKFLOW_STATE_COMPLETED:
                $this->step()->process_script($this, $this->step()->oncompletescript);
                break;
            default:
                break;
        }

        // Trigger an event for the status change.
        switch ($state->state) {
            case BLOCK_WORKFLOW_STATE_ACTIVE:
                $event = \block_workflow\event\step_activated::create_from_step_state($this);
                $event->trigger();
                break;
            case BLOCK_WORKFLOW_STATE_COMPLETED:
                $event = \block_workflow\event\step_completed::create_from_step_state($this);
                $event->trigger();
                break;
            case BLOCK_WORKFLOW_STATE_ABORTED:
                $event = \block_workflow\event\step_aborted::create_from_step_state($this);
                $event->trigger();
                break;
            default:
                break;
        }

        $transaction->allow_commit();

        // This is a workaround for a limitation of the message_send system.
        // This must be called outside of a transaction.
        block_workflow_command_email::message_send($this);

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
    public function finish_step($newcomment, $newcommentformat) {
        global $DB;
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
                $newstate = new stdClass();
                $newstate->stepid           = $nextstep->id;
                $newstate->contextid        = $this->contextid;
                $newstate->state            = BLOCK_WORKFLOW_STATE_ACTIVE;
                $newstate->timemodified     = time();
                $newstate->comment          = '';
                $newstate->commentformat    = 1;
                $newstate->id = $DB->insert_record('block_workflow_step_states', $newstate);
                $nextstate = new block_workflow_step_state($newstate->id);
            }

            $nextstate->previouscomment = $this->comment;
            $nextstate->previouscommentformat = $this->commentformat;
            $nextstate->change_status(BLOCK_WORKFLOW_STATE_ACTIVE);
        }

        $transaction->allow_commit();

        // This is a workaround for a limitation of the message_send system.
        // This must be called outside of a transaction.
        block_workflow_command_email::message_send($nextstep ? $nextstate : $this);

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
            block_workflow_command_email::message_send($state);

            return;
        }

        // Move to the specified step for this workflow.
        try {
            // Try and load an existing state to change status for.
            $nextstate = new block_workflow_step_state();
            $nextstate->load_context_step($this->contextid, $newstepid);
        } catch (block_workflow_not_assigned_exception $e) {
            // No step_state for this step on this context so create a new state.
            $newstate = new stdClass();
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
        $nextstate->previouscomment = get_string(
            'jumptostepcommentaddition',
            'block_workflow',
            $a
        ); // Hack alert!
        $nextstate->previouscommentformat = $state->commentformat;
        $nextstate->change_status(BLOCK_WORKFLOW_STATE_ACTIVE);

        $transaction->allow_commit();

        // This is a workaround for a limitation of the message_send system.
        // This must be called outside of a transaction.
        block_workflow_command_email::message_send($nextstate);

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
                    FROM {block_workflow_step_todos} todos
                    LEFT JOIN {block_workflow_todo_done} done ON done.steptodoid = todos.id AND done.stepstateid = ?
                    WHERE todos.stepid = ? AND todos.obsolete = 0
                    ORDER BY todos.id';
            $this->todos = $DB->get_records_sql($sql, [$this->id, $this->stepid]);
        }
        return $this->todos;
    }

    /**
     * Toggle the completed status of a task for a step state
     *
     * @param int $todoid  The ID of the task
     * @param bool whether user check/uncheck the link.
     * @return  boolean The new state of the task
     */
    public function todo_toggle(int $todoid, bool $check): bool {
        global $DB, $USER;
        $transaction = $DB->start_delegated_transaction();

        // Try and pick up the current task.
        $todo = $DB->get_record('block_workflow_todo_done', ['stepstateid' => $this->id, 'steptodoid' => $todoid]);
        // Has completed to do and user want to completed it. Do nothing.
        if ($todo && $check) {
            return true;
        }
        // Don't have to do and user want to uncheck it.
        if (!$check && !$todo) {
            return false;
        }
        // Trigger an event for the toggled completed status of this to-do.
        $event = \block_workflow\event\todo_triggered::create_from_step_state($this, $todoid, !$todo);
        $event->trigger();

        if (!$check) {
            // Remove the current record. There is no past history at present.
            $DB->delete_records('block_workflow_todo_done', ['id' => $todo->id]);
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
                FROM {block_workflow_state_changes} changes
                INNER JOIN {user} u ON u.id = changes.userid
                WHERE changes.stepstateid = ?
                ORDER BY changes.timestamp DESC';
        return $DB->get_records_sql($sql, [$stateid]);
    }

    /**
     * Returns an array of users including their roles
     * @param object $roles, array of roles
     * @param object $context, the workflow context
     */
    public function get_all_users_and_their_roles($roles, $context) {
        global $DB;
        if (!$roles) {
            return null;
        }

        $fields = \core_user\fields::for_identity($context);
        $fieldssql = $fields->get_sql('u');

        [$sortorder, $notused] = users_order_by_sql('u');
        $roleinfo = role_get_names($context);
        $rolenames = [];
        foreach ($roleinfo as $role) {
            $rolenames[$role->shortname] = $role->localname;
        }
        [$roleids, $params] = $DB->get_in_or_equal(array_keys($roles));
        $sql = "SELECT u.* {$fieldssql->selects}, r.shortname
                FROM {user} u
                {$fieldssql->joins}
                JOIN {role_assignments} ra ON u.id=ra.userid
                JOIN {role} r ON r.id = ra.roleid
                WHERE ra.roleid $roleids AND ra.contextid = ? ORDER BY $sortorder, r.sortorder";
        $params[] = $context->id;

        $userroles = $DB->get_recordset_sql($sql, array_merge($fieldssql->params, $params));

        $users = [];
        foreach ($userroles as $userrole) {
            if (!array_key_exists($userrole->id, $users)) {
                $users[$userrole->id] = $userrole;
                $users[$userrole->id]->roles = [$rolenames[$userrole->shortname]];
            } else {
                $users[$userrole->id]->roles[] = $rolenames[$userrole->shortname];
            }
        }
        $userroles->close();

        return $users;
    }
}
