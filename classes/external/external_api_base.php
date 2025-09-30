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

namespace block_workflow\external;

use core_external\external_api;
use core_external\external_function_parameters;
use block_workflow_step_state;
use block_workflow_ajax_exception;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/blocks/workflow/locallib.php');

/**
 * Overriding the api base class for common functions.
 *
 * @package block_workflow
 * @copyright 2023 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class external_api_base extends external_api {

    /**
     * Handle security check when we call the webservice.
     *
     * @param array $params
     * @param external_function_parameters $decription
     * @return array [$state, $validatedparams] the current state and the validated params.
     */
    public static function handle_security_check(array $params, external_function_parameters $decription): array {
        $validatedparams = self::validate_parameters($decription, $params);
        $state = new block_workflow_step_state($validatedparams['stateid']);
        if (!block_workflow_can_make_changes($state)) {
            throw new block_workflow_ajax_exception(get_string('notallowedtodothisstep', 'block_workflow'));
        }
        [$context, $course, $cm] = get_context_info_array($state->contextid);
        self::validate_context($context);
        return [$state, $validatedparams];
    }
}
