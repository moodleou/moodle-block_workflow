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
 * Workflow script command to override a permission.
 *
 * @package   block_workflow
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');


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

        // Break down the line.
        $line = preg_split('/[\s+]/', $args);

        // Grab the role name.
        $data->role = parent::require_role_exists(array_shift($line), $data->errors);
        if ($data->errors) {
            // Return early if we hit errors.
            return $data;
        }

        // Grab the override.
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

        // And the capability.
        $cap = array_shift($line);
        if (!get_capability_info($cap)) {
            $data->errors[] = get_string('invalidcapability', 'block_workflow');
            return $data;
        }
        $data->capability = $cap;

        // What is it being overridden in?
        array_shift($line);
        $in = array_shift($line);

        if ($this->is_course($step->workflow()) && $in != 'course') {
            $data->errors[] = get_string('notacourse', 'block_workflow');
            return $data;
        }

        switch ($in) {
            case "course":
                if ($state) {
                    // If we're actually running this, determine the relevant contextid.
                    if ($this->is_course($step->workflow())) {
                        // Changing the contextid on the workflow's context.
                        $data->contextid = $state->contextid;
                    } else {
                        // Changing the contextid on the workflow's parent context.
                        $data->contextid = $state->context()->get_parent_context()->id;
                    }
                }
                break;
            case "activity":
                if ($this->is_course($step->workflow())) {
                    // You can't change activity permissions on a course.
                    $data->errors[] = get_string('notacourse', 'block_workflow');
                } else if ($state) {
                    // Changing the contextid on the workflow's context.
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
