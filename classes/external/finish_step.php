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
use block_workflow_workflow;

/**
 * Finish step webservice.
 *
 * @package block_workflow
 * @copyright 2023 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class finish_step extends external_api_base {
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'stateid' => new external_value(PARAM_INT, 'The ID of the step_state to load', VALUE_REQUIRED),
            'text' => new external_value(PARAM_RAW, 'The text of the new comment', VALUE_REQUIRED),
            'format' => new external_value(PARAM_INT, 'The format of the new comment', VALUE_REQUIRED),
        ]);
    }

    /**
     * Finish the step.
     *
     * @param int $stateid the id of the current state
     * @param string $text The text of the new comment
     * @param int $format The format of the new comment
     * @return array of The next state or false if there is none
     */
    public static function execute(int $stateid, string $text, int $format): array {
        global $PAGE;
        [$state, $params] = self::handle_security_check(
            ['stateid' => $stateid, 'text' => $text, 'format' => $format],
            self::execute_parameters()
        );
        $renderer = $PAGE->get_renderer('block_workflow');

        // Retrieve the next step.
        $newstate = $state->finish_step($params['text'], $params['format']);
        $canview = ($newstate) ? has_capability('block/workflow:view', $newstate->context()) : false;

        if ($newstate && ($canview || block_workflow_can_make_changes($newstate))) {
            // There is a next possible state, and the current user may view and/or work on it.
            $result['response'] = $renderer->block_display($newstate, true);
            $result['stateid'] = $newstate->id;
        } else if ($newstate) {
            // There is a new step, but this user can't view it, and can't work on it ...
            $result['response'] = $renderer->block_display_step_complete_confirmation();
        } else {
            // Last step has been reached, if permitted retrieve the list of workflows.
            $workflows = new block_workflow_workflow();
            $previous = $workflows->load_context_workflows($state->contextid);
            $canadd = has_capability('block/workflow:manage', $state->context());
            $appliesto = $state->step()->workflow()->appliesto;
            $addableworkflows = block_workflow_workflow::available_workflows($appliesto);
            $result['listworkflows'] = $canadd && $addableworkflows;
            $result['response'] = $renderer->block_display_no_more_steps(
                $state->contextid,
                $canadd,
                $addableworkflows,
                $previous
            );
        }
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
            'stateid' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
            'listworkflows' => new external_value(PARAM_BOOL, '', VALUE_OPTIONAL),
        ]);
    }
}
