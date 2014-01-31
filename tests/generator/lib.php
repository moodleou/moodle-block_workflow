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

class block_workflow_generator extends phpunit_block_generator {

    /**
     * Create new block instance
     * @param array|stdClass $record
     * @param array $options
     * @return stdClass activity record with extra cmid field
     */
    public function create_instance($record = null, array $options = null) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/blocks/workflow/locallib.php');

        $this->instancecount++;

        $record = (object)(array)$record;
        $options = (array)$options;

        $record = $this->prepare_record($record);

        $id = $DB->insert_record('block_instances', $record);
        context_block::instance($id);

        $instance = $DB->get_record('block_instances', array('id' => $id), '*', MUST_EXIST);
        return $instance;
    }

    protected function create_workflow($createstep = true) {
        // Create a new workflow.
        $data = new stdClass();
        $data->shortname            = 'courseworkflow';
        $data->name                 = 'First Course Workflow';
        $data->description          = 'This is a test workflow applying to a course for the unit test';

        // Create a new workflow object.
        $workflow = new block_workflow_workflow();

        // The method create_workflow will return a completed workflow object.
        $workflow->create_workflow($data, $createstep);
        return $workflow;
    }

    protected function create_step($workflow) {
        // Create a new step.
        $step = new block_workflow_step();
        $data = new stdClass();
        $data->workflowid = $workflow->id;
        $data->name = 'STEP_ONE';
        $data->instructions = '';
        $step->create_step($data);
        return $step;
    }

    protected function create_email($shortname = 'TESTMAIL') {
        // Create a new todo.
        $email  = new block_workflow_email();
        $data   = new stdClass();
        $data->shortname   = $shortname;
        $data->message     = 'Example e-mail';
        $data->subject     = 'Example subject';
        $email->create($data);
        return $email;
    }

    protected function create_todo($step) {
        // Create a new todo.
        $todo = new block_workflow_todo();
        $data = new stdClass();
        $data->stepid   = $step->id;
        $data->task     = 'TASK ONE';
        $todo->create_todo($data);
        return $todo;
    }
}
