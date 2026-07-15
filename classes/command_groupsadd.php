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
 * Workflow script command to add role members to groups.
 *
 * @package   block_workflow
 * @copyright 2021 Takayuki Nagai, Center for Information Science, Kyoto Institute of Technology
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

/**
 * The command to add role members to a list of groups identified by idnumbers
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2021 Takayuki Nagai
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
class block_workflow_command_groupsadd extends block_workflow_command {
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
     * Note: No exception is thrown if there are no users with specified role.
     */
    public function parse($args, $step, $state = null) {
        // We'll return the components in an object.
        $data = new stdClass();
        $data->errors = array();

        if ($state) {
            $data->context = $state->context();
        }

        // Break down the line. It should be in the format
        // role from idnumber1 idnumber2 ... idnumberN
        // with any number of group idnumbers.
        $line = preg_split('/[\s+]/', $args);

        // Grab the role name.
        $data->role = parent::require_role_exists(array_shift($line), $data->errors);

        // Shift off the 'to' component.
        $to = array_shift($line);
        if ($to !== 'to') {
            $data->errors[] = get_string('invalidsyntaxmissingto', 'block_workflow');
            return $data;
        }

        // Check whether the specified groups exist and fill the list of target users.
        $data->groups = array();
        $data->users = array();

        if($state) {
            // Get users who has the specified role.
            $thisrole = $data->role;
            if ($thisrole) {
                // We can only get the list of users if we've got a specific context.
                $data->users = array_merge($data->users, parent::role_users($thisrole, $data->context));
            }

            $courseid = context::instance_by_id($state->contextid)->get_course_context()->instanceid;

            // Check that each group with the specified idnumber exists
            foreach ($line as $idnumber) {
                // Check that the group exists.

                // (courseid,idnumber) -> (group)
                if($thisgroup = groups_get_group_by_idnumber($courseid,$idnumber)) {
                    $data->groups[] = $thisgroup;
                } else {
                    $data->errors[] = get_string('invalididnumber', 'block_workflow');
                }
            }

            // Check that some groups were specified.
            if (count($data->groups) <= 0) {
                $data->errors[] = get_string('nogroupsspecified', 'block_workflow');
                return $data;
            }
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
        global $CFG;
        require_once("$CFG->dirroot/group/lib.php");

        $data = $this->parse($args, $state->step(), $state);
        foreach ($data->groups as $group) {
            foreach ($data->users as $user){
                groups_add_member($group->id,$user->id,'block_workflow', $state->id);
            }
        }
    }
}
