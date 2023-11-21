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

use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;

/**
 * Save/update the comment for the currently loaded step_state
 *
 * @package block_workflow
 * @copyright 2023 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_step_state_comment extends external_api_base {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'stateid' => new external_value(PARAM_INT, 'The ID of the step_state to load', VALUE_REQUIRED),
            'newcomment' => new external_value(PARAM_RAW, 'The text of the new comment', VALUE_REQUIRED),
            'newcommentformat' => new external_value(PARAM_INT, 'The format of the new comment', VALUE_REQUIRED),
        ]);
    }

    /**
     * Update the comment for the currently loaded step_state
     *
     * @param int $stateid the id of the current state
     * @param string $newcomment The text of the new comment
     * @param int $newcommentformat The format of the new comment
     * @return array of newly created comment
     */
    public static function execute(int $stateid, string $newcomment, int $newcommentformat): array {
        [$state, $params] = self::handle_security_check(['stateid' => $stateid, 'newcomment' => $newcomment,
            'newcommentformat' => $newcommentformat], self::execute_parameters());
        $state->update_comment($params['newcomment'], $params['newcommentformat']);
        $result['response'] = shorten_text($newcomment, BLOCK_WORKFLOW_MAX_COMMENT_LENGTH);
        return $result;
    }

    /**
     * Describe the return structure of the external service.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'response' => new external_value(PARAM_RAW),
        ]);
    }
}
