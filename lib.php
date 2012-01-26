<?php

/**
 * Workflow block libraries
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}

/**
 * An active state for a step_state
 */
define('BLOCK_WORKFLOW_STATE_ACTIVE',       'active');

/**
 * A completed state for a step_state
 */
define('BLOCK_WORKFLOW_STATE_COMPLETED',    'completed');

/**
 * An aborted state for a step_state
 */
define('BLOCK_WORKFLOW_STATE_ABORTED',      'aborted');

/**
 * The enabled state for a workflow
 */
define('BLOCK_WORKFLOW_ENABLED',            0);

/**
 * The obsolste state for a workflow
 */
define('BLOCK_WORKFLOW_OBSOLETE',           1);

/**
 * The maximum comment length to be disapled in block
 */
define('BLOCK_WORKFLOW_MAX_COMMENT_LENGTH', 200);

/**
 * Workflow class
 *
 * Class for handling workflow operations, and retrieving information from a workflow
 *
 * @package    block
 * @subpackage workflow
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
     * Load all workflows associated with the context ID
     *
     * @param   int $contextid The ID of the context to load workflows
     *          for
     * @return  An array of stdClasses as returned by the database
     * abstraction layer
     */
    public function load_context_workflows($contextid) {
        global $DB;
        $sql = "SELECT DISTINCT workflows.id
            FROM {block_workflow_step_states} states
            INNER JOIN {block_workflow_steps} steps ON steps.id = states.stepid
            INNER JOIN {block_workflow_workflows} workflows ON workflows.id = steps.workflowid
            WHERE states.contextid = ?";
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

        // Check whether a shortname was specified
        if (empty($workflow->shortname)) {
            $transaction->rollback(new block_workflow_invalid_workflow_exception('invalidshortname', 'block_workflow'));
        }

        // Check whether this shortname is already in use
        if ($DB->get_record('block_workflow_workflows', array('shortname' => $workflow->shortname))) {
            if ($makenamesunique) {
                // Create new name by adding a digit and incrementing it if
                // name already has digit at the end
                $shortnameclean = preg_replace('/\d+$/', '', $workflow->shortname);
                $sql = 'SELECT shortname FROM {block_workflow_workflows} WHERE shortname LIKE ? ORDER BY shortname DESC LIMIT 1';
                $lastshortname = $DB->get_record_sql($sql, array($shortnameclean."%"));
                if (preg_match('/\d+$/', $lastshortname->shortname)) {
                    $workflow->shortname = $lastshortname->shortname;
                    $workflow->shortname++;
                }
                else {
                    $workflow->shortname .= '1';
                }
            }
            else {
                $transaction->rollback(new block_workflow_invalid_workflow_exception('shortnameinuse', 'block_workflow'));
            }
        }

        // Check whether a valid name was specified
        if (empty($workflow->name)) {
            $transaction->rollback(new block_workflow_invalid_workflow_exception('invalidworkflowname', 'block_workflow'));
        }

        // Check whether this name is already in use
        if ($DB->get_record('block_workflow_workflows', array('name' => $workflow->name))) {
            if ($makenamesunique) {
                // Create new name by adding a digit and incrementing it if
                // name already has digit at the end
                $nameclean = preg_replace('/\d+$/', '', $workflow->name);
                $sql = 'SELECT name FROM {block_workflow_workflows} WHERE name LIKE ? ORDER BY name DESC LIMIT 1';
                $lastname = $DB->get_record_sql($sql, array($nameclean."%"));
                if (preg_match('/\d+$/', $lastname->name)) {
                    $workflow->name = $lastname->name;
                    $workflow->name++;
                }
                else {
                    $workflow->name .= '1';
                }
            }
            else {
                $transaction->rollback(new block_workflow_invalid_workflow_exception('nameinuse', 'block_workflow'));
            }
        }


        // Set the default description
        if (!isset($workflow->description)) {
            $workflow->description = '';
        }

        // Set the default descriptionformat
        if (!isset($workflow->descriptionformat)) {
            $workflow->descriptionformat = FORMAT_PLAIN;
        }

        // Set the default appliesto to 'course'
        if (!isset($workflow->appliesto)) {
            $workflow->appliesto = 'course';
        }

        // Check that the appliesto given is valid
        $pluginlist = block_workflow_appliesto_list();
        if (!isset($pluginlist[$workflow->appliesto])) {
            $transaction->rollback(new block_workflow_invalid_workflow_exception('invalidappliestomodule', 'block_workflow'));
        }

        // Set the default obsolete value
        if (!isset($workflow->obsolete)) {
            $workflow->obsolete = 0;
        }

        // Check that the obsolete value is valid
        if ($workflow->obsolete != 0 && $workflow->obsolete != 1) {
            $transaction->rollback(new block_workflow_invalid_workflow_exception('invalidobsoletesetting', 'block_workflow'));
        }

        // Remove any atendgobacktostep -- the steps can't exist yet
        if (isset($workflow->atendgobacktostep)) {
            $transaction->rollback(new block_workflow_invalid_workflow_exception(get_string('atendgobackatworkflowcreate', 'block_workflow')));
        }

        // Check that each of the submitted data is a valid field
        $expectedsettings = $this->expected_settings();
        foreach ((array) $workflow as $k => $v) {
            if (!in_array($k, $expectedsettings)) {
                $transaction->rollback(new block_workflow_invalid_workflow_exception(get_string('invalidfield', 'block_workflow', $k)));
            }
        }

        // Create the workflow
        $workflow->id = $DB->insert_record('block_workflow_workflows', $workflow);

        if ($createstep) {
            // Create the initial step using default options
            $emptystep = new stdClass;
            $emptystep->workflowid          = $workflow->id;
            $emptystep->name                = get_string('defaultstepname',         'block_workflow');
            $emptystep->instructions        = get_string('defaultstepinstructions', 'block_workflow');
            $emptystep->instructionsformat  = FORMAT_PLAIN;
            $emptystep->onactivescript      = get_string('defaultonactivescript',   'block_workflow');
            $emptystep->oncompletescript    = get_string('defaultoncompletescript', 'block_workflow');

            $step = new block_workflow_step();
            $step->create_step($emptystep);
        }

        $transaction->allow_commit();

        // Reload the object using the returned workflow id and return it
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

        // Retrieve the source and copy it
        $src = new block_workflow_workflow($srcid);

        // Copy the source based on the allowed settings
        foreach (self::expected_settings() as $k) {
            $dst->$k = $src->$k;
        }

        // Grab the description and format if submitted by a mform editor
        if (isset($data->description_editor)) {
            $data->description          = $data->description_editor['text'];
            $data->descriptionformat    = $data->description_editor['format'];
            unset($data->description_editor);
        }

        // Merge any other new fields in
        $dst = (object) array_merge((array) $src, (array) $data);

        // Check whether this shortname is already in use
        if ($DB->get_record('block_workflow_workflows', array('shortname' => $dst->shortname))) {
            $transaction->rollback(new block_workflow_invalid_workflow_exception('shortnameinuse', 'block_workflow'));
        }

        // Create a clean record
        // Note: we can't set the atendgobacktostep until we've copied the steps
        $record = new stdClass();
        $record->shortname          = $dst->shortname;
        $record->name               = $dst->name;
        $record->description        = $dst->description;
        $record->descriptionformat  = $dst->descriptionformat;
        $record->appliesto          = $dst->appliesto;
        $record->obsolete           = $dst->obsolete;

        // Create the workflow
        $record->id = $DB->insert_record('block_workflow_workflows', $record);

        // Clone any steps
        foreach ($src->steps() as $step) {
            block_workflow_step::clone_step($step->id, $record->id);
        }

        // Set the atendgobacktostep now we have all of our steps
        $update = new stdClass();
        $update->id                 = $record->id;
        $update->atendgobacktostep  = $dst->atendgobacktostep;
        $DB->update_record('block_workflow_workflows', $update);

        $transaction->allow_commit();

        // Reload the object using the returned workflow id and return it
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

        // Check whether we can remove this workflow
        $this->require_deletable();

        // First remove any steps and their associated doers and todos
        $steps = $DB->get_records('block_workflow_step_states', array('id' => $this->id), null, 'id');
        $steplist = array_map(create_function('$a', 'return $a->id;'), $steps);

        $DB->delete_records_list('block_workflow_step_doers', 'stepid', $steplist);
        $DB->delete_records_list('block_workflow_step_todos', 'stepid', $steplist);
        $DB->delete_records('block_workflow_steps', array('workflowid' => $this->id));

        // Finally, remove the workflow itself
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
    public function available_workflows($for) {
        global $DB;
        $workflows = $DB->get_records('block_workflow_workflows', array('appliesto' => $for, 'obsolete' => 0));
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

        // Grab a setp quickly
        $step = new block_workflow_step();

        // Can only assign a context to a workflow if that context has no workflows assigned already
        try {
            $step->load_active_step($contextid);
            $transaction->rollback(new block_workflow_exception(get_string('workflowalreadyassigned', 'block_workflow')));

        } catch (block_workflow_not_assigned_exception $e) {
            // A workflow shouldn't be assigned to this context already. A
            // context may only have one workflow assigned at a time
        }

        // Workflows are associated using a step_state.
        // Retrieve the first step of this workflow
        $step->load_workflow_stepno($this->id, 1);

        $state = new stdClass();
        $state->stepid              = $step->id;
        $state->timemodified        = time();
        $state->state               = BLOCK_WORKFLOW_STATE_ACTIVE;

        // Check whether this workflow has been previously assigned to this
        // context
        $existingstate = $DB->get_record('block_workflow_step_states',
                array('stepid' => $step->id, 'contextid' => $contextid));
        if ($existingstate) {
            $state->id              = $existingstate->id;
            $DB->update_record('block_workflow_step_states', $state);

        } else {
            // Create a new state to associate the workflow with the context
            $state->comment             = '';
            $state->commentformat       = 1;
            $state->contextid           = $contextid;
            $state->id = $DB->insert_record('block_workflow_step_states', $state);
        }
        $state = new block_workflow_step_state($state->id);

        // Make a note of the change
        $statechange = new stdClass;
        $statechange->stepstateid   = $state->id;
        $statechange->newstate      = BLOCK_WORKFLOW_STATE_ACTIVE;
        $statechange->userid        = $USER->id;
        $statechange->timestamp     = $state->timemodified;  // Use the timestamp from $state to ensure the data matches
        $DB->insert_record('block_workflow_state_changes', $statechange);

        // Process any required scripts for this state
        $step->process_script($state);

        $transaction->allow_commit();

        // This is a workaround for a limitation of the message_send system
        // This must be called outside of a transaction
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

        // Grab the current step_states and check that the workflow is assigned to this context
        $step_states = $this->step_states($contextid);
        $used = array_filter($step_states, create_function('$a', 'return isset($a->stateid);'));
        if (count($used) == 0) {
            $transaction->rollback(new block_workflow_not_assigned_exception(get_string('workflownotassigned', 'block_workflow', $this->name)));
        }

        // We can only abort if the workflow is assigned to this contextid
        $state = new block_workflow_step_state();
        try {
            $state->require_active_state($contextid);

            // Abort the step by jumping to no step at all
            $state->jump_to_step();

        } catch (block_workflow_not_assigned_exception $e) {
            // The workflow may be inactive so it's safe to catch this exception
        }

        // Retrieve a list of the step_states
        $statelist = array_map(create_function('$a', 'return $a->stateid;'), $step_states);

        // Remove all of the state_change history
        $DB->delete_records_list('block_workflow_state_changes', 'stepstateid', $statelist);

        // Remove the todo_done entries
        $DB->delete_records_list('block_workflow_todo_done', 'stepstateid', $statelist);

        // Remove the states
        $DB->delete_records('block_workflow_step_states', array('contextid' => $contextid));

        // These are all of the required steps for removing a workflow from a context, so commit
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

        // Check that we've been given a valid step to loop back to
        if ($atendgobacktostep && !$DB->get_record('block_workflow_steps',
                array('workflowid' => $this->id, 'stepno' => $atendgobacktostep))) {
            $transaction->rollback(new block_workflow_invalid_workflow_exception('invalidstepno', 'block_workflow'));
        }

        // Update the workflow record
        $update = new stdClass();
        $update->atendgobacktostep  = $atendgobacktostep;
        $update->id                 = $this->id;
        $DB->update_record('block_workflow_workflows', $update);

        $transaction->allow_commit();

        // Return the updated workflow object
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

        // Switch the obsolete state of the workflow
        if ($this->obsolete == BLOCK_WORKFLOW_ENABLED) {
            $update->obsolete = BLOCK_WORKFLOW_OBSOLETE;
        }
        else {
            $update->obsolete = BLOCK_WORKFLOW_ENABLED;
        }

        // Update the record
        $DB->update_record('block_workflow_workflows', $update);
        $transaction->allow_commit();

        // Return the updated workflow object
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
     * @return  Boolean Whether the workflow may be deleted or not
     */
    public function is_deletable($id = null) {
        global $DB;

        if ($id === null) {
            // Get the current workflow id
            $id = $this->id;
        }

        // Count the uses
        $count = self::in_use_by($id);

        return (!$count > 0);
    }

    /**
     * Convenience function to require that a workflow is deletable
     *
     * @param   int $id The ID of the workflow (defaults to the id of the current workflow)
     * @throws  block_workflow_exception If the workflow is currently in use
     */
    public function require_deletable($id = null) {
        if ($id === null) {
            // Get the current workflow id
            $id = $this->id;
        }

        if (!$this->is_deletable($id)) {
            throw new block_workflow_exception(get_string('cannotremoveworkflowinuse', 'block_workflow'));
        }
        return true;
    }

    /**
     * Determine how many locations the currently loaded, or the specified worklow is in use.
     *
     * @param   int     $id The ID of the workflow if the function is
     *          called in a static context
     * @param   boolean $activeonly Include active states only?
     * @return  int     How many places the workflow is in use
     */
    public function in_use_by($id = null, $activeonly = false) {
        global $DB;

        // If no ID was specified, use the ID from the currently loaded
        // object
        if (!$id) {
            $id = $this->id;
        }

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

        // Retrieve the list of current steps ordered ascendingly by their stepno ASC
        $sql = 'SELECT id,stepno FROM {block_workflow_steps} WHERE workflowid = ? AND stepno > ? ORDER BY stepno ASC';
        $steps = $DB->get_records_sql($sql, array($this->id, $from));

        // Check whether the steps are incorrectly ordered in any way
        $sql = 'SELECT COUNT(stepno) AS count, MAX(stepno) AS max, MIN(stepno) AS min FROM {block_workflow_steps} WHERE workflowid = ?';
        $checksteps = $DB->get_record_sql($sql, array($this->id));

        if (($checksteps->count != $checksteps->max) || ($checksteps->min != 0)) {
            $topstep = $checksteps->max + 1;
            foreach ($steps as $step) {
                $topstep++;
                $step->stepno = $topstep;
                $DB->update_record('block_workflow_steps', $step);
            }
        }

        // Renumber the steps starting from count($steps) + $from + 1 and going down
        $topstep = count($steps) + $from + $moveup;

        // Pop elements off the *end* of the array to give them in reverse order
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
        // ascending stepno
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
        }
        else {
            // If this workflow applies does not apply to a course, then it
            // must be a module
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
        $sql = "SELECT
                    steps.id, steps.stepno, steps.name, steps.instructions, steps.instructionsformat,
                    states.id AS stateid,
                    states.state, states.timemodified, states.comment, states.commentformat, states.contextid,
                    (
                        SELECT
                            CASE WHEN COUNT(todos.id) > 0 THEN
                                100.0 * COUNT(done.id) / COUNT(todos.id)
                            ELSE
                                NULL
                            END
                        FROM
                            {block_workflow_step_todos} AS todos
                        LEFT JOIN
                            {block_workflow_todo_done} AS done
                        ON done.steptodoid = todos.id AND done.stepstateid = states.id
                        WHERE stepid = steps.id
                    ) AS complete,
                    (
                        SELECT
                            " . $DB->sql_fullname('u.firstname', 'u.lastname') . "
                        FROM {user} u
                        JOIN {block_workflow_state_changes} wsc ON wsc.userid = u.id
                        WHERE wsc.stepstateid = states.id
                          AND wsc.id = (SELECT MAX(id) FROM {block_workflow_state_changes}
                                        WHERE stepstateid = states.id)
                    ) AS modifieduser
                FROM {block_workflow_workflows} AS workflows
                INNER JOIN {block_workflow_steps} AS steps ON steps.workflowid = workflows.id
                LEFT  JOIN {block_workflow_step_states} AS states ON states.stepid = steps.id AND states.contextid = ?
                WHERE workflows.id = ?
                ORDER BY steps.stepno ASC";
        $steps = $DB->get_records_sql($sql, array($contextid, $this->id));
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

        // Retrieve the id for the current workflow
        $data->id = $this->id;

        $transaction = $DB->start_delegated_transaction();

        // Check whether this shortname is already in use
        if (isset($data->shortname) && ($id = $DB->get_field('block_workflow_workflows', 'id', array('shortname' => $data->shortname)))) {
            if ($id != $data->id) {
                $transaction->rollback(new block_workflow_invalid_workflow_exception('shortnameinuse', 'block_workflow'));
            }
        }

        // Check that the appliesto given is valid
        if (isset($data->appliesto)) {
            $pluginlist = block_workflow_appliesto_list();
            if (!isset($pluginlist[$data->appliesto])) {
                $transaction->rollback(new block_workflow_invalid_workflow_exception('invalidappliestomodule', 'block_workflow'));
            }
        }

        // Check that the obsolete value is valid
        if (isset($data->obsolete) && ($data->obsolete != 0 && $data->obsolete != 1)) {
            $transaction->rollback(new block_workflow_invalid_workflow_exception('invalidobsoletesetting', 'block_workflow'));
        }

        // Check the validity of the atendgobacktostep if specified
        if (isset($data->atendgobacktostep)) {
            $step = new block_workflow_step();
            try {
                $step->load_workflow_stepno($this->id, $data->atendgobacktostep);
            } catch (Exception $e) {
                $transaction->rollback($e);
            }
        }

        // Check that each of the submitted data is a valid field
        $expectedsettings = $this->expected_settings();
        foreach ((array) $data as $k => $v) {
            if (!in_array($k, $expectedsettings)) {
                $transaction->rollback(new block_workflow_invalid_workflow_exception(get_string('invalidfield', 'block_workflow', $k)));
            }
        }

        // Update the record
        $DB->update_record('block_workflow_workflows', $data);

        $transaction->allow_commit();

        // Return the updated workflow object
        return $this->load_workflow($data->id);
    }
}

/**
 * step class
 *
 * Class for handling workflow step operations
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @property-read int       $id                 The ID of the step
 * @property-read int       $workflowid         The ID of the owner workflow
 * @property-read int       $stepno             The step number
 * @property-read string    $name               The full name of the step
 * @property-read string    $instructions       The formatted instructions of the step
 * @property-read int       $instructionsformat The format of the instructions field
 * @property-read string    $onactivescript     The script for processing when the step is made active
 * @property-read string    $oncompletescript   The script for processing when the step is made complete
 */
class block_workflow_step {
    private $step       = null;
    private $workflow   = null;
    private $todos      = null;

    public $id;
    public $workflowid;
    public $stepno;
    public $name;
    public $instructions;
    public $instructionsformat;
    public $onactivescript;
    public $oncompletescript;

    /**
     * Constructor to obtain a step
     *
     * See documentation for {@link load_step} for further information.
     *
     * @param int $stepid The ID of the step to load
     * @return Object The step
     */
    public function __construct($stepid = null) {
        if ($stepid) {
            $this->load_step($stepid);
        }
    }

    /**
     * Private function to overload the current class instance with a
     * step object
     *
     * @param   stdClass $step Database record to overload into the
     * object   instance
     * @return  The instantiated block_workflow_step object
     * @access  private
     */
    private function _load($step) {
        $this->id                   = $step->id;
        $this->workflowid           = $step->workflowid;
        $this->stepno               = $step->stepno;
        $this->name                 = $step->name;
        $this->instructions         = $step->instructions;
        $this->instructionsformat   = $step->instructionsformat;
        $this->onactivescript       = $step->onactivescript;
        $this->oncompletescript     = $step->oncompletescript;
        return $this;
    }

    /**
     * A list of expected settings for a step
     *
     * @return  array   The list of available settings
     */
    public function expected_settings() {
        return array(
            'id',
            'workflowid',
            'stepno',
            'name',
            'instructions',
            'instructionsformat',
            'onactivescript',
            'oncompletescript'
        );
    }

    /**
     * Load a step given it's ID
     *
     * @param   int $stepid The ID of the step to load
     * @return  The instantiated block_workflow_step object
     * @throws  block_workflow_invalid_step_exception if the id is not found
     */
    public function load_step($stepid) {
        global $DB;
        $step = $DB->get_record('block_workflow_steps', array('id' => $stepid));
        if (!$step) {
            throw new block_workflow_invalid_step_exception(get_string('noactiveworkflow', 'block_workflow'));
        }
        return $this->_load($step);
    }

    /**
     * Load a step given a workflowid and step number
     *
     * @param   int $workflowid The ID of the workflow this step belongs to
     * @param   int $stepno The number of the step in the workflow
     * @return  The instantiated block_workflow_step object
     * @throws  block_workflow_invalid_step_exception if the id is not found
     */
    public function load_workflow_stepno($workflowid, $stepno) {
        global $DB;
        $step = $DB->get_record('block_workflow_steps',
                array('workflowid' => $workflowid, 'stepno' => $stepno));
        if (!$step) {
            throw new block_workflow_invalid_step_exception(get_string('invalidworkflowstepno', 'block_workflow'));
        }
        return $this->_load($step);
    }

    /**
     * Function to create a new step
     *
     * If a step number is specified, this will be ignored. The step number
     * will be calculated instead by taking the highest stepno for the
     * workflow and adding to it, or setting it to the first step as
     * appropriate.
     *
     * @param   stdClass $step containing the workflowid, name, instructions,
     *          instructions, onactivescript, and oncompletescript.
     * @param   integer  $beforeafter   an optional parameter to suggest what step number to use. If
     *                   a positive number is suggested, then the stepno after that number will be
     *                   used, if a negative number is used, then that step numbrer is used, by
     *                   default or if parameter is 0, the next possible step number is used.
     * @return  The newly created block_workflow_step object
     */
    public function create_step($step, $beforeafter = 0) {
        global $DB;

        // Set the default onactivescript and oncompletescript
        if (!isset($step->onactivescript)) {
            $step->onactivescript = '';
        }
        if (!isset($step->oncompletescript)) {
            $step->oncompletescript = '';
        }

        // Set the default instructionsformat
        if (!isset($step->instructionsformat)) {
            $step->instructionsformat = FORMAT_PLAIN;
        }

        $transaction = $DB->start_delegated_transaction();

        // Check that the workflowid was specified
        if (!isset($step->workflowid)) {
            $transaction->rollback(new block_workflow_invalid_step_exception('invalidworkflowid', 'block_workflow'));
        }

        // Check for a step name
        if (!isset($step->name) || empty($step->name)) {
            $transaction->rollback(new block_workflow_invalid_step_exception('invalidname', 'block_workflow'));
        }

        // Check for instructions
        if (!isset($step->instructions)) {
            $transaction->rollback(new block_workflow_invalid_step_exception('invalidinstructions', 'block_workflow'));
        }

        // We don't allow a stepid to be specified at create time
        unset($step->id);

        // This has the effect of checking the specified workflowid is valid
        try {
            $this->workflow = new block_workflow_workflow($step->workflowid);
        } catch (Exception $e) {
            $transaction->rollback($e);
        }

        if ($beforeafter !== 0) {
            if ($beforeafter < 0) {
                // A negative beforeafter is the same as $beforeafter - 1
                $beforeafter = abs($beforeafter) - 1;
            }
            // Renumber the steps from $beforeafter
            // Placing this step after the specified step
            $this->workflow->renumber_steps($beforeafter, 1);
            $step->stepno = $beforeafter + 1;
        }
        else {
            // Retrieve the stepno from the final step for this workflow
            $sql = 'SELECT stepno FROM {block_workflow_steps} WHERE workflowid = ? ORDER BY stepno DESC LIMIT 1';
            $step->stepno = $DB->get_field_sql($sql, array($step->workflowid));

            if ($step->stepno) {
                // If there's already a step on this workflow, add to that step
                // number
                $step->stepno++;
            }
            else {
                // No steps yet for this workflow, this is step 1
                $step->stepno = 1;
            }
        }

        // Check that each of the submitted data is a valid field
        $expectedsettings = $this->expected_settings();
        foreach ((array) $step as $k => $v) {
            if (!in_array($k, $expectedsettings)) {
                $transaction->rollback(new block_workflow_invalid_step_exception(get_string('invalidfield', 'block_workflow', $k)));
            }
        }

        // Validate any onactivescript and oncompletescript
        if (isset($step->onactivescript)) {
            $result = $this->validate_script($step->onactivescript);
            if ($result->errors) {
                $transaction->rollback(new block_workflow_invalid_command_exception(
                        get_string('invalidscript', 'block_workflow', $result->errors[0])));
            }
        }

        if (isset($step->oncompletescript)) {
            $result = $this->validate_script($step->oncompletescript);
            if ($result->errors) {
                $transaction->rollback(new block_workflow_invalid_command_exception(
                        get_string('invalidscript', 'block_workflow', $result->errors[0])));
            }
        }


        // Create the step
        $step->id = $DB->insert_record('block_workflow_steps', $step);

        $transaction->allow_commit();

        // Reload the object using the returned step id and return it
        return $this->load_step($step->id);
    }

    /**
     * Clone an existing step. If a workflowid is specified, attach it to
     * that workflow instead of the same workflow.
     *
     * This function passes the actual step creation to {@link
     * create_step}. If a stepno is specified, this will be overridden by
     * create_step as documented.
     *
     * clone_step returns a new object without altering the currently
     * loaded object.
     *
     * @param   int $srcid The ID of the step to clone
     * @param   int $workflowid of the workflow to place this step into. If
     *          no workflowid is specified, the step is placed within the same
     *          workflow as the source
     * @return  The newly created block_workflow_step object
     * @static
     */
    public static function clone_step($srcid, $workflowid = null) {
        global $DB;
        $transaction = $DB->start_delegated_transaction();

        // Retrieve the source and copy it
        $src = new block_workflow_step($srcid);

        // Copy the source based on the allowed settings
        foreach (self::expected_settings() as $k) {
            $dst->$k = $src->$k;
        }

        // Unset the id on the target
        unset($dst->id);

        // If a new workflowid was specified, then use it instead
        if ($workflowid) {
            $dst->workflowid = $workflowid;
        }

        // Create the step
        $newstep = new block_workflow_step();
        $newstep->create_step($dst);

        // Clone the todos
        foreach ($src->todos() as $todo) {
            block_workflow_todo::clone_todo($todo->id, $newstep->id);
        }

        // Clone the roles
        foreach ($src->roles() as $role) {
            unset($role->id);
            $role->stepid = $newstep->id;
            $DB->insert_record('block_workflow_step_doers', $role);
        }

        // Allow the transaction at this stage, and return the newly
        // created object
        $transaction->allow_commit();

        // Reload the object using the returned step id and return it
        return $newstep->load_step($newstep->id);
    }

    /**
     * Delete the currently loaded step
     *
     * Before the step is actually removed, {@link require_deletable} is called
     * to ensure that it is ready for removal.
     *
     * @return void
     */
    public function delete() {
        global $DB;

        // Check whether this step may be removed and throw errors if
        // required
        $this->require_deletable();

        $transaction = $DB->start_delegated_transaction();

        // Retrieve a list of the step_states
        $states = $DB->get_records('block_workflow_step_states', array('stepid' => $this->id), null, 'id');
        $statelist = array_map(create_function('$a', 'return $a->id;'), $states);

        // Remove all of the state_change history
        $DB->delete_records_list('block_workflow_state_changes', 'stepstateid', $statelist);

        // Remove the todo_done entries
        $DB->delete_records_list('block_workflow_todo_done', 'stepstateid', $statelist);

        // Remove the states
        $DB->delete_records('block_workflow_step_states', array('stepid' => $this->id));

        // Update the atengobacktostep setting for the workflow if required
        $workflow           = $this->workflow();
        $atendgobacktostep  = $workflow->atendgobacktostep;
        if ($atendgobacktostep && $atendgobacktostep > 1 && $this->stepno <= $atendgobacktostep) {
            $workflow->atendgobacktostep($atendgobacktostep - 1);
        }

        // Remove the step
        $DB->delete_records('block_workflow_steps', array('id' => $this->id));

        // Now that the step has been removed, renumber the remaining step
        // numbers
        $workflow->renumber_steps();

        $transaction->allow_commit();
    }

    /**
     * Update the current step with the data provided
     *
     * This will pass the scripts through {@link validate_script}, if they
     * have been changed.
     *
     * @param   stdClass $data A stdClass containing the fields to update
     *          for this step. The id cannot be changed, or specified in
     *          this data set
     * @return  An update block_workflow_step record as returned by
     *          {@link load_step}.
     */
    public function update_step($data) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        // Retrieve the id for the current step
        $data->id = $this->id;

        // Check that any specified workflow exists
        if (isset($data->workflowid)) {
            try {
                new block_workflow_workflow($data->workflowid);
            } catch (Exception $e) {
                $transaction->rollback($e);
            }
        }

        // Check that each of the submitted data is a valid field
        $expectedsettings = $this->expected_settings();
        foreach ((array) $data as $k => $v) {
            if (!in_array($k, $expectedsettings)) {
                $transaction->rollback(new block_workflow_invalid_step_exception(
                        get_string('invalidfield', 'block_workflow', $k)));
            }
        }

        // Validate any changes to the onactivescript and oncompletescript
        if (isset($data->onactivescript)) {
            $result = $this->validate_script($data->onactivescript);
            if ($result->errors) {
                $transaction->rollback(new block_workflow_invalid_command_exception(
                        get_string('invalidscript', 'block_workflow', $result->errors[0])));
            }
        }

        if (isset($data->oncompletescript)) {
            $result = $this->validate_script($data->oncompletescript);
            if ($result->errors) {
                $transaction->rollback(new block_workflow_invalid_command_exception(
                        get_string('invalidscript', 'block_workflow', $result->errors[0])));
            }
        }

        // Update the record
        $DB->update_record('block_workflow_steps', $data);

        $transaction->allow_commit();

        // Return the updated step object
        return $this->load_step($data->id);
    }

    /**
     * Assign a workflow to the step
     *
     * This is primarily used when creating steps for script validation.
     * When trying to validate a script for a step which has not yet been
     * created, that step must have a workflow already assigned. Any value
     * here is overwritten when the step is actually created.
     *
     * @param   integer $workflowid The ID of the workflow to load for
     * @return  void
     */
    public function set_workflow($workflowid) {
        if ($this->workflowid !== null) {
            throw new block_workflow_invalid_step_exception(
                    get_string('workflowalreadyset', 'block_workflow'));
        }
        $this->workflowid = $workflowid;
    }

    /**
     * Determine whether the step is currently in use
     *
     * @param   int $id The ID of the step if the function is
     *          called in a static context
     * @return  int The number of times the step is in use
     */
    public function in_use($stepid = null) {
        global $DB;
        if (!$stepid) {
            $stepid = $this->id;
        }

        // Determine how many states the step is active in
        return $DB->count_records('block_workflow_step_states',
                array('stepid' => $stepid, 'state' => BLOCK_WORKFLOW_STATE_ACTIVE));
    }

    /**
     * Determine whether the currently loaded step is in use or not, and thus whether it can be removed.
     *
     * A step may not be removed if it is currently in use, and active. A step may only be removed 
     * if it is not the only step in the workflow.
     *
     * @return  boolean Whether the step may be deleted or not
     * @throws  block_workflow_exception If the step is currently in use, and the $throw parameter is true
     */
    public function is_deletable() {
        // A step may only be removed if it isn't actively in use
        if (($count = $this->in_use()) != 0) {
            return false;
        }

        // A step may only be removed if there are other steps in the
        // workflow
        $steps = $this->workflow()->steps();
        if (count($steps) == 1) {
            return false;
        }

        // All conditions must be met if getting to this point
        return true;
    }

    /**
     * Convenience function to require that a step is deletable
     *
     * This is checked using {@link is_deletable}.
     *
     * @throws  block_workflow_exception If the step is currently in use, or is the only step in the workflow
     */
    public function require_deletable() {
        if (!$this->is_deletable()) {
            throw new block_workflow_exception(get_string('cannotremoveonlystep', 'block_workflow'));
        }
        return true;
    }

    /**
     * Load the active step for a given contextid.
     *
     * @param   int $contextid The ID of the context
     * @return  The instantiated block_workflow_step object
     * @throws  block_workflow_not_assigned_exception if no step
     *          appears to be active for the specified $contextid
     */
    public function load_active_step($contextid) {
        global $DB;

        // Contexts are associated to a step by the step_state table
        $sql = 'SELECT steps.* FROM {block_workflow_step_states} state
                LEFT JOIN {block_workflow_steps} steps ON steps.id = state.stepid
                WHERE state.contextid = ? AND state.state = ?';

        $step = $DB->get_record_sql($sql, array($contextid, BLOCK_WORKFLOW_STATE_ACTIVE));
        if (!$step) {
            throw new block_workflow_not_assigned_exception(get_string('noactiveworkflow', 'block_workflow'));
        }
        return $this->load_step($step->id);
    }

    /**
     * Parse the provided script into a set of commands, arguments and classes
     *
     * @param   string $script The script to validate
     * @return  Array of Objects where each object is a standard class
     *          describing a valid command with the following properties:
     *          - string command    The name of the command
     *          - string arguments  The arguments supplied to that command
     *          - string classname  The name of the workflow block class
     *                              this command is provided by
     */
    public static function parse_script($script) {
        // Our return place-holder
        $return = new stdClass();
        $return->errors     = array();
        $return->commands   = array();

        // Break the script into lines
        $lines = preg_split('~[\r\n]+~', $script, null, PREG_SPLIT_NO_EMPTY);

        foreach ($lines as $line) {
            $c = new stdClass();

            // Retrieve the command and arguments
            $args           = preg_split('/[\s]/', trim($line), 2);
            $c->command     = array_shift($args);
            $c->arguments   = array_shift($args);

            // Skip comments
            if (preg_match('/^#/', $c->command)) {
                continue;
            }

            // Check that the class is valid. It must:
            // * exist; and
            // * extend block_workflow_command, which has all required classes defined.
            $c->classname = 'block_workflow_command_' . $c->command;
            if (!class_exists($c->classname)
                    || !is_subclass_of($c->classname, 'block_workflow_command')) {
                    $return->errors[] = get_string('invalidcommand', 'block_workflow', $c->command);
            }
            else {
                // Append the current command to the array
                $return->commands[] = $c;
            }
        }

        return $return;
    }

    /**
     * Validate the provided script
     *
     * The script is parsed by {@link parse_script}, and the is_valid
     * function is then called for each command in this script.
     *
     * @param   string $script   The script to validate
     * @return  array            The list of commands
     */
    public function validate_script($script) {
        // Parse the script to retrieve a list of all valid commands
        $commands =  self::parse_script($script);

        // Call validate on each command
        foreach ($commands->commands as $c) {
            $class = block_workflow_command::create($c->classname);
            if (!$class->is_valid($c->arguments, $this)) {
                $errors = $class->get_validation_errors($c->arguments, $this);
                $commands->errors = array_merge($commands->errors, $errors);
            }
        }
        return $commands;
    }

    /**
     * Determine whether the specified script is valid
     *
     * @param   string $script   The script to validate
     * @return  boolean          Whether the script is valid
     */
    public function is_script_valid($script) {
        // Validate the script
        $return = self::validate_script($script);

        // Check for errors
        if ($return->errors) {
            return false;
        }
        return true;
    }

    /**
     * Require that the specified script is valid and, if not, throw an exception
     *
     * @param   string $script   The script to validate
     * @return  boolean          Whether the script is valid
     * @throws  block_workflow_invalid_command_exception
     */
    public function require_script_valid($script) {
        // Validate the script
        $return = self::validate_script($script);

        // Check for errors
        if ($return->errors) {
            throw new block_workflow_invalid_command_exception(
                    get_string('invalidscript', 'block_workflow', $return->errors[0]));
        }
        return true;
    }

    /**
     * Retrieve any validation errors for the specified script
     *
     * @param   string $script   The script to validate
     * @return  boolean          Whether the script is valid
     */
    public function get_validation_errors($script) {
        // Validate the script
        $return = self::validate_script($script);

        return $return->errors;
    }

    /**
     * Process and Execute the relevant script for the specified state.
     *
     * If the provided state is currently active, then the onactivescript is tested executed;
     * if the provided state is complete, then the oncompletescript is tested executed.
     *
     * @param   block_workflow_step_state $state The step_state to process the script for
     * @return  void
     */
    public function process_script(block_workflow_step_state $state) {
        global $DB;
        $transaction = $DB->start_delegated_transaction();

        switch ($state->state) {
            case BLOCK_WORKFLOW_STATE_ACTIVE:
                $script = $this->onactivescript;
                break;
            case BLOCK_WORKFLOW_STATE_COMPLETED:
                $script = $this->oncompletescript;
                break;
            default:
                $script = '';
                break;
        }

        // Parse the script to retrieve a list of all valid commands
        $commands =  self::parse_script($script);

        // Check for errors
        if ($commands->errors) {
            throw new block_workflow_invalid_command_exception(
                    get_string('invalidscript', 'block_workflow', $commands->errors[0]));

        }

        // Call require_valid and execute on each command
        // We re-validate each script command in case the specific $state makes them invalid
        foreach ($commands->commands as $c) {
            $class = block_workflow_command::create($c->classname);
            $class->require_valid($c->arguments, $state->step(), $state);
            $class->execute($c->arguments, $state);
        }

        // We must allow the transaction to be committed before we attempt to process mail
        $transaction->allow_commit();

        // This is a workaround for a limitation of the message_send system
        // This must be called outside of a transaction
        block_workflow_command_email::message_send();
    }

    /**
     * Return the ID of the next step in a workflow
     *
     * If a subsequent step exists, this is deemed to be the next step; otherwise
     * if the workflow that this step belongs to has an atendgobacktostep
     * value, then this is deemed to be the next step in the sequence.
     *
     * @return mixed    If a next step is available, return the
     *                  block_workflow_step object for that step, otherwise
     *                  return false.
     */
    public function get_next_step() {
        global $DB;

        // Determine the stepid of the next step
        $stepid = $DB->get_field('block_workflow_steps', 'id',
                array('workflowid' => $this->workflowid, 'stepno' => ($this->stepno + 1)));


        if ($stepid) {
            // If there is another step, return that step object
            return new block_workflow_step($stepid);
        }

        if ($stepno = $this->workflow()->atendgobacktostep) {
            // If the workflow has an atendgobacktostep, load that step
            $return = new block_workflow_step();
            return $return->load_workflow_stepno($this->workflowid, $stepno);
        }

        // No next step, return false
        return false;
    }

    /**
     * Return the workflow object that this step belongs to
     *
     * @return block_workflow_workflow Object for this step's workflow
     */
    public function workflow() {
        if ($this->workflow === null) {
            $this->workflow = new block_workflow_workflow($this->workflowid);
        }
        return $this->workflow;
    }

    /**
     * Swap the current stepno with that of the specified step
     *
     * Note: This will allow you to swap any step with any other -- they
     * do not have to be consecutively numbered steps
     *
     * @param block_workflow_step $with The step to swap with
     * @return  An update block_workflow_step record as returned by
     *          {@link load_step}.
     */
    public function swap_step_with(block_workflow_step $with) {
        global $DB;
        $transaction = $DB->start_delegated_transaction();

        // Some handy refernces
        $from   = $this->stepno;
        $to     = $with->stepno;

        // Owing to the unique workflowid, stepno constraint, we need to
        // set the stepno to 0 initially
        $thisstep = new stdClass();
        $thisstep->id       = $this->id;
        $thisstep->stepno   = 0;
        $DB->update_record('block_workflow_steps', $thisstep);

        // Then update the step we're swapping with
        $swapstep = new stdClass();
        $swapstep->id       = $with->id;
        $swapstep->stepno   = $from;
        $DB->update_record('block_workflow_steps', $swapstep);

        // Now update this step again
        $thisstep->stepno   = $to;
        $DB->update_record('block_workflow_steps', $thisstep);

        $transaction->allow_commit();

        // Return the updated step object
        return $this->load_step($this->id);
    }

    /**
     * Return the list of todo tasks belonging to this step
     *
     * @param   boolean $obsolete   Whether to include obsolete steps or not
     * @return  Array of stdClass objects as returned by the database
     *          abstraction layer
     */
    public function todos($obsolete = true) {
        global $DB;

        if ($this->todos === null) {
            $params['stepid'] = $this->id;
            if ($obsolete) {
                $params['obsolete'] = 0;
            }
            $this->todos = $DB->get_records('block_workflow_step_todos', $params);
        }
        return $this->todos;
    }

    /**
     * Returns the list of roles associated with this step
     *
     * @param   int $id The ID of the step if the function is
     *          called in a static context
     * @return  Array of stdClass objects as returned by the database
     *          abstraction layer.
     */
    public function roles($stepid = null) {
        global $DB;

        // If the stepid was not specified, load it from the loaded object
        if (!$stepid) {
            $stepid = $this->id;
        }

        // Retrieve a list of the roles in use
        // We join to the role table here to retrieve the role name data to
        // avoid additional queries later
        $sql = 'SELECT *
                FROM {block_workflow_step_doers} d
                INNER JOIN {role} r ON r.id = d.roleid
                WHERE d.stepid = ?
                ORDER BY r.shortname ASC';

        return $DB->get_records_sql($sql, array($stepid));
    }

    /**
     * Toggle the use of the specified role for the current step
     *
     * If the role is currently not assigned to this step, it is assigned;
     * otherwise, the assignation is removed.
     *
     * @param   int $roleid The ID of the role to toggle
     */
    public function toggle_role($roleid) {
        global $DB;
        if ($DB->get_record('block_workflow_step_doers', array('stepid' => $this->id, 'roleid' => $roleid))) {
            // The ro
            return $DB->delete_records('block_workflow_step_doers', array('roleid' => $roleid, 'stepid' => $this->id));
        }
        else {
            $role = new stdClass();
            $role->stepid = $this->id;
            $role->roleid = $roleid;
            return $DB->insert_record('block_workflow_step_doers', $role);
        }
    }

    public function format_instructions($context) {
        $replaces = array();
        if ($context->contextlevel == CONTEXT_MODULE) {
            $replaces['%%cmid%%'] = $context->instanceid;
        }
        return str_replace(array_keys($replaces), array_values($replaces), $this->instructions);
    }
}

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

        // Update the record
        $DB->update_record('block_workflow_step_states', $state);
        $transaction->allow_commit();

        // Return the updated step_state object
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

        // Make a record of the change
        $change = new stdClass;
        $change->stepstateid    = $this->id;
        $change->newstate       = $newstatus;
        $change->userid         = $USER->id;
        $change->timestamp      = time();
        $DB->insert_record('block_workflow_state_changes', $change);

        // Make the change
        $state = new stdClass;
        $state->id              = $this->id;
        $state->timemodified    = $change->timestamp;
        $state->state           = $newstatus;
        $DB->update_record('block_workflow_step_states', $state);

        // Update the current state
        $this->load_state($this->id);

        // Unassign any role assignments created for this workflow
        switch ($this->state) {
            case BLOCK_WORKFLOW_STATE_ABORTED:
            case BLOCK_WORKFLOW_STATE_COMPLETED:
                role_unassign_all(array('component' => 'block_workflow', 'itemid' => $this->id));
                break;
            default:
                break;
        }

        // Request that any required scripts be processed
        $this->step()->process_script($this);

        $transaction->allow_commit();

        // This is a workaround for a limitation of the message_send system
        // This must be called outside of a transaction
        block_workflow_command_email::message_send();

        // Return the updated step_state object
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

        // Update the comment
        $this->update_comment($newcomment, $newcommentformat);

        // Change the status
        $this->change_status(BLOCK_WORKFLOW_STATE_COMPLETED);

        // move to the next step for this workflow
        if ($nextstep = $this->step()->get_next_step()) {
            try {
                // Try and load an existing state to change status for
                $nextstate = new block_workflow_step_state();
                $nextstate->load_context_step($this->contextid, $nextstep->id);

            } catch (block_workflow_not_assigned_exception $e) {
                // No step_state for this step on this context so create a new state
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

        // This is a workaround for a limitation of the message_send system
        // This must be called outside of a transaction
        block_workflow_command_email::message_send();

        // Return the new state
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

        // If the newstepid wasn't specified, we're just aborting the current step
        if (!$newstepid) {
            // Commit the transaction
            $transaction->allow_commit();

            // This is a workaround for a limitation of the message_send system
            // This must be called outside of a transaction
            block_workflow_command_email::message_send();

            return;
        }

        // move to the specified step for this workflow
        try {
            // Try and load an existing state to change status for
            $nextstate = new block_workflow_step_state();
            $nextstate->load_context_step($this->contextid, $newstepid);

        } catch (block_workflow_not_assigned_exception $e) {
            // No step_state for this step on this context so create a new state
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

        // This is a workaround for a limitation of the message_send system
        // This must be called outside of a transaction
        block_workflow_command_email::message_send();

        // Return a reference to the new state
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
                    WHERE todos.stepid = ? AND todos.obsolete = 0';
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

        // Try and pick up the current task
        $todo = $DB->get_record('block_workflow_todo_done', array('stepstateid' => $this->id, 'steptodoid' => $todoid));
        if ($todo) {
            // Remove the current record. There is no past history at present
            $DB->delete_records('block_workflow_todo_done', array('id' => $todo->id));
            $transaction->allow_commit();
            return false;
        }
        else {
            // Mark the step as completed
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
     * @return  mixed               The database results, or null if no result was found
     */
    public function state_changes($stateid = null) {
        if (!$stateid) {
            $stateid = $this->id;
        }
        global $DB;
        $sql = 'SELECT changes.*, ' . $DB->sql_fullname('u.firstname', 'u.lastname') . ' AS username
                FROM {block_workflow_state_changes} AS changes
                INNER JOIN {user} AS u ON u.id = changes.userid
                WHERE changes.stepstateid = ?
                ORDER BY changes.timestamp DESC';
        return $DB->get_records_sql($sql,
                array($stateid));
    }
}

/**
 * E-mail email class
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @property-read int       $id                 The ID of the email
 * @property-read string    $message            The message of the e-mail email
 * @property-read string    $shortname          The shortname for the e-mail email
 * @property-read string    $subject            The subject of the e-mail email
 */
class block_workflow_email {
    public $id;
    public $message;
    public $shortname;
    public $subject;

    /**
     * Constructor to obtain an e-mail template
     *
     * See documentation for {@link load_email_id} for further information.
     *
     * @param   int $emailid The ID of the e-mail to load
     * @return  Object The e-mail
     */
    public function __construct($emailid = null) {
        if ($emailid) {
            $this->load_email_id($emailid);
        }
    }

    /**
     * Private function to overload the current class instance with a
     * email object
     *
     * @param   stdClass $email Database record to overload into the
     * object   instance
     * @return  The instantiated block_workflow_email object
     * @access  private
     */
    private function _load($email) {
        $this->id           = $email->id;
        $this->message      = $email->message;
        $this->shortname    = $email->shortname;
        $this->subject      = $email->subject;
        return $this;
    }

    /**
     * A list of expected settings for an email template
     *
     * @return  array   The list of available settings
     */
    public function expected_settings() {
        return array(
            'id',
            'message',
            'shortname',
            'subject'
        );
    }

    /**
     * Load a email given it's ID
     *
     * @param   int $id The ID of the email to load
     * @return  The instantiated block_workflow_email object
     * @throws  block_workflow_invalid_email_exception if the id is not found
     */
    public function load_email_id($id) {
        global $DB;
        $email = $DB->get_record('block_workflow_emails', array('id' => $id));
        if (!$email) {
            throw new block_workflow_invalid_email_exception(get_string('invalidid', 'block_workflow'));
        }
        return $this->_load($email);
    }

    /**
     * Load a email given it's shortname
     *
     * @param   string  $shortname The shortname of the email to load
     * @return  The instantiated block_workflow_email object or false if the email does not exist
     */
    public function load_email_shortname($shortname) {
        global $DB;
        $email = $DB->get_record('block_workflow_emails', array('shortname' => $shortname));
        if (!$email) {
            return false;
        }
        return $this->_load($email);
    }

    /**
     * Load a email given it's shortname
     *
     * @param   string  $shortname The shortname of the email to load
     * @return  The instantiated block_workflow_email object
     * @throws  block_workflow_invalid_email_exception if the id is not found
     */
    public function require_email_shortname($shortname) {
        $email = $this->load_email_shortname($shortname);
        if (!$email) {
            throw new block_workflow_invalid_email_exception(get_string('invalidemailshortname', 'block_workflow', $shortname));
        }
        return $email;
    }

    /**
     * Return a list of emails sorted by shortname
     *
     * We also try to determine the number of times that the template is in use in the various step
     * onactivescript and oncompletescript fields.
     *
     * @return  Array of stdClass objects as returned by the database
     *          abstraction layer
     * @throws  block_workflow_invalid_email_exception if the id is not found
     */
    public static function load_emails() {
        global $DB;
        $sql = "SELECT emails.*,
            (
                SELECT COUNT(activescripts.id)
                FROM {block_workflow_steps} AS activescripts
                WHERE activescripts.onactivescript ILIKE '%email%' || emails.shortname || '%to%'
            ) AS activecount,
            (
                SELECT COUNT(completescripts.id)
                FROM {block_workflow_steps} AS completescripts
                WHERE completescripts.oncompletescript ILIKE '%email%' || emails.shortname || '%to%'
            ) AS completecount
            FROM {block_workflow_emails} AS emails
            ORDER BY shortname ASC
        ";
        return $DB->get_records_sql($sql);
    }

    /**
     * Function to create a new email
     *
     * @param   stdClass $email containing the subject, message, and
     *          optionally obsolete option.
     * @return  The newly created block_workflow_email object
     */
    public function create($email) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        // Check whether a shortname was specified
        if (!isset($email->shortname) || empty($email->shortname)) {
            $transaction->rollback(new block_workflow_invalid_email_exception('invalidshortname', 'block_workflow'));
        }

        // Check whether this shortname is already in use
        if ($DB->get_record('block_workflow_emails', array('shortname' => $email->shortname))) {
            $transaction->rollback(new block_workflow_invalid_email_exception('shortnameinuse', 'block_workflow'));
        }

        // Require the message
        if (!isset($email->message)) {
            $transaction->rollback(new block_workflow_invalid_email_exception('invalidmessage', 'block_workflow'));
        }

        // Require the subject
        if (!isset($email->subject)) {
            $transaction->rollback(new block_workflow_invalid_email_exception('invalidsubject', 'block_workflow'));
        }

        // Check that each of the submitted fields is a valid field
        $expectedsettings = $this->expected_settings();
        foreach ((array) $email as $k => $v) {
            if (!in_array($k, $expectedsettings)) {
                $transaction->rollback(new block_workflow_invalid_email_exception(get_string('invalidfield', 'block_workflow', $k)));
            }
        }

        // Insert the new email
        $email->id = $DB->insert_record('block_workflow_emails', $email);

        $transaction->allow_commit();

        // And load it again
        return $this->load_email_id($email->id);
    }

    /**
     * Update the current email with the data provided
     *
     * @param   stdClass $data A stdClass containing the fields to update
     *          for this email. The id cannot be changed, or specified
     *          in this data set
     * @return  An update block_workflow_email record as returned by
     *          {@link load_email_id}.
     */
    public function update($data) {
        global $DB;

        // Retrieve the id for the current email
        $data->id = $this->id;

        $transaction = $DB->start_delegated_transaction();

        // Check whether this shortname is already in use
        if (isset($data->shortname) && ($id = $DB->get_field('block_workflow_emails', 'id', array('shortname' => $data->shortname)))) {
            if ($id != $data->id) {
                $transaction->rollback(new block_workflow_invalid_email_exception('shortnameinuse', 'block_workflow'));
            }
        }

        // Check that each of the submitted fields is a valid field
        $expectedsettings = $this->expected_settings();
        foreach ((array) $data as $k => $v) {
            if (!in_array($k, $expectedsettings)) {
                $transaction->rollback(new block_workflow_invalid_email_exception(get_string('invalidfield', 'block_workflow', $k)));
            }
        }

        // Update the record
        $DB->update_record('block_workflow_emails', $data);

        $transaction->allow_commit();

        // And load it again
        return $this->load_email_id($this->id);
    }

    /**
     * Determine whether the currently loaded e-mail is in use or not, and thus whether it can be removed.
     *
     * @return  boolean Whether the e-mail may be deleted or not
     */
    public function is_deletable() {
        // Count the number of uses
        $count = $this->used_count();

        return (!$count > 0);
    }

    /**
     * Convenience function to require that an email is deletable
     *
     * This is checked using {@link is_deletable}.
     *
     * @throws  block_workflow_exception If the email is currently in use
     */
    public function require_deletable() {
        if (!$this->is_deletable()) {
            throw new block_workflow_exception(get_string('cannotremoveonlystep', 'block_workflow'));
        }
        return true;
    }

    /**
     * Delete the currently loaded email
     *
     * We first check whether we can delete this e-mail using {@link require_deletable}.
     *
     * @return void
     */
    public function delete() {
        global $DB;

        // First check that we can delete this
        $this->require_deletable();
        $DB->delete_records('block_workflow_emails', array('id' => $this->id));
    }

    /**
     * Accurately count the number of times the e-mail template is in use
     *
     * Please note that this is quite computationally expensive
     *
     * @return  integer             The number of times the template is in use
     */
    public function used_count() {
        global $DB;

        // Grab the count
        $count = 0;

        // Count the uses in the activescripts
        $sql = "SELECT activescripts.onactivescript AS script
                FROM {block_workflow_steps} AS activescripts
                WHERE activescripts.onactivescript ILIKE '%email%' || ? || '%to%'";
        $activescripts = $DB->get_records_sql($sql, array($this->shortname));
        $count += $this->_used_count($activescripts);

        // Count the uses in the completescripts
        $sql = "SELECT completescripts.oncompletescript AS script
                FROM {block_workflow_steps} AS completescripts
                WHERE completescripts.oncompletescript ILIKE '%email%' || ? || '%to%'";
        $completescripts =  $DB->get_records_sql($sql, array($this->shortname));
        $count += $this->_used_count($completescripts);

        // Return the tital usage count
        return $count;
    }

    /**
     * Check the provided array of scripts whether the template is really in use
     *
     * @param   array   $scripts    An array of stdClass objects with a script value
     * @return  integer             The number of times the template is in use
     */
    private function _used_count($scripts) {
        // Keep track of the count
        $count = 0;

        // Check each of the provided scripts
        foreach ($scripts as $script) {
            $commands = block_workflow_step::parse_script($script->script);
            foreach ($commands->commands as $c) {
                if ($c->command == 'email') {
                    // For each e-mail command, process the command and get the shortname
                    $class = block_workflow_command::create($c->classname);
                    $data = $class->parse($c->arguments, $this);
                    if ($data->email->shortname == $this->shortname) {
                        // Shortnames match so increment the count
                        $count++;
                    }
                }
            }
        }

        return $count;
    }

}

/**
 * A class describing and handling actions for todo list items
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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

        // Check that we have a task
        if (!isset($todo->task)) {
            $transaction->rollback(new block_workflow_invalid_todo_exception(get_string('tasknotspecified', 'block_workflow')));
        }

        // Ensure that a stepid was specified
        if (!isset($todo->stepid)) {
            $transaction->rollback(new block_workflow_invalid_todo_exception(get_string('invalidstepid', 'block_workflow')));
        }

        // Ensure that the stepid related to a valid step
        try {
            new block_workflow_step($todo->stepid);
        } catch (Exception $e) {
            $transaction->rollback($e);
        }

        // Set the obsolete value
        $todo->obsolete = BLOCK_WORKFLOW_ENABLED;

        // Check that each of the submitted fields is a valid field
        $expectedsettings = $this->expected_settings();
        foreach ((array) $todo as $k => $v) {
            if (!in_array($k, $expectedsettings)) {
                $transaction->rollback(new block_workflow_invalid_todo_exception(get_string('invalidfield', 'block_workflow', $k)));
            }
        }

        // Create the todo
        $todo->id = $DB->insert_record('block_workflow_step_todos', $todo);

        // Finished with the transaction
        $transaction->allow_commit();

        // Reload the object using the returned step id and return it
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

        // Retrieve the id for the current todo
        $data->id = $this->id;

        $transaction = $DB->start_delegated_transaction();

        // Don't allow the stepid to be updated
        if (isset($data->stepid) && ($data->stepid != $this->stepid)) {
            $transaction->rollback(new block_workflow_invalid_todo_exception(get_string('todocannotchangestepid', 'block_workflow')));
        }

        // Check that each of the submitted fields is a valid field
        $expectedsettings = $this->expected_settings();
        foreach ((array) $data as $k => $v) {
            if (!in_array($k, $expectedsettings)) {
                $transaction->rollback(new block_workflow_invalid_todo_exception(get_string('invalidfield', 'block_workflow', $k)));
            }
        }

        // Update the record
        $DB->update_record('block_workflow_step_todos', $data);

        $transaction->allow_commit();

        // Return the updated todo object
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

        // First remove any todo_done records
        $DB->delete_records('block_workflow_todo_done', array('steptodoid' => $this->id));

        // Then remove the actual todo
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

        // Retrieve the source, and clone it
        $src = new block_workflow_todo($srcid);

        // Copy the source based on the allowed settings
        foreach (self::expected_settings() as $k) {
            $dst->$k = $src->$k;
        }

        // Unset the id on the target
        unset($dst->id);

        // If a new stepid was specified, then use it instead
        if ($stepid) {
            $dst->stepid = $stepid;
        }

        // Ensure that obsolete is set
        $dst->obsolete = ($dst->obsolete) ? 1 : 0;

        // Create the entry
        $newtodo = new block_workflow_todo();
        $newtodo->create_todo($dst);

        // Allow the transaction at this stage, and return the newly
        // created object
        $transaction->allow_commit();

        return $newtodo->load_by_id($newtodo->id);
    }

    /**
     * Toggle the obsolete flag for the current todo
     *
     * @param   int $todoid The ID of the todo to toggle if called in a
     *          static context
     * @return  An update block_workflow_todo record as returned by {@link load_todo}.
     */
    public function toggle($todoid = null) {
        global $DB;
        $transaction = $DB->start_delegated_transaction();

        if (!$todoid) {
            $todoid  = $this->id;
            $current = $this;
        }
        else {
            // Retrieve the specified record
            $current = new block_workflow_todo($todoid);
        }

        $update = new stdClass();
        $update->id = $todoid;

        // Switch the obsolete state of the todo
        if ($current->obsolete == BLOCK_WORKFLOW_ENABLED) {
            $update->obsolete = BLOCK_WORKFLOW_OBSOLETE;
        }
        else {
            $update->obsolete = BLOCK_WORKFLOW_ENABLED;
        }

        // Update the record
        $DB->update_record('block_workflow_step_todos', $update);
        $transaction->allow_commit();

        // Return the updated todo object
        return $current->load_by_id($todoid);
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

/**
 * The abstract class that each workflow command should extend
 *
 * This class also provides some additional helper functions which the various commands may use
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
class block_workflow_command {

    /**
     * Helper function to return a new instance of the specified command class
     *
     * @param   string  $class  The name of the class to instantiate
     * @return  object          The instantiated class
     */
    public static function create($class) {
        return new $class;
    }

    /**
     * Determine whether the data provided to this command is valid
     *
     * @param   string $args    The list of arguments passed to the command in the script
     * @param   object $step    The step that this command is associated with
     * @param   object $state   The state for this script. This may be used to validate this step in the context of the
     *                          provided state.
     * @return  boolean         Whether the command is valid
     */
    public function is_valid($args, $step, $state = null) {
        $return = $this->parse($args, $step, $state);

        if ($return->errors) {
            return false;
        }
        return true;
    }

    /**
     * Require that the data provided to this command is valid
     *
     * @param   string $args    The list of arguments passed to the command in the script
     * @param   object $step    The step that this command is associated with
     * @param   object $state   The state for this script. This may be used to validate this step in the context of the
     *                          provided state.
     * @return  boolean         Whether the command is valid
     */
    public function require_valid($args, $step, $state = null) {
        // Parse the script to grab any errors
        $return = $this->parse($args, $step, $state);

        if ($return->errors) {
            // Throw an exception -- only show the first error
            throw new block_workflow_invalid_command_exception(
                    get_string('invalidscript', 'block_workflow', $return->errors[0]));
        }
        return true;
    }

    /**
     * Return a list of validation errors
     *
     * @param   string $args    The list of arguments passed to the command in the script
     * @param   object $step    The step that this command is associated with
     * @param   object $state   The state for this script. This may be used to validate this step in the context of the
     *                          provided state.
     * @return  array           The list of errors
     */
    public function get_validation_errors($args, $step, $state = null) {
        $return = $this->parse($args, $step, $state);
        return $return->errors;
    }

    /**
     * Determine whether the specified role exists
     *
     * @param   string $rolename    The shortname of the role
     * @return  mixed               The record for this role retrieved from the database, or false if it does not exist
     */
    public static function role_exists($rolename) {
        global $DB;

        $role = $DB->get_record('role', array('shortname' => strtolower($rolename)));
        return $role;
    }

    /**
     * Convenience function to require that the specified role exists
     *
     * @param   string $rolename The shortname of the role
     * @return  stdClass The record for this role retrieved from the database
     * @throws  block_workflow_invalid_command_exception If the role does not exist
     */
    public static function require_role_exists($rolename, &$errors) {
        $role = self::role_exists($rolename);
        if ($role) {
            return $role;
        }
        $errors[] = get_string('invalidrole', 'block_workflow', $rolename);
        return false;
    }

    /**
     * Retrieve a list of users for the specified role in the specified context
     *
     * @param   stdClass $role    An object containing at least the role id
     * @param   stdClass $context A context object
     * @return  Array    A list of users for the specified context and role
     */
    public function role_users($role, $context) {
        $fields = 'u.id, u.confirmed, u.username, u.firstname, u.lastname, '.
                  'u.maildisplay, u.mailformat, u.maildigest, '.
                  // This is just the default list of fields, but adding emailstop,
                  // which is absolutely vital when sending emails now, but which
                  // they did not add to the default list of fields. Once MDL-30260
                  // is fixed, we should be able to once more remove the explicit
                  // list of fields here.
                  'u.emailstop, '.
                                                             'u.email, u.city, '.
                  'u.country, u.picture, u.idnumber, u.department, u.institution, '.
                  'u.lang, u.timezone, u.lastaccess, u.mnethostid, r.name AS rolename, r.sortorder';
        return get_role_users($role->id, $context, false, $fields);
    }

    /**
     * Helper function to determine whether the specified workflow applies to an activity
     *
     * @param   object $workflow The workflow to be tested
     * @return  Boolean
     */
    public function is_activity($workflow) {
        // All workflows barring courses are activities
        return ($workflow->appliesto != 'course');
    }

    /**
     * Helper function to determine whether the specified context belongs to directly a course
     *
     * @param   object $workflow The workflow to test
     * @return  Boolean
     */
    public function is_course($workflow) {
        // Only 'course' is a course
        return ($workflow->appliesto == 'course');
    }
}

/**
 * The command handling for sending e-mail
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
class block_workflow_command_email extends block_workflow_command {

    /**
     * Parse the supplied arguments into a email name, and list of roles
     *
     * @param   string $args The list of arguments
     * @return  stdClass containing:
     *          - emailname
     *          - roles
     */
    public function parse_args($args) {
        $data = new stdClass();
        $data->errors = array();

        // Break down the line. It should be in the format:
        //      {email} to {rolea} {roleb} {rolen}
        $line = preg_split('/[\s+]/', $args);

        // Grab the email name
        $data->emailname = array_shift($line);

        // Shift off the 'to' component
        $to = array_shift($line);
        if ($to !== 'to') {
            $data->errors[] = get_string('invalidsyntaxmissingto', 'block_workflow');
            return $data;
        }

        // Return the remaining roles unprocessed
        $data->roles = $line;

        return $data;
    }

    /**
     * Static function to parse a command given it's arguments, the step it is associated with, and optionally the state
     *
     * If a state is specified, this may be used to parse the script in a specific context.
     * The provided roles are validated for present with {@link require_role_exists}.
     * The arguments are parsed by {@link parse}.
     *
     * @param   string $args  The list of arguments passed to the command in the script
     * @param   object $step  The step that this command is associated with
     * @param   object $state The state for this script. This may be used to validate this step in the context of the
     *                        provided state.
     * @return  stdClass containing the validated data
     *          - All fields as provided by {@link parse}
     *          - email     - The full body of the email
     *          - context   - If the $state was specified, the context for that state
     *          - users     - The list of users
     *          - errors    - Any errors returned
     */
    public function parse($args, $step, $state = null) {
        // Parse the arguments
        $data = $this->parse_args($args);

        // Check that the e-mail email exists
        $data->email = $this->email($data->emailname, $data->errors);
        if ($data->errors) {
            return $data;
        }

        // If we were given a state, then retrieve it's context for use in the execution
        if ($state) {
            $data->context = $state->context();
        }

        // Check that some roles were specified
        if (count($data->roles) <= 0) {
            $data->errors[] = get_string('norolesspecified', 'block_workflow');
            return $data;
        }

        // Check whether the specified roles exist and fill the list of target users
        $data->users = array();
        foreach ($data->roles as $role) {
            $thisrole = parent::require_role_exists($role, $data->errors);
            if ($data->errors) {
                return $data;
            }

            if ($state) {
                // We can only get the list of users if we've got a specific context
                $data->users = array_merge($data->users, parent::role_users($thisrole, $data->context));
            }
        }

        return $data;
    }

    /**
     * Execute the command given the supplied arguments and state.
     * The function calls {@link validate} with the arguments, step and state.
     *
     * Owing to a restriction in the moodle message_send function which prevents messages from being
     * sent whilst in a transaction, we pass sending to block_workflow_command_email::message_send
     * which stores them for later.
     *
     * To process the message queue, block_workflow_command_email::message_send() must be called
     * outside of a transaction
     *
     * @param   string $args  The list of arguments passed to the command in the script
     * @param   object $state The state for this script. This may be used to validate this step in the context of the
     *                        provided state.
     * @return  void
     */
    public function execute($args, $state) {
        // Validate the command and use it to retrieve the required data
        $email = $this->parse($args, $state->step(), $state);

        if ($email->errors) {
            // We should never be able to execute a script which contains errors
            throw new block_workflow_invalid_command_exception(get_string('invalidscript', 'block_workflow', $email->errors[0]));
        }

        // Fill in the blanks
        $this->email_params($email, $state);

        // Send the e-mail
        $eventdata = new stdClass();
        $eventdata->component   = 'block_workflow';
        $eventdata->name        = 'notification';
        $eventdata->userfrom    = get_admin();
        $eventdata->subject     = $email->email->subject;
        $eventdata->fullmessage = $email->email->message;
        $eventdata->fullmessageformat   = FORMAT_PLAIN;
        $eventdata->fullmessagehtml     = '';
        $eventdata->smallmessage        = $eventdata->fullmessage;
        $eventdata->contexturl          = get_context_url($email->context);
        $eventdata->contexturlname      = print_context_name($email->context, false, true);

        /*
         * Because of an issue with the message_send function in moodle core whereby it is not
         * possible to call the function within a transaction, we queue messages here to be called
         * later by the function block_workflow_command_email::send_mail()
         * It should be possible to replace this call with message_send($eventdata); if and
         * when this limitation is removed
         */
        foreach ($email->users as $user) {
            $eventdata->userto          = $user;
            block_workflow_command_email::message_send($eventdata);
        }
    }

    /**
     * Retrieve the text for the specified email
     *
     * @param   String shortname
     * @return  stdClass The database result for the specified e-mail email
     * @throws  block_workflow_invalid_command_exception If the email does not exist
     */
    public function email($shortname, &$errors) {
        global $DB;
        $email = $DB->get_record('block_workflow_emails', array('shortname' => $shortname));
        if (!$email) {
            $errors[] = get_string('invalidemailemail', 'block_workflow', $shortname);
            return false;
        }
        return $email;
    }

    /**
     * Substitute the standard email parameters. The following parameters are substituted:
     * - %%workflowname%%   The name of the workflow
     * - %%stepname%%       The name of the step
     * - %%contextname%%    The name of the context for the specified $state
     * - %%coursename%%     The name of the course for the specified $state
     * - %%usernames%%      The list of users to whom this e-mail will be sent
     * - %%instructions%%   The set of instructions in the step
     * - %%tasks%%          A comma-separated list of todo tasks
     * - %%comment%%        If the specified state is active, then the comment for the current
     *                      state, otherwise the comment for the previous state.
     *
     * @param   stdClass &$email The incoming email
     * @param   object   $state    The block_workflow_step_state for the message being sent
     * @return  void
     */
    private function email_params(&$email, $state) {
        // Shorter accessors
        $string   = $email->email->message;
        $subject  = $email->email->subject;
        $step     = $state->step();
        $workflow = $step->workflow();

        // Replace %%workflowname%%
        $string  = str_replace('%%workflowname%%', $workflow->name, $string);
        $subject = str_replace('%%workflowname%%', $workflow->name, $subject);

        // Replace %%stepname%%
        $string  = str_replace('%%stepname%%', $step->name, $string);
        $subject = str_replace('%%stepname%%', $step->name, $subject);

        // Replace %%contextname%%
        $contextname = print_context_name($email->context, false, true);
        $string  = str_replace('%%contextname%%', $contextname, $string);
        $subject = str_replace('%%contextname%%', $contextname, $subject);

        // Replace %%contexturl%%
        $contexturl = get_context_url($email->context);
        $string  = str_replace('%%contexturl%%', $contexturl, $string);
        $subject = str_replace('%%contexturl%%', $contexturl, $subject);

        // Replace %%coursename%%
        if ($email->context->contextlevel == CONTEXT_COURSE) {
            $coursename = $contextname;
        } else {
            $parentcontextid = get_parent_contextid($email->context);
            $coursename = print_context_name(get_context_instance_by_id($parentcontextid), false, true);
        }
        $string  = str_replace('%%coursename%%', $coursename, $string);
        $subject = str_replace('%%coursename%%', $coursename, $subject);

        // Replace %%usernames%%
        $usernames = array_map(create_function('$a', 'return fullname($a);'), $email->users);
        $string  = str_replace('%%usernames%%', implode(', ', $usernames), $string);
        $subject = str_replace('%%usernames%%', implode(', ', $usernames), $subject);

        // Replace %%instructions%%
        $instructions = html_to_text($step->format_instructions($email->context), 0, false);
        $string  = str_replace('%%instructions%%', $instructions, $string);
        $subject = str_replace('%%instructions%%', $instructions, $subject);

        // Replace %%tasks%%
        $tasks   = array_map(create_function('$a', 'return $a->task;'), $step->todos());
        $string  = str_replace('%%tasks%%', implode(', ', $tasks), $string);
        $subject = str_replace('%%tasks%%', implode(', ', $tasks), $subject);

        // Replace %%comment%%
        if ($state->state != BLOCK_WORKFLOW_STATE_ACTIVE) {
            $comment = html_to_text($state->comment, 0, false);
        } else if (!empty($state->previouscomment)) {
            $comment = html_to_text($state->previouscomment, 0, false);
        } else {
            $comment = '';
        }
        $string  = str_replace('%%comment%%', $comment, $string);
        $subject = str_replace('%%comment%%', $comment, $subject);

        // Re-assign the message
        $email->email->message = $string;
        $email->email->subject = $subject;
    }

    /**
     * This function is provided as a workaround to a @todo in the Moodle message_send function.
     * Unfortunately, at time of writing, the message_send function cannot
     * be called from within a transaction. Doing so will throw a dml_transaction_exception.
     *
     * This workaround must be called to send the e-mail at a later point when not in a transaction
     * and will only attempt to send the messages if no transaction is currently in progress.
     *
     * It is safe to call this function multiple times
     *
     * @access  public
     * @param   object  $eventdata  The message to send
     * @return  void
     */
    public static function message_send($eventdata = null) {
        global $DB;

        static $mailqueue = array();

        if ($eventdata) {
            $mailqueue[] = clone $eventdata;
        }

        if (count($mailqueue) > 0 && !$DB->is_transaction_started()) {
            // Only try to send if we're not in a transaction
            while ($eventdata = array_shift($mailqueue)) {
                // Send each message in the array
                if (!message_send($eventdata)) {
                    throw new workflow_command_failed_exception(get_string('emailfailed', 'block_workflow'));
                }
            }
        }
    }
}

/**
 * The command to assign one role to a list of other roles
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
class block_workflow_command_assignrole extends block_workflow_command {
    /**
     * Validate the syntax of this line, and ensure that it is correct for
     * this context
     * @param String    args    The list of arguments to the command
     * @param stdClass  state   The state object for this step_state
     * @throws block_workflow_invalid_command_exception
     * @return stdClass data    An object containing the validated data
     *                          which will be used for execution
     *
     * Exceptions are thrown if:
     * * an invalid role is specified for the newrole; or
     * * an invalid role is specified for any of the role assignments.
     *
     * Note: No exception is thrown if there are no users to assign the newrole to.
     */
    public function parse($args, $step, $state = null) {
        // We'll return the components in an object
        $data = new stdClass();
        $data->errors = array();

        if ($state) {
            $data->context = $state->context();
        }

        // Break down the line. It should be in the format:
        //      {newrole} to {rolea} {roleb} {rolen}
        $line = preg_split('/[\s+]/', $args);

        // Grab the new role name
        $data->newrole = parent::require_role_exists(array_shift($line), $data->errors);

        // Shift off the 'to' component
        $to = array_shift($line);
        if ($to !== 'to') {
            $data->errors[] = get_string('invalidsyntaxmissingto', 'block_workflow');
            return $data;
        }

        // Check whether the specified roles exist and fill the list of target users
        $data->roles = array();
        $data->users = array();

        // Check each role exists, and retrieve the data
        foreach ($line as $role) {
            // Check that the role exists
            if ($thisrole = parent::require_role_exists($role, $data->errors)) {
                $data->roles[] = $thisrole;
                if ($state) {
                    // We can only get the list of users if we've got a specific context
                    $data->users = array_merge($data->users, parent::role_users($thisrole, $data->context));
                }
            }
        }

        // Check that some roles were specified
        if (count($data->roles) <= 0) {
            $data->errors[] = get_string('norolesspecified', 'block_workflow');
            return $data;
        }

        return $data;
    }

    /**
     * Execute the command given the line of arguments and state of the
     * step.
     *
     * Validation is automatically performed before continuing.
     * @param String    args    The list of arguments to the command
     * @param stdClass  state   The state object for this step_state
     */
    public function execute($args, $state) {
        $data = $this->parse($args, $state->step(), $state);
        foreach ($data->users as $user) {
            role_assign($data->newrole->id, $user->id, $data->context->id, 'block_workflow', $state->id);
        }
    }
}

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

/**
 * The command to set the visibility of the course that the workflow is assigned to
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
class block_workflow_command_setcoursevisibility extends block_workflow_command {
    public function parse($args, $step, $state = null) {
        $data = new stdClass();
        $data->errors = array();

        // Check that this step workflow relatees to an activity
        if (!parent::is_course($step->workflow())) {
            $data->errors[] = get_string('notacourse', 'block_workflow');
        }

        // Check for the correct visibility option
        if ($args == 'hidden') {
            $data->visible = 0;
        }
        else if ($args == 'visible') {
            $data->visible = 1;
        }
        else {
            $data->errors[] = get_string('invalidvisibilitysetting', 'block_workflow', $args);
        }

        if ($state) {
            $data->id = $state->context()->instanceid;
        }

        return $data;
    }
    public function execute($args, $state) {
        global $DB;
        $data = $this->parse($args, $state->step(), $state);

        // Change the visiblity
        $DB->update_record('course', $data);
    }
}

/**
 * The command to override a role capability for the course (or activity)
 * the workflow is assigned to
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
class block_workflow_command_override extends block_workflow_command {
    public function parse($args, $step, $state = null) {
        $data = new stdClass();
        $data->errors = array();

        if ($state) {
            $data->context = $state->context();
        }

        // Break down the line. It should be in the format:
        //      {newrole} to {rolea} {roleb} {rolen}
        $line = preg_split('/[\s+]/', $args);

        // Grab the role name
        $data->role = parent::require_role_exists(array_shift($line), $data->errors);
        if ($data->errors) {
            // Return early if we hit errors
            return $data;
        }

        // Grab the override
        $override = array_shift($line);
        switch ($override) {
            case "inherit":
                $data->permission = CAP_INHERIT;
                break;
            case "allow":
                $data->permission = CAP_ALLOW;
                break;
            case "prevent":
                $data->permission = CAP_PREVENT;
                break;
            case "prohibit":
                $data->permission = CAP_PROHIBIT;
                break;
            default:
                $data->errors[] = get_string('invalidpermission', 'block_workflow');
                return $data;
                break;
        }

        /**
         * And the capability
         */
        $cap = array_shift($line);
        if (!get_capability_info($cap)) {
            $data->errors[] = get_string('invalidcapability', 'block_workflow');
            return $data;
        }
        $data->capability = $cap;

        /**
         * What is it being overridden in?
         */
        array_shift($line);
        $in = array_shift($line);

        if ($this->is_course($step->workflow()) && $in != 'course') {
            $data->errors[] = get_string('notacourse', 'block_workflow');
            return $data;
        }

        switch ($in) {
        case "course":
                if ($state) {
                    // If we're actually running this, determine the relevant contextid
                    if ($this->is_course($step->workflow())) {
                        // Changint the contextid on the workflow's context
                        $data->contextid = $state->contextid;
                    }
                    else {
                        // Changing the contextid on the workflow's parent context
                        $data->contextid = get_parent_contextid($state->context());
                    }
                }
                break;
            case "activity":
                if ($this->is_course($step->workflow())) {
                    // You can't change activity permissions on a course
                    $data->errors[] = get_string('notacourse', 'block_workflow');
                }
                else if ($state) {
                    // Changing the contextid on the workflow's context
                    $data->contextid = $state->contextid;
                }
                break;
            default:
                $data->errors[] = get_string('invalidtarget', 'block_workflow');
                break;
        }

        return $data;
    }
    public function execute($args, $state) {
        $data = $this->parse($args, $state->step(), $state);
        assign_capability($data->capability, $data->permission, $data->role->id, $data->contextid, true);
    }
}

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

        // Check that this step workflow relatees to an activity
        if (!parent::is_activity($workflow)) {
            $data->errors[] = get_string('notanactivity', 'block_workflow', 'setactivityvisibility');
            return $data;
        }

        if ($state) {
            $data->cm = get_coursemodule_from_id($workflow->appliesto, $state->context()->instanceid);
        }

        // We'll use the database_manager to check whether tables and fields exist
        $dbman = $DB->get_manager();

        // Check that the $appliesto table exists
        $data->table = $workflow->appliesto;
        if (!$dbman->table_exists($data->table)) {
            $data->errors[] = get_string('invalidappliestotable', 'block_workflow', $workflow->appliesto);
            return $data;
        }

        // Break down the line. It should be in the format:
        //      {column} to {value}
        $line = preg_split('/[\s+]/', $args);

        /**
         * Get the column and check that it exists
         */
        $data->column = array_shift($line);
        if (!$dbman->field_exists($data->table, $data->column)) {
            $data->errors[] = get_string('invalidactivitysettingcolumn', 'block_workflow', $data->column);
            return $data;
        }

        // Shift off the 'to' component
        $to = array_shift($line);
        if ($to !== 'to') {
            $data->errors[] = get_string('invalidsyntaxmissingto', 'block_workflow');
            return $data;
        }

        // What we'll be setting it to
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


/*
 * Exceptions
 */

/**
 * Base Block Workflow exception
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_workflow_exception                  extends moodle_exception{}

/**
 * Workflow not assigned exception
 *
 * This exception is typically thrown when trying to load the active workflow for a context which
 * has no workflow assigned
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_workflow_not_assigned_exception     extends block_workflow_exception{}

/**
 * Invalid Workflow exception
 *
 * This exception is typically thrown when attempting to load a workflow which does not exist
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_workflow_invalid_workflow_exception extends block_workflow_exception{}

/**
 * Invalid step exception
 *
 * This exception is typically thrown when attempting to load a step which does not exist
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_workflow_invalid_step_exception     extends block_workflow_exception{}

/**
 * Invalid command exception
 *
 * This exception is typically thrown when attempting to use a command which does not exist
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_workflow_invalid_command_exception  extends block_workflow_exception{}

/**
 * Invalid email exception
 *
 * This exception is typically thrown when attempting to load a email which does not exist
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_workflow_invalid_email_exception extends block_workflow_exception{}

/**
 * Invalid todo exception
 *
 * This exception is typically thrown when attempting to load a todo which does not exist
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_workflow_invalid_todo_exception extends block_workflow_exception{}

/**
 * AJAX exception
 *
 * This exception is typically thrown when an AJAX script attempts to use an invalid command
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_workflow_ajax_exception             extends block_workflow_exception{}

/**
 * Invalid import exception
 *
 * This exception is typically thrown on importing validation errors
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_workflow_invalid_import_exception extends block_workflow_exception{}

/*
 * Other functions
 */

/**
 * Return an list of all of the workflows ordered by obsolete status, then appliesto, and finally
 * the shortname
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @return  array   Containing a list of the all workflows
 */
function block_workflow_load_workflows() {
    global $DB;
    return $DB->get_records('block_workflow_workflows', null, 'obsolete ASC, appliesto ASC, shortname ASC');
}

/**
 * Return the list of modules that workflows may apply to (appliesto)
 *
 * The list contains course as the first item, plus every installed plugin
 * as returned by {@link get_plugin_list}.
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @return  array   Associative array to fill an appliesto select
 */
function block_workflow_appliesto_list() {
    // appliesto should contain courses
    $return = array('course' => get_string('course'));

    // and any installed modules
    $mods = get_plugin_list('mod');
    foreach ($mods as $name => $path) {
        $return[$name] = get_string('pluginname', 'mod_' . $name);
    }
    return $return;
}

/**
 * Return the formatted language string for the specified $appliesto
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @param   string  $appliesto  The language key
 * @return  string              The formatted version for the $appliesto
 */
function block_workflow_appliesto($appliesto) {
    if ($appliesto == 'course') {
        return get_string($appliesto);
    }
    return get_string('pluginname', 'mod_' . $appliesto);
}

/**
 * Returns a list of the roles available at the specified contextlevel
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @param   string  $contextlevel   The contextlevel
 * @return  mixed                   The database results, or null if no result was found
 */
function block_workflow_contextlevel_roles($contextlevel) {
    global $DB;
    $sql = "SELECT DISTINCT roles.*
            FROM {role_context_levels} cl
            INNER JOIN {role} roles ON roles.id = cl.roleid
            WHERE cl.contextlevel = ?
            ORDER BY roles.sortorder ASC
            ";
    return $DB->get_records_sql($sql, array($contextlevel));
}

/**
 * Return an array of the default editor options to use for the standard moodle html editor
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @return  array   Containing a list of default properties
 */
function block_workflow_editor_options() {
    $options = array();

    // Disallow files
    $options['maxfiles'] = 0;

    // Disallow use of images
    return $options;
}

/**
 * Return a human-readable string to describe the editor format
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @param   int     The editor format
 * @return  string  The human-readable string format
 */
function block_workflow_editor_format($type) {
    switch ($type) {
        case FORMAT_HTML:
            return get_string('format_html', 'block_workflow');
        case FORMAT_PLAIN:
            return get_string('format_plain', 'block_workflow');
        default:
            return get_string('format_unknown', 'block_workflow');
    }
}

/**
 * Coverts human-readable string to editor format, used in importing
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @param   string     The human-readable string format
 * @return  int        The editor format
 */
function block_workflow_convert_editor_format($format) {
    $knownformats = array(
        get_string('format_html', 'block_workflow')  => FORMAT_HTML,
        get_string('format_plain', 'block_workflow') => FORMAT_PLAIN,
    );
    if (isset($knownformats[$format])) {
        return $knownformats[$format];
    }
    else {
        throw new block_workflow_exception(get_string('invalidformat', 'block_workflow', $format));
    }
}

/**
 * Check whether the current user can make changes to the specified state
 *
 * That is to say, that ths current user has either the workflow:dostep
 * permission, or is listed in the step roles for the specified state step
 *
 * @param   object  $state  The step_state object
 * @return  boolean         Whether or not the user has permission
 */
function block_workflow_can_make_changes($state) {
    global $USER;

    static $canmakechanges = array();

    $context = $state->context();

    if (isset($canmakechanges[$context->id][$state->id])) {
        return $canmakechanges[$context->id][$state->id];
    }
    else {
        $canmakechanges[$context->id][$state->id] = false;
    }

    if (has_capability('block/workflow:dostep', $context)) {
        $canmakechanges[$context->id][$state->id] = true;
        return $canmakechanges[$context->id][$state->id];
    }

    foreach ($state->step()->roles() as $role) {
        if (user_has_role_assignment($USER->id, $role->id, $context->id)) {
            $canmakechanges[$context->id][$state->id] = true;
            return $canmakechanges[$context->id][$state->id];
        }
    }
    return $canmakechanges[$context->id][$state->id];
}
