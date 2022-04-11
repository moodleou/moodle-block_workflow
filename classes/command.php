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
 * Base class for workflow script commands.
 *
 * @package   block_workflow
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * The abstract class that each workflow command should extend
 *
 * This class also provides some additional helper functions which the various commands may use
 *
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
        // Parse the script to grab any errors.
        $return = $this->parse($args, $step, $state);

        if ($return->errors) {
            // Throw an exception -- only show the first error.
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
        return get_role_users($role->id, $context, false, 'u.*, u.id', 'u.id');
    }

    /**
     * Helper function to determine whether the specified workflow applies to an activity
     *
     * @param   object $workflow The workflow to be tested
     * @return  Boolean
     */
    public static function is_activity($workflow) {
        // All workflows barring courses are activities.
        return $workflow->appliesto != 'course';
    }

    /**
     * Helper function to determine whether the specified context belongs to directly a course
     *
     * @param   object $workflow The workflow to test
     * @return  Boolean
     */
    public function is_course($workflow) {
        // Only 'course' is a course.
        return ($workflow->appliesto == 'course');
    }

    protected function check_next_word_is($requiredword, $actualword, $data) {
        if ($actualword != $requiredword) {
            $data->errors[] = get_string('invalidsyntaxmissingx', 'block_workflow', $requiredword);
            return false;
        }
        return true;
    }
}
