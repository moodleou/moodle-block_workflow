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
 * Defines the class representing one step in a workflow.
 *
 * @package   block_workflow
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');


/**
 * step class
 *
 * Class for handling workflow step operations
 *
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @property-read int       $id                 The ID of the step
 * @property-read int       $workflowid         The ID of the owner workflow
 * @property-read int       $stepno             The step number
 * @property-read string    $name               The full name of the step
 * @property-read string    $instructions       The formatted instructions of the step
 * @property-read int       $instructionsformat The format of the instructions field
 * @property-read string    $onactivescript     The script for processing when the step is made active
 * @property-read string    $oncompletescript   The script for processing when the step is made complete
 * @property-read string    $autofinish         The string for processing when the step is finished automatically.
 * @property-read int       $autofinishoffset   The duration in seconds relative to $autofinish
 */
class block_workflow_step {
    const DAYS_BEFORE_QUIZ = -10;
    const DAYS_AFTER_QUIZ = 10;
    const DAYS_BEFORE_COURSE = -120;
    const DAYS_AFTER_COURSE = 30;

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
    public $autofinish;
    public $autofinishoffset;

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
        $this->autofinish           = $step->autofinish;
        $this->autofinishoffset     = $step->autofinishoffset;
        return $this;
    }

    /**
     * Create a step object from a raw database row.
     * @param stdClass $stepdata raw data, as returned by $DB->get_record('block_workflow_steps', ...);
     * @return block_workflow_step the corresponding object.
     */
    public static function make($stepdata) {
        $step = new block_workflow_step();
        $step->_load($stepdata);
        return $step;
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
            'oncompletescript',
            'autofinish',
            'autofinishoffset'
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

        // Set the default onactivescript and oncompletescript.
        if (!isset($step->onactivescript)) {
            $step->onactivescript = '';
        }
        if (!isset($step->oncompletescript)) {
            $step->oncompletescript = '';
        }

        // Set the default instructionsformat.
        if (!isset($step->instructionsformat)) {
            $step->instructionsformat = FORMAT_HTML;
        }

        // Set the default autofinish and autofinishoffset.
        if (empty($step->autofinish)) {
            $step->autofinish = null;
        }
        if (empty($step->autofinishoffset)) {
            $step->autofinishoffset = 0;
        }

        $transaction = $DB->start_delegated_transaction();

        // Check that the workflowid was specified.
        if (!isset($step->workflowid)) {
            $transaction->rollback(new block_workflow_invalid_step_exception('invalidworkflowid', 'block_workflow'));
        }

        // Check for a step name.
        if (!isset($step->name) || empty($step->name)) {
            $transaction->rollback(new block_workflow_invalid_step_exception('invalidname', 'block_workflow'));
        }

        // Check for instructions.
        if (!isset($step->instructions)) {
            $transaction->rollback(new block_workflow_invalid_step_exception('invalidinstructions', 'block_workflow'));
        }

        // We don't allow a stepid to be specified at create time.
        unset($step->id);

        // This has the effect of checking the specified workflowid is valid.
        try {
            $this->workflow = new block_workflow_workflow($step->workflowid);
        } catch (Exception $e) {
            $transaction->rollback($e);
        }

        if ($beforeafter !== 0) {
            if ($beforeafter < 0) {
                // A negative beforeafter is the same as $beforeafter - 1.
                $beforeafter = abs($beforeafter) - 1;
            }
            // Renumber the steps from $beforeafter.
            // Placing this step after the specified step.
            $this->workflow->renumber_steps($beforeafter, 1);
            $step->stepno = $beforeafter + 1;
        } else {
            // Retrieve the stepno from the final step for this workflow.
            $sql = 'SELECT stepno FROM {block_workflow_steps} WHERE workflowid = ? ORDER BY stepno DESC LIMIT 1';
            $step->stepno = $DB->get_field_sql($sql, array($step->workflowid));

            if ($step->stepno) {
                // If there's already a step on this workflow, add to that step number.
                $step->stepno++;
            } else {
                // No steps yet for this workflow, this is step 1.
                $step->stepno = 1;
            }
        }

        // Check that each of the submitted data is a valid field.
        $expectedsettings = $this->expected_settings();
        foreach ((array) $step as $k => $v) {
            if (!in_array($k, $expectedsettings)) {
                $transaction->rollback(new block_workflow_invalid_step_exception(get_string('invalidfield', 'block_workflow', $k)));
            }
        }

        // Validate any onactivescript and oncompletescript.
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

        // Create the step.
        $step->id = $DB->insert_record('block_workflow_steps', $step);

        $transaction->allow_commit();

        // Reload the object using the returned step id and return it.
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

        // Retrieve the source and copy it.
        $src = new block_workflow_step($srcid);
        $dst = new stdClass();

        // Copy the source based on the allowed settings.
        foreach ($src->expected_settings() as $k) {
            $dst->$k = $src->$k;
        }

        // Unset the id on the target.
        unset($dst->id);

        // If a new workflowid was specified, then use it instead.
        if ($workflowid) {
            $dst->workflowid = $workflowid;
        }

        // Create the step.
        $newstep = new block_workflow_step();
        $newstep->create_step($dst);

        // Clone the todos.
        foreach ($src->todos() as $todo) {
            block_workflow_todo::clone_todo($todo->id, $newstep->id);
        }

        // Clone the roles.
        foreach ($src->roles() as $role) {
            $doer = new stdClass();
            $doer->stepid = $newstep->id;
            $doer->roleid = $role->id;
            $DB->insert_record('block_workflow_step_doers', $doer);
        }

        // Allow the transaction at this stage, and return the newly
        // created object.
        $transaction->allow_commit();

        // Reload the object using the returned step id and return it.
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

        // Check whether this step may be removed and throw errors if required.
        $this->require_deletable();

        $transaction = $DB->start_delegated_transaction();

        // Retrieve a list of the step_states.
        $states = $DB->get_records('block_workflow_step_states', array('stepid' => $this->id), null, 'id');
        $statelist = array_map(create_function('$a', 'return $a->id;'), $states);

        // Remove all of the state_change history.
        $DB->delete_records_list('block_workflow_state_changes', 'stepstateid', $statelist);

        // Remove the todo_done entries.
        $DB->delete_records_list('block_workflow_todo_done', 'stepstateid', $statelist);

        // Remove the states.
        $DB->delete_records('block_workflow_step_states', array('stepid' => $this->id));

        // Update the atengobacktostep setting for the workflow if required.
        $workflow           = $this->workflow();
        $atendgobacktostep  = $workflow->atendgobacktostep;
        if ($atendgobacktostep && $atendgobacktostep > 1 && $this->stepno <= $atendgobacktostep) {
            $workflow->atendgobacktostep($atendgobacktostep - 1);
        }

        // Remove the step.
        $DB->delete_records('block_workflow_steps', array('id' => $this->id));

        // Now that the step has been removed, renumber the remaining step numbers.
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

        // Retrieve the id for the current step.
        $data->id = $this->id;

        // Check that any specified workflow exists.
        if (isset($data->workflowid)) {
            try {
                new block_workflow_workflow($data->workflowid);
            } catch (Exception $e) {
                $transaction->rollback($e);
            }
        }

        // Check that each of the submitted data is a valid field.
        $expectedsettings = $this->expected_settings();
        foreach ((array) $data as $k => $v) {
            if (!in_array($k, $expectedsettings)) {
                $transaction->rollback(new block_workflow_invalid_step_exception(
                        get_string('invalidfield', 'block_workflow', $k)));
            }
        }

        if (empty($data->autofinish)) {
            $data->autofinish = null;
        }
        if (empty($data->autofinishoffset)) {
            $data->autofinishoffset = 0;
        }

        // Validate any changes to the onactivescript and oncompletescript.
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

        // Update the record.
        $DB->update_record('block_workflow_steps', $data);

        $transaction->allow_commit();

        // Return the updated step object.
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
     * @param   int $id The ID of the step.
     * @return  int The number of times the step is in use
     */
    public static function is_step_in_use($stepid) {
        global $DB;
        return $DB->count_records('block_workflow_step_states',
                array('stepid' => $stepid, 'state' => BLOCK_WORKFLOW_STATE_ACTIVE));
    }

    /**
     * Determine whether this step is currently in use
     *
     * @return  int The number of times the step is in use
     */
    public function in_use() {
        return self::is_step_in_use($this->id);
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
        // A step may only be removed if it isn't actively in use.
        if (($count = $this->in_use()) != 0) {
            return false;
        }

        // A step may only be removed if there are other steps in the workflow.
        $steps = $this->workflow()->steps();
        if (count($steps) == 1) {
            return false;
        }

        // All conditions must be met if getting to this point.
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

        // Contexts are associated to a step by the step_state table.
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
        // Our return place-holder.
        $return = new stdClass();
        $return->errors     = array();
        $return->commands   = array();

        // Break the script into lines.
        $lines = preg_split('~[\r\n]+~', $script, null, PREG_SPLIT_NO_EMPTY);

        foreach ($lines as $line) {
            $c = new stdClass();

            // Retrieve the command and arguments.
            $args           = preg_split('/[\s]/', trim($line), 2);
            $c->command     = array_shift($args);
            $c->arguments   = array_shift($args);

            // Skip comments.
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
            } else {
                // Append the current command to the array.
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
        // Parse the script to retrieve a list of all valid commands.
        $commands = self::parse_script($script);

        // Call validate on each command.
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
        // Validate the script.
        $return = self::validate_script($script);

        // Check for errors.
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
        // Validate the script.
        $return = self::validate_script($script);

        // Check for errors.
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
        // Validate the script.
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

        // Parse the script to retrieve a list of all valid commands.
        $commands = self::parse_script($script);

        // Check for errors.
        if ($commands->errors) {
            throw new block_workflow_invalid_command_exception(
                    get_string('invalidscript', 'block_workflow', $commands->errors[0]));

        }

        // Call require_valid and execute on each command.
        // We re-validate each script command in case the specific $state makes them invalid.
        foreach ($commands->commands as $c) {
            $class = block_workflow_command::create($c->classname);
            $class->require_valid($c->arguments, $state->step(), $state);
            $class->execute($c->arguments, $state);
        }

        // We must allow the transaction to be committed before we attempt to process mail.
        $transaction->allow_commit();

        // This is a workaround for a limitation of the message_send system.
        // This must be called outside of a transaction.
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

        // Determine the stepid of the next step.
        $stepid = $DB->get_field('block_workflow_steps', 'id',
                array('workflowid' => $this->workflowid, 'stepno' => ($this->stepno + 1)));

        if ($stepid) {
            // If there is another step, return that step object.
            return new block_workflow_step($stepid);
        }

        if ($stepno = $this->workflow()->atendgobacktostep) {
            // If the workflow has an atendgobacktostep, load that step.
            $return = new block_workflow_step();
            return $return->load_workflow_stepno($this->workflowid, $stepno);
        }

        // No next step, return false.
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

        // Some handy refernces.
        $from   = $this->stepno;
        $to     = $with->stepno;

        // Owing to the unique workflowid, stepno constraint, we need to
        // set the stepno to 0 initially.
        $thisstep = new stdClass();
        $thisstep->id       = $this->id;
        $thisstep->stepno   = 0;
        $DB->update_record('block_workflow_steps', $thisstep);

        // Then update the step we're swapping with.
        $swapstep = new stdClass();
        $swapstep->id       = $with->id;
        $swapstep->stepno   = $from;
        $DB->update_record('block_workflow_steps', $swapstep);

        // Now update this step again.
        $thisstep->stepno   = $to;
        $DB->update_record('block_workflow_steps', $thisstep);

        $transaction->allow_commit();

        // Return the updated step object.
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
            $this->todos = $DB->get_records('block_workflow_step_todos', $params, 'id');
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

        // If the stepid was not specified, load it from the loaded object.
        if (!$stepid) {
            $stepid = $this->id;
        }

        // Retrieve a list of the roles in use.
        // We join to the role table here to retrieve the role name data to
        // avoid additional queries later.
        $sql = 'SELECT r.*
                FROM {block_workflow_step_doers} d
                JOIN {role} r ON r.id = d.roleid
                WHERE d.stepid = ?
                ORDER BY r.shortname ASC';

        return role_fix_names($DB->get_records_sql($sql, array($stepid)));
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
            return $DB->delete_records('block_workflow_step_doers', array('roleid' => $roleid, 'stepid' => $this->id));
        } else {
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
        $instructions = str_replace(array_keys($replaces), array_values($replaces), $this->instructions);
        return format_text($instructions, $this->instructionsformat,
                array('noclean' => true, 'context' => $context));
    }

    public static function get_autofinish_options($appliesto) {
        global $CFG, $DB;

        $options = array();
        $options[''] = get_string('donotautomaticallyfinish', 'block_workflow');
        if ($appliesto === 'course') {
            // The string is stored in the database in the following format.
            // {database table name};{field name with value as timestamp or
            // date-string which can be converted to timestamp}.
            // For instance, course;startdate, quiz;timeopen, quiz;timeclose
            // 'vl_v_crs_version_pres;vle_student_open_date'.
            $days = self::get_list_of_days(self::DAYS_BEFORE_COURSE, self::DAYS_AFTER_COURSE);

            // Here we are using 'db-tablename;relevatfield' as array key.
            $options['course;startdate'] = get_string('coursestartdate', 'block_workflow');

            // Check whether vl_v_crs_version_pres table exists.
            if (!empty($CFG->hasdataloadtables)) {
                // Here we are using 'db-tablename;relevatfield' as array key.
                $options['vl_v_crs_version_pres;vle_student_open_date'] = get_string('coursestudentopen', 'block_workflow');
                $options['vl_v_crs_version_pres;vle_student_close_date'] = get_string('coursestudentclose', 'block_workflow');
                $options['vl_v_crs_version_pres;vle_tutor_open_date'] = get_string('coursetutoropen', 'block_workflow');
                $options['vl_v_crs_version_pres;vle_tutor_close_date'] = get_string('coursetutorclose', 'block_workflow');
            }
        } else if ($appliesto === 'quiz' || $appliesto === 'externalquiz') {
            // The workflow applies to the quiz or external quiz.
            // It could have apply to other course mdoules if relevat db-fields had the same field name.

            // We are using the same constants for quiz and external quiz.
            $days = self::get_list_of_days(self::DAYS_BEFORE_QUIZ, self::DAYS_AFTER_QUIZ);

            // Here we are using 'db-tablename;relevatfield' as array key.
            $options["$appliesto;timeopen"] = get_string('quizopendate', 'block_workflow');
            $options["$appliesto;timeclose"] = get_string('quizclosedate', 'block_workflow');
        } else {
            $days = self::get_list_of_days(0, 0);
        }

        return array($options, $days);
    }

    /**
     * Returns an array of strings which starts from the maximum number of days before,
     * which is a negative number. This increments by 1 until maximum number of days after
     * has been reached. Each number is translated into a string. Negative numbers shows
     * as 'N days before', 0 translated to 'same day as' and positive numbers are
     * ranslated as 'N days after', whereby, singular and plural presentation of
     * day (dys vs daY) is taken into account.
     *
     * @param int $daysbefore
     * @param int $daysafter
     * @return object $days, array of strings
     */
    private static function get_list_of_days($daysbefore, $dayafter) {
        $days = array();
        $secondsinday = 24 * 60 * 60;
        for ($count = $daysbefore; $count <= $dayafter; $count++) {
            if ($count < 0) {
                $daysbefore = 'daysbefore';
                if ($count === -1) {
                    $daysbefore = 'daybefore';
                }
                $days[$count * $secondsinday] = get_string($daysbefore, 'block_workflow', abs($count));
            }
            if ($count == 0) {
                $days[$count * $secondsinday] = get_string('dayas', 'block_workflow');
            }
            if ($count > 0) {
                $daysafter = 'daysafter';
                if ($count === 1) {
                    $daysafter = 'dayafter';
                }
                $days[$count * $secondsinday] = get_string($daysafter, 'block_workflow', $count);
            }
        }
        return $days;
    }
}
