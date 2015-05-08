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
 * Defines the class representing a workflow.
 *
 * @package   block_workflow
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');


/**
 * Workflow class
 *
 * Class for handling workflow operations, and retrieving information from a workflow
 *
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @property-read int       $id                 The ID of the workflow
 * @property-read string    $shortname          The shortname of the workflow
 * @property-read string    $name               The full name of the workflow
 * @property-read string    $description        The formatted description of the workflow
 * @property-read int       $descriptionformat  The format of the description field
 * @property-read string    $appliesto          The type of module that the workflow applies to, or course
 * @property-read int       $atendgobacktostep  The number of the step to go back to when reaching the final step
 * @property-read int       $obsolete           The visibility of this workflow
 */
class block_workflow_workflow {

    public $id;
    public $shortname;
    public $name;
    public $description;
    public $descriptionformat;
    public $appliesto;
    public $atendgobacktostep;
    public $obsolete;

    /**
     * Constructor to obtain a workflow
     *
     * See documentation for {@link load_workflow} for further information.
     *
     * @param int $workflowid The ID of the workflow to load
     * @return Object The workflow
     */
    public function __construct($workflowid = null) {
        if ($workflowid) {
            $this->load_workflow($workflowid);
        }
    }

    /**
     * Private function to overload the current class instance with a
     * workflow object
     *
     * @param stdClass $workflow Database record to overload into the
     * object instance
     * @return The instantiated block_workflow_workflow object
     * @access private
     */
    private function _load($workflow) {
        $this->id                   = $workflow->id;
        $this->shortname            = $workflow->shortname;
        $this->name                 = $workflow->name;
        $this->description          = $workflow->description;
        $this->descriptionformat    = $workflow->descriptionformat;
        $this->appliesto            = $workflow->appliesto;
        $this->atendgobacktostep    = $workflow->atendgobacktostep;
        $this->obsolete             = $workflow->obsolete;
        return $this;
    }

    /**
     * A list of expected settings for a workflow
     *
     * @return  array   The list of available settings
     */
    public function expected_settings() {
        return array('id',
            'shortname',
            'name',
            'description',
            'descriptionformat',
            'appliesto',
            'atendgobacktostep',
            'obsolete'
        );
    }

    /**
     * Load a workflow given it's ID
     *
     * @param   int $workflowid The ID of the workflow to load
     * @return  The instantiated block_workflow_workflow object
     * @throws  block_workflow_invalid_workflow_exception if the id is not found
     */
    public function load_workflow($workflowid) {
        global $DB;
        $workflow = $DB->get_record('block_workflow_workflows', array('id' => $workflowid));
        if (!$workflow) {
            throw new block_workflow_invalid_workflow_exception(get_string('invalidworkflow', 'block_workflow'));
        }
        return $this->_load($workflow);
    }

    /**
     * Load a workflow given it's shortname
     *
     * @param   String $shortname The shortname of the workflow to load
     * @return  The instantiated block_workflow_workflow object
     * @throws  block_workflow_invalid_workflow_exception if the shortname is not found
     */
    public function load_workflow_from_shortname($shortname) {
        global $DB;
        $workflow = $DB->get_record('block_workflow_workflows', array('shortname' => $shortname));
        if (!$workflow) {
            throw new block_workflow_invalid_workflow_exception(get_string('invalidworkflow', 'block_workflow'));
        }
        return $this->_load($workflow);
    }

    /**
     * Load all workflows associated with a context.
     *
     * @param   int $contextid The ID of the context to load workflows for.
     * @return  array of stdClasses as returned by the database, most recent first.
     *           Each object has a single field id. This is also the array keys.
     * abstraction layer
     */
    public function load_context_workflows($contextid) {
        global $DB;
        $sql = "SELECT workflows.id
            FROM {block_workflow_step_states} states
            INNER JOIN {block_workflow_steps} steps ON steps.id = states.stepid
            INNER JOIN {block_workflow_workflows} workflows ON workflows.id = steps.workflowid
            WHERE states.contextid = ?
            GROUP BY workflows.id
            ORDER BY MAX(states.timemodified) DESC";
        $workflows = $DB->get_records_sql($sql, array($contextid));
        return $workflows;
    }

    /**
     * Function to create a new workflow
     *
     * @param   object  $workflow    stdClass containing the shortname, name and description.
     *                               descriptionformat, appliesto and obsolete can additionally be
     *                               specified
     * @param   boolean $createstep  Whether to create the first step
     * @param   boolean $makenamesunique Whether shortname and name should be unique
     * @return  The newly created block_workflow_workflow object
     * @throws  block_workflow_invalid_workflow_exception if the supplied shortname is already in use
     */
    public function create_workflow($workflow, $createstep = true, $makenamesunique = false) {
        global $DB;
        $transaction = $DB->start_delegated_transaction();

        // Check whether a shortname was specified.
        if (empty($workflow->shortname)) {
            $transaction->rollback(new block_workflow_invalid_workflow_exception('invalidshortname', 'block_workflow'));
        }

        // Check whether this shortname is already in use.
        if ($DB->get_record('block_workflow_workflows', array('shortname' => $workflow->shortname))) {
            if ($makenamesunique) {
                // Create new name by adding a digit and incrementing it if
                // name already has digit at the end.
                $shortnameclean = preg_replace('/\d+$/', '', $workflow->shortname);
                $sql = 'SELECT shortname FROM {block_workflow_workflows} WHERE shortname LIKE ? ORDER BY shortname DESC LIMIT 1';
                $lastshortname = $DB->get_record_sql($sql, array($shortnameclean."%"));
                if (preg_match('/\d+$/', $lastshortname->shortname)) {
                    $workflow->shortname = $lastshortname->shortname;
                    $workflow->shortname++;
                } else {
                    $workflow->shortname .= '1';
                }
            } else {
                $transaction->rollback(new block_workflow_invalid_workflow_exception('shortnameinuse', 'block_workflow'));
            }
        }

        // Check whether a valid name was specified.
        if (empty($workflow->name)) {
            $transaction->rollback(new block_workflow_invalid_workflow_exception('invalidworkflowname', 'block_workflow'));
        }

        // Check whether this name is already in use.
        if ($DB->get_record('block_workflow_workflows', array('name' => $workflow->name))) {
            if ($makenamesunique) {
                // Create new name by adding a digit and incrementing it if
                // name already has digit at the end.
                $nameclean = preg_replace('/\d+$/', '', $workflow->name);
                $sql = 'SELECT name FROM {block_workflow_workflows} WHERE name LIKE ? ORDER BY name DESC LIMIT 1';
                $lastname = $DB->get_record_sql($sql, array($nameclean."%"));
                if (preg_match('/\d+$/', $lastname->name)) {
                    $workflow->name = $lastname->name;
                    $workflow->name++;
                } else {
                    $workflow->name .= '1';
                }
            } else {
                $transaction->rollback(new block_workflow_invalid_workflow_exception('nameinuse', 'block_workflow'));
            }
        }

        // Set the default description.
        if (!isset($workflow->description)) {
            $workflow->description = '';
        }

        // Set the default descriptionformat.
        if (!isset($workflow->descriptionformat)) {
            $workflow->descriptionformat = FORMAT_HTML;
        }

        // Set the default appliesto to 'course'.
        if (!isset($workflow->appliesto)) {
            $workflow->appliesto = 'course';
        }

        // Check that the appliesto given is valid.
        $pluginlist = block_workflow_appliesto_list();
        if (!isset($pluginlist[$workflow->appliesto])) {
            $transaction->rollback(new block_workflow_invalid_workflow_exception('invalidappliestomodule', 'block_workflow'));
        }

        // Set the default obsolete value.
        if (!isset($workflow->obsolete)) {
            $workflow->obsolete = 0;
        }

        // Check that the obsolete value is valid.
        if ($workflow->obsolete != 0 && $workflow->obsolete != 1) {
            $transaction->rollback(new block_workflow_invalid_workflow_exception('invalidobsoletesetting', 'block_workflow'));
        }

        // Remove any atendgobacktostep -- the steps can't exist yet.
        if (isset($workflow->atendgobacktostep)) {
            $transaction->rollback(new block_workflow_invalid_workflow_exception(
                    get_string('atendgobackatworkflowcreate', 'block_workflow')));
        }

        // Check that each of the submitted data is a valid field.
        $expectedsettings = $this->expected_settings();
        foreach ((array) $workflow as $k => $v) {
            if (!in_array($k, $expectedsettings)) {
                $transaction->rollback(new block_workflow_invalid_workflow_exception(
                        get_string('invalidfield', 'block_workflow', $k)));
            }
        }

        // Create the workflow.
        $workflow->id = $DB->insert_record('block_workflow_workflows', $workflow);

        if ($createstep) {
            // Create the initial step using default options.
            $emptystep = new stdClass;
            $emptystep->workflowid          = $workflow->id;
            $emptystep->name                = get_string('defaultstepname',         'block_workflow');
            $emptystep->instructions        = get_string('defaultstepinstructions', 'block_workflow');
            $emptystep->instructionsformat  = FORMAT_HTML;
            $emptystep->onactivescript      = get_string('defaultonactivescript',   'block_workflow');
            $emptystep->oncompletescript    = get_string('defaultoncompletescript', 'block_workflow');

            $step = new block_workflow_step();
            $step->create_step($emptystep);
        }

        $transaction->allow_commit();

        // Reload the object using the returned workflow id and return it.
        return $this->load_workflow($workflow->id);
    }

    /**
     * Clone an existing workflow, substituting the data provided
     *
     * @param int $srcid The ID of the workflow to clone
     * @param Object  $data  An object containing any data to override
     * @return  The newly created block_workflow_workflow object
     * @throws  block_workflow_invalid_workflow_exception if the supplied shortname is already in use
     * @static
     */
    public static function clone_workflow($srcid, $data) {
        global $DB;
        $transaction = $DB->start_delegated_transaction();

        // Retrieve the source and copy it.
        $src = new block_workflow_workflow($srcid);
        $dst = new stdClass();

        // Copy the source based on the allowed settings.
        foreach ($src->expected_settings() as $k) {
            $dst->$k = $src->$k;
        }

        // Grab the description and format if submitted by a mform editor.
        if (isset($data->description_editor)) {
            $data->description          = $data->description_editor['text'];
            $data->descriptionformat    = $data->description_editor['format'];
            unset($data->description_editor);
        }

        // Merge any other new fields in.
        $dst = (object) array_merge((array) $src, (array) $data);

        // Check whether this shortname is already in use.
        if ($DB->get_record('block_workflow_workflows', array('shortname' => $dst->shortname))) {
            $transaction->rollback(new block_workflow_invalid_workflow_exception('shortnameinuse', 'block_workflow'));
        }

        // Create a clean record.
        // Note: we can't set the atendgobacktostep until we've copied the steps.
        $record = new stdClass();
        $record->shortname          = $dst->shortname;
        $record->name               = $dst->name;
        $record->description        = $dst->description;
        $record->descriptionformat  = $dst->descriptionformat;
        $record->appliesto          = $dst->appliesto;
        $record->obsolete           = $dst->obsolete;

        // Create the workflow.
        $record->id = $DB->insert_record('block_workflow_workflows', $record);

        // Clone any steps.
        foreach ($src->steps() as $step) {
            block_workflow_step::clone_step($step->id, $record->id);
        }

        // Set the atendgobacktostep now we have all of our steps.
        $update = new stdClass();
        $update->id                 = $record->id;
        $update->atendgobacktostep  = $dst->atendgobacktostep;
        $DB->update_record('block_workflow_workflows', $update);

        $transaction->allow_commit();

        // Reload the object using the returned workflow id and return it.
        return new block_workflow_workflow($record->id);
    }

    /**
     * Delete the currently loaded workflow
     *
     * Before the workflow is actually removed, {@link require_deletable} is
     * called to ensure that it is ready for removal.
     *
     * @return void
     */
    public function delete() {
        global $DB;
        $transaction = $DB->start_delegated_transaction();

        // Check whether we can remove this workflow.
        $this->require_deletable();

        // First remove any steps and their associated doers and todos.
        $steps = $DB->get_records('block_workflow_step_states', array('id' => $this->id), null, 'id');
        $steplist = array_map(create_function('$a', 'return $a->id;'), $steps);

        $DB->delete_records_list('block_workflow_step_doers', 'stepid', $steplist);
        $DB->delete_records_list('block_workflow_step_todos', 'stepid', $steplist);
        $DB->delete_records('block_workflow_steps', array('workflowid' => $this->id));

        // Finally, remove the workflow itself.
        $DB->delete_records('block_workflow_workflows', array('id' => $this->id));
        $transaction->allow_commit();
    }

    /**
     * Return an array of available workflows
     *
     * @param   String for  The context in which the workflow is for
     * @return  Array of stdClass objects as returned by the database
     *          abstraction layer
     */
    public static function available_workflows($for) {
        global $DB;
        $workflows = $DB->get_records('block_workflow_workflows',
                array('appliesto' => $for, 'obsolete' => 0), 'name');
        return $workflows;
    }

    /**
     * Add the currently loaded workflow to the specified context
     *
     * @param   int $contextid The ID of the context to assign
     */
    public function add_to_context($contextid) {
        global $DB, $USER;
        $transaction = $DB->start_delegated_transaction();

        // Grab a setp quickly.
        $step = new block_workflow_step();

        // Can only assign a context to a workflow if that context has no workflows assigned already.
        try {
            $step->load_active_step($contextid);
            $transaction->rollback(new block_workflow_exception(get_string('workflowalreadyassigned', 'block_workflow')));

        } catch (block_workflow_not_assigned_exception $e) {
            // A workflow shouldn't be assigned to this context already. A
            // context may only have one workflow assigned at a time.
        }

        // Workflows are associated using a step_state.
        // Retrieve the first step of this workflow.
        $step->load_workflow_stepno($this->id, 1);

        $state = new stdClass();
        $state->stepid              = $step->id;
        $state->timemodified        = time();
        $state->state               = BLOCK_WORKFLOW_STATE_ACTIVE;

        // Check whether this workflow has been previously assigned to this context.
        $existingstate = $DB->get_record('block_workflow_step_states',
                array('stepid' => $step->id, 'contextid' => $contextid));
        if ($existingstate) {
            $state->id              = $existingstate->id;
            $DB->update_record('block_workflow_step_states', $state);

        } else {
            // Create a new state to associate the workflow with the context.
            $state->comment             = '';
            $state->commentformat       = 1;
            $state->contextid           = $contextid;
            $state->id = $DB->insert_record('block_workflow_step_states', $state);
        }
        $state = new block_workflow_step_state($state->id);

        // Make a note of the change.
        $statechange = new stdClass;
        $statechange->stepstateid   = $state->id;
        $statechange->newstate      = BLOCK_WORKFLOW_STATE_ACTIVE;
        $statechange->userid        = $USER->id;
        $statechange->timestamp     = $state->timemodified; // Use the timestamp from $state to ensure the data matches.
        $DB->insert_record('block_workflow_state_changes', $statechange);

        // Process any required scripts for this state.
        $step->process_script($state);

        $transaction->allow_commit();

        // This is a workaround for a limitation of the message_send system.
        // This must be called outside of a transaction.
        block_workflow_command_email::message_send();

        return $state;
    }

    /**
     * Aborts the currently loaded workflow for the specified context, and
     * removes all traces of it being used
     *
     * @param   int $contextid The ID of the context to abort
     */
    public function remove_workflow($contextid) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        // Grab the current step_states and check that the workflow is assigned to this context.
        $stepstates = $this->step_states($contextid);
        $used = array_filter($stepstates, create_function('$a', 'return isset($a->stateid);'));
        if (count($used) == 0) {
            $transaction->rollback(new block_workflow_not_assigned_exception(
                    get_string('workflownotassigned', 'block_workflow', $this->name)));
        }

        // We can only abort if the workflow is assigned to this contextid.
        $state = new block_workflow_step_state();
        try {
            $state->require_active_state($contextid);

            // Abort the step by jumping to no step at all.
            $state->jump_to_step();

        } catch (block_workflow_not_assigned_exception $e) {
            // The workflow may be inactive so it's safe to catch this exception.
        }

        // Retrieve a list of the step_states.
        $statelist = array_map(create_function('$a', 'return $a->stateid;'), $stepstates);

        // Remove all of the state_change history.
        $DB->delete_records_list('block_workflow_state_changes', 'stepstateid', $statelist);

        // Remove the todo_done entries.
        $DB->delete_records_list('block_workflow_todo_done', 'stepstateid', $statelist);

        // Remove the states.
        $DB->delete_records('block_workflow_step_states', array('contextid' => $contextid));

        // These are all of the required steps for removing a workflow from a context, so commit.
        $transaction->allow_commit();
    }

    /**
     * Update the atendgobacktostep setting for the currently loaded
     * workflow
     *
     * @param   int $atendgobacktostep The step number to go back to at
     *          the end of the workflow, or null for the workflow to end
     * @return  An update block_workflow_workflow record as returned by
     *          {@link load_workflow}.
     * @throws  block_workflow_invalid_workflow_exception if the supplied stepno is
     *          invalid
     */
    public function atendgobacktostep($atendgobacktostep = null) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        // Check that we've been given a valid step to loop back to.
        if ($atendgobacktostep && !$DB->get_record('block_workflow_steps',
                array('workflowid' => $this->id, 'stepno' => $atendgobacktostep))) {
            $transaction->rollback(new block_workflow_invalid_workflow_exception('invalidstepno', 'block_workflow'));
        }

        // Update the workflow record.
        $update = new stdClass();
        $update->atendgobacktostep  = $atendgobacktostep;
        $update->id                 = $this->id;
        $DB->update_record('block_workflow_workflows', $update);

        $transaction->allow_commit();

        // Return the updated workflow object.
        return $this->load_workflow($this->id);
    }

    /**
     * Toggle the obselete flag for the currently loaded workflow
     *
     * @return  An update block_workflow_workflow record as returned by
     *          {@link load_workflow}.
     */
    public function toggle() {
        global $DB;
        $transaction = $DB->start_delegated_transaction();

        $update = new stdClass();
        $update->id = $this->id;

        // Switch the obsolete state of the workflow.
        if ($this->obsolete == BLOCK_WORKFLOW_ENABLED) {
            $update->obsolete = BLOCK_WORKFLOW_OBSOLETE;
        } else {
            $update->obsolete = BLOCK_WORKFLOW_ENABLED;
        }

        // Update the record.
        $DB->update_record('block_workflow_workflows', $update);
        $transaction->allow_commit();

        // Return the updated workflow object.
        return $this->load_workflow($this->id);
    }

    /**
     * Determine whether the currently loaded workflow is in use or not,
     * and thus whether it can be removed.
     *
     * A workflow may not be removed if it is currently in use, or has
     * ever been used and thus has state information
     *
     * @param   int $id The ID of the workflow (defaults to the id of the current workflow)
     * @return  bool whether the workflow may be deleted.
     */
    public function is_deletable($id = null) {
        return self::is_workflow_deletable($this->id);
    }

    /**
     * Determine whether a workflow is in use or not, and thus whether it can be removed.
     *
     * Workflows can only be removed if they are not in use.
     *
     * @param   int $id The ID of the workflow.
     * @return  bool whether the workflow may be deleted.
     */
    public static function is_workflow_deletable($id) {
        return self::in_use_by($id) == 0;
    }

    /**
     * Convenience function to require that a workflow is deletable
     *
     * @param   int $id The ID of the workflow (defaults to the id of the current workflow)
     * @throws  block_workflow_exception If the workflow is currently in use
     */
    public function require_deletable($id = null) {
        if ($id === null) {
            // Get the current workflow id.
            $id = $this->id;
        }

        if (!$this->is_deletable($id)) {
            throw new block_workflow_exception(get_string('cannotremoveworkflowinuse', 'block_workflow'));
        }
        return true;
    }

    /**
     * Determine how many locations the specified worklow is in use.
     *
     * @param   int  $id The ID of the workflow to check.
     * @param   bool $activeonly Include active states only?
     * @return  int  How many places the workflow is in use.
     */
    public static function in_use_by($id, $activeonly = false) {
        global $DB;

        // Determine whether the workflow is currently assigned to any
        // step_states, regardless of whether those states are are active
        // or not.
        $sql = "SELECT COUNT(w.id)
                FROM {block_workflow_workflows} w
                INNER JOIN {block_workflow_steps} s ON s.workflowid = w.id
                INNER JOIN {block_workflow_step_states} st ON st.stepid = s.id
                WHERE w.id = ?
        ";
        if ($activeonly) {
            $sql .= " AND st.state IN ('active')";
        }
        return $DB->count_records_sql($sql, array($id));
    }

    /**
     * Renumber the steps of the currently loaded workflow from 1 to n
     *
     * If a step has been removed, or stepno data has somehow entered an inconsistent state, then
     * this function will re-number the steps based upon their current stepno.
     *
     * Database constraints prevent steps with a null stepno, or multiple steps with the same stepno.
     *
     * return   int The number of steps
     */
    public function renumber_steps($from = null, $moveup = 0) {
        global $DB;
        $transaction = $DB->start_delegated_transaction();

        if ($from == null) {
            $from = 0;
        }

        // Retrieve the list of current steps ordered ascendingly by their stepno ASC.
        $sql = 'SELECT id,stepno
                FROM {block_workflow_steps}
                WHERE workflowid = ? AND stepno > ?
                ORDER BY stepno ASC';
        $steps = $DB->get_records_sql($sql, array($this->id, $from));

        // Check whether the steps are incorrectly ordered in any way.
        $sql = 'SELECT COUNT(stepno) AS count, MAX(stepno) AS max, MIN(stepno) AS min
                FROM {block_workflow_steps}
                WHERE workflowid = ?';
        $checksteps = $DB->get_record_sql($sql, array($this->id));

        if (($checksteps->count != $checksteps->max) || ($checksteps->min != 0)) {
            $topstep = $checksteps->max + 1;
            foreach ($steps as $step) {
                $topstep++;
                $step->stepno = $topstep;
                $DB->update_record('block_workflow_steps', $step);
            }
        }

        // Renumber the steps starting from count($steps) + $from + 1 and going down.
        $topstep = count($steps) + $from + $moveup;

        // Pop elements off the *end* of the array to give them in reverse order.
        while ($step = array_pop($steps)) {
            $step->stepno = $topstep;
            $DB->update_record('block_workflow_steps', $step);
            $topstep--;
        }

        $transaction->allow_commit();

        return $from;
    }

    /**
     * Return a list of the steps in a workflow
     *
     * @return  Array of stdClass objects as returned by the database
     *          abstraction layer
     */
    public function steps() {
        global $DB;

        // Retrieve all of the steps for this workflowid, in order of their
        // ascending stepno.
        $steps = $DB->get_records('block_workflow_steps', array('workflowid' => $this->id), 'stepno ASC');

        return $steps;
    }

    /**
     * The context level for this workflow
     *
     * @return  constant Either CONTEXT_COURSE or CONTEXT_MODULE
     */
    public function context() {
        if ($this->appliesto == 'course') {
            return CONTEXT_COURSE;
        } else {
            // If this workflow applies does not apply to a course, then it
            // must be a module.
            return CONTEXT_MODULE;
        }
    }

    /**
     * Return a list of steps with their current state for a specific
     * context
     *
     * @param   int $contextid The ID of the context to retrieve data
     *          for
     * @return  Array of stdClass objects as returned by the database
     *          abstraction layer
     */
    public function step_states($contextid) {
        global $DB;

        // The 'complete' subquery below is written in a more complex way than
        // necessary to work around a MyQSL short-coming.
        // (It was not possible to refer to states.id in an ON clause, only in a WHERE clause.)
        $sql = "SELECT steps.id,
                       steps.stepno,
                       steps.name,
                       steps.instructions,
                       steps.instructionsformat,
                       states.id AS stateid,
                       states.state,
                       states.timemodified,
                       states.comment,
                       states.commentformat,
                       states.contextid,
                       " . $DB->sql_fullname('u.firstname', 'u.lastname') . " AS modifieduser,
                       (
                             SELECT CASE WHEN COUNT(todos.id) > 0 THEN
                                        100.0 * COUNT(done.id) / COUNT(todos.id)
                                    ELSE
                                        NULL
                                    END
                               FROM {block_workflow_step_states} inner_states
                               JOIN {block_workflow_steps}       inner_steps  ON inner_steps.id   = inner_states.stepid
                               JOIN {block_workflow_step_todos}  todos        ON todos.stepid     = inner_steps.id
                          LEFT JOIN {block_workflow_todo_done}   done         ON done.steptodoid  = todos.id
                                                                             AND done.stepstateid = inner_states.id
                              WHERE inner_states.id = states.id
                       ) AS complete

                  FROM {block_workflow_workflows}   workflows
                  JOIN {block_workflow_steps}         steps  ON steps.workflowid = workflows.id
             LEFT JOIN {block_workflow_step_states}   states ON states.stepid = steps.id
                                                            AND states.contextid = :contextid
             LEFT JOIN {block_workflow_state_changes} wsc    ON wsc.id = (
                               SELECT MAX(iwsc.id)
                                 FROM {block_workflow_state_changes} iwsc
                                WHERE iwsc.stepstateid = states.id
                       )
             LEFT JOIN {user} u ON u.id = wsc.userid

                 WHERE workflows.id = :workflowid

        ORDER BY steps.stepno";

        $steps = $DB->get_records_sql($sql, array('contextid' => $contextid, 'workflowid' => $this->id));
        return $steps;
    }

    /**
     * Update the current workflow with the data provided
     *
     * @param   stdClass $data A stdClass containing the fields to update
     *          for this workflow. The id cannot be changed, or specified
     *          in this data set
     * @return  An update block_workflow_workflow record as returned by
     *          {@link load_workflow}.
     */
    public function update($data) {
        global $DB;

        // Retrieve the id for the current workflow.
        $data->id = $this->id;

        $transaction = $DB->start_delegated_transaction();

        // Check whether this shortname is already in use.
        if (isset($data->shortname) &&
                ($id = $DB->get_field('block_workflow_workflows', 'id', array('shortname' => $data->shortname)))) {
            if ($id != $data->id) {
                $transaction->rollback(new block_workflow_invalid_workflow_exception('shortnameinuse', 'block_workflow'));
            }
        }

        // Check that the appliesto given is valid.
        if (isset($data->appliesto)) {
            $pluginlist = block_workflow_appliesto_list();
            if (!isset($pluginlist[$data->appliesto])) {
                $transaction->rollback(new block_workflow_invalid_workflow_exception('invalidappliestomodule', 'block_workflow'));
            }
        }

        // Check that the obsolete value is valid.
        if (isset($data->obsolete) && ($data->obsolete != 0 && $data->obsolete != 1)) {
            $transaction->rollback(new block_workflow_invalid_workflow_exception('invalidobsoletesetting', 'block_workflow'));
        }

        // Check the validity of the atendgobacktostep if specified.
        if (isset($data->atendgobacktostep)) {
            $step = new block_workflow_step();
            try {
                $step->load_workflow_stepno($this->id, $data->atendgobacktostep);
            } catch (Exception $e) {
                $transaction->rollback($e);
            }
        }

        // Check that each of the submitted data is a valid field.
        $expectedsettings = $this->expected_settings();
        foreach ((array) $data as $k => $v) {
            if (!in_array($k, $expectedsettings)) {
                $transaction->rollback(new block_workflow_invalid_workflow_exception(
                        get_string('invalidfield', 'block_workflow', $k)));
            }
        }

        // Update the record.
        $DB->update_record('block_workflow_workflows', $data);

        $transaction->allow_commit();

        // Return the updated workflow object.
        return $this->load_workflow($data->id);
    }
}
