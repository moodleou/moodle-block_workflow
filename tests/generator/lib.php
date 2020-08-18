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
 * Workflow block test helper code.
 *
 * @package   block_workflow
 * @copyright 2012 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/workflow/locallib.php');


class block_workflow_generator extends testing_block_generator {

    /**
     * Create a workflow.
     *
     * @param array $record workflow data. All optional. Defaults are used.
     * @return block_workflow_workflow
     */
    public function create_workflow(array $record): block_workflow_workflow {
        $data = (object) $record;

        // Supply default vaues where needed.
        if (!isset($data->shortname)) {
            $data->shortname = 'courseworkflow';
        }
        if (!isset($data->name)) {
            $data->name = 'First Course Workflow';
        }
        if (!isset($data->description)) {
            $data->description = 'This is a test workflow applying to a course for the unit test';
        }

        // Extract createstep option.
        $createstep = !empty($data->createstep);
        unset($data->createstep);

        // Create the workflow.
        $workflow = new block_workflow_workflow();
        $workflow->create_workflow($data, $createstep);
        return $workflow;
    }

    public function create_workflow_step(array $record) {
        $data = (object) $record;

        if (!isset($data->workflowid)) {
            throw new coding_exception('workflowid is required when creating a workflow step');
        }
        if (!isset($data->name)) {
            $data->name = 'STEP_ONE';
        }
        if (!isset($data->instructions)) {
            $data->instructions = '';
        }

        // Create a new step.
        $step = new block_workflow_step();
        $step->create_step($data);
        return $step;
    }

    public function create_email($shortname = 'TESTMAIL') {
        // Create a new email template.
        $email  = new block_workflow_email();
        $data   = new stdClass();
        $data->shortname   = $shortname;
        $data->message     = 'Example e-mail';
        $data->subject     = 'Example subject';
        $email->create($data);
        return $email;
    }

    public function create_todo($step) {
        // Create a new todo.
        $todo = new block_workflow_todo();
        $data = new stdClass();
        $data->stepid   = $step->id;
        $data->task     = 'TASK ONE';
        $todo->create_todo($data);
        return $todo;
    }
}
