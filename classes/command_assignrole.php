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
 * Workflow script command to assign a role.
 *
 * @package   block_workflow
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');


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
