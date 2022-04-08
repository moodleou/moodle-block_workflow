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
 * Workflow block tests
 *
 * @package   block_workflow
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group block_workflow
 */

namespace block_workflow;

use block_workflow_command_setgradeitemvisibility;
use block_workflow_command;

defined('MOODLE_INTERNAL') || die();

// Include our test library so that we can use the same mocking system for all tests.
global $CFG;
require_once(dirname(__FILE__) . '/lib.php');
require_once($CFG->dirroot.'/lib/grade/grade_item.php');

class commandsetgradeitemvisibility_test extends \block_workflow_testlib {
    private function generate_module($modname) {
        global $CFG;
        if (!is_readable($CFG->dirroot . '/mod/externalquiz/tests/generator/lib.php')) {
            $this->markTestSkipped('This test requires mod_externalquiz to be installed.');
        }
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_' . $modname);
        return $generator->create_instance(array('course' => $this->courseid));
    }

    public function test_setgradeitemvisibility() {
        $command = new block_workflow_command_setgradeitemvisibility();
        $this->assertInstanceOf('block_workflow_command', $command);
    }

    public function test_parse_no_state_visible() {
        $workflow = $this->create_activity_workflow('assign', false);
        $step     = $this->create_step($workflow);

        // This should change the visibility to visible.
        $args = 'visible';

        // Try parsing without a context.
        $class = block_workflow_command::create('block_workflow_command_setgradeitemvisibility');
        $result = $class->parse($args, $step);

        // Test: $result should have data.
        $this->assertNotNull($result);

        // There should be no errors.
        $this->assertEquals(count($result->errors), 0);

        // Test: $result should have a visibility state of 0.
        $this->assertEquals($result->visibility, 0);

        // Test: $result should have no id.
        $this->assertFalse(isset($result->id));
    }

    public function test_parse_no_state_hidden() {
        $workflow = $this->create_activity_workflow('assign', false);
        $step     = $this->create_step($workflow);

        // This should change the visibility to hidden.
        $args = 'hidden';

        // Try parsing without a context.
        $class = block_workflow_command::create('block_workflow_command_setgradeitemvisibility');
        $result = $class->parse($args, $step);

        // Test: $result should have data.
        $this->assertNotNull($result);

        // There should be no errors.
        $this->assertEquals(count($result->errors), 0);

        // Test: $result should have a visibility state of 0.
        $this->assertEquals($result->visibility, 1);

        // Test: $result should have no id.
        $this->assertFalse(isset($result->id));
    }

    public function test_parse_no_state_invalid_state() {
        $workflow = $this->create_activity_workflow('assign', false);
        $step     = $this->create_step($workflow);

        // This should change the visibility to invalid.
        $args = 'invalid';

        // Try parsing without a context.
        $class = block_workflow_command::create('block_workflow_command_setgradeitemvisibility');
        $result = $class->parse($args, $step);

        // Test: $result should have data.
        $this->assertNotNull($result);

        // There should be one error.
        $this->assertEquals(count($result->errors), 1);
        $this->assertEquals($result->errors, array(
                get_string('invalidvisibilitysetting', 'block_workflow', $args)));

        // Test: $result should have no visible.
        $this->assertFalse(isset($result->visibility));

        // Test: $result should have no id.
        $this->assertFalse(isset($result->id));
    }

    public function test_parse_no_state_appliestocourse() {
        $workflow = $this->create_workflow(false);
        $step     = $this->create_step($workflow);

        // This should change the visibility to hidden.
        $args = 'hidden';

        // Try parsing without a context.
        $class = block_workflow_command::create('block_workflow_command_setgradeitemvisibility');
        $result = $class->parse($args, $step);

        // Test: $result should have data.
        $this->assertNotNull($result);

        // There should be one error.
        $this->assertEquals(count($result->errors), 1);
        $this->assertEquals($result->errors, array(
                get_string('notanactivity', 'block_workflow', 'setgradeitemvisibility')));

        // Test: $result should have no id.
        $this->assertFalse(isset($result->id));
    }

    public function test_parse_no_state_appliesto_a_activity_which_controls_gradeitemvisibility() {
        $workflow = $this->create_activity_workflow('quiz', false);
        $step = $this->create_step($workflow);

        // This should change the visibility to hidden.
        $args = 'hidden';

        // Try parsing without a context.
        $class = block_workflow_command::create('block_workflow_command_setgradeitemvisibility');
        $result = $class->parse($args, $step);

        // There should be one error.
        $this->assertEquals(count($result->errors), 1);
        $this->assertEquals($result->errors, array(
                get_string('notcontrollablegradeitem', 'block_workflow', 'setgradeitemvisibility')));
    }

    public function test_parse_with_state_visible() {
        $this->generate_module('assign');
        $workflow = $this->create_activity_workflow('assign', false);
        $step     = $this->create_step($workflow);
        $state    = $this->assign_workflow($workflow);

        // This should change the visibility to visible.
        $args = 'visible';

        // Try parsing with a state.
        $class = block_workflow_command::create('block_workflow_command_setgradeitemvisibility');
        $result = $class->parse($args, $step, $state);

        // Test: $result should have data.
        $this->assertNotNull($result);

        // There should be no errors.
        $this->assertEquals(count($result->errors), 0);

        // Test: $result should have a visibility state of 0.
        $this->assertEquals($result->visibility, 0);

        // Test: $result should have a context, step, workflow and cm.
        $this->assertEquals('assign', $result->cm->modname);
    }

    public function test_parse_with_state_hidden() {
        $this->generate_module('forum');
        $workflow = $this->create_activity_workflow('forum', false);
        $step     = $this->create_step($workflow);
        $state    = $this->assign_workflow($workflow);

        // This should change the visibility to hidden.
        $args = 'hidden';

        // Try parsing with a state.
        $class = block_workflow_command::create('block_workflow_command_setgradeitemvisibility');
        $result = $class->parse($args, $step, $state);

        // Test: $result should have data.
        $this->assertNotNull($result);

        // There should be no errors.
        $this->assertEquals(count($result->errors), 0);

        // Test: $result should have a visibility state of 0.
        $this->assertEquals($result->visibility, 1);

        // Test: $result should have a context, step, workflow and cm.
        $this->assertEquals('forum', $result->cm->modname);
    }

    public function test_parse_with_state_invalid_state() {
        $this->generate_module('assign');
        $workflow = $this->create_activity_workflow('assign', false);
        $step     = $this->create_step($workflow);
        $state    = $this->assign_workflow($workflow);

        // This should change the visibility to invalid.
        $args = 'invalid';

        // Try parsing with a state.
        $class = block_workflow_command::create('block_workflow_command_setgradeitemvisibility');
        $result = $class->parse($args, $step, $state);

        // Test: $result should have data.
        $this->assertNotNull($result);

        // There should be one error.
        $this->assertEquals(count($result->errors), 1);

        // Test: $result should have no visibility.
        $this->assertFalse(isset($result->visibility));

        // Test: $result should have a context, step, workflow and cm.
        $this->assertEquals('assign', $result->cm->modname);
    }

    public function test_parse_with_state_appliestocourse() {
        $workflow = $this->create_workflow(false);
        $step     = $this->create_step($workflow);
        $state    = $this->assign_workflow($workflow);

        // This should change the visibility to hidden.
        $args = 'hidden';

        // Try parsing without a context.
        $class = block_workflow_command::create('block_workflow_command_setgradeitemvisibility');
        $result = $class->parse($args, $step, $state);

        // Test: $result should have have the validation error.
        $this->assertEquals(array(get_string('notanactivity', 'block_workflow', 'setgradeitemvisibility')),
                 $result->errors);

        // There should be one error.
        $this->assertCount(1, $result->errors);
    }

    public function test_parse_with_state_no_support_for_grade() {
        $workflow = $this->create_activity_workflow('chat', false);
        $step     = $this->create_step($workflow);
        $state    = $this->assign_workflow($workflow);

        $args = 'visible';

        // Try parsing with a state.
        $class = block_workflow_command::create('block_workflow_command_setgradeitemvisibility');
        $result = $class->parse($args, $step, $state);

        // Test: $result should have have the validation error.
        $this->assertEquals(array(get_string('notgradesupported', 'block_workflow', 'setgradeitemvisibility')),
                $result->errors);

        $this->assertCount(1, $result->errors);
    }

    public function test_execute_hidden() {
        global $DB;
        $assign = $this->generate_module('externalquiz');

        // Check the value of the field hidden in grade_items table before executing setgradeitemvisibility command.
        $hiddenfieldbefore = $DB->get_field('grade_items', 'hidden',
                array('courseid' => $this->courseid, 'itemtype' => 'mod',
                        'itemmodule' => 'externalquiz', 'iteminstance' => $assign->id));
        $this->assertEquals($hiddenfieldbefore, 0);

        // Create a workflow which applies to 'assign' module with one step.
        $workflow = $this->create_activity_workflow('externalquiz', true);
        $state    = $this->assign_workflow($workflow);

        // Create the workflow command 'setgradeitemvisibility hidden'.
        $wfc = block_workflow_command::create('block_workflow_command_setgradeitemvisibility');
        $wfc->execute('hidden', $state);
        $hiddenfieldafter = $DB->get_field('grade_items', 'hidden',
                array('courseid' => $this->courseid, 'itemtype' => 'mod',
                        'itemmodule' => 'externalquiz', 'iteminstance' => $assign->id));
        $this->assertEquals($hiddenfieldafter, 1);
    }

    public function test_execute_visible() {
        global $DB;
        $assign = $this->generate_module('externalquiz');

        $DB->set_field('grade_items', 'hidden', 1,
                array('courseid' => $this->courseid, 'itemtype' => 'mod',
                        'itemmodule' => 'externalquiz', 'iteminstance' => $assign->id));

        // Check the value of the field hidden in grade_items table before executing setgradeitemvisibility command.
        $hiddenfieldbefore = $DB->get_field('grade_items', 'hidden',
                array('courseid' => $this->courseid, 'itemtype' => 'mod',
                        'itemmodule' => 'externalquiz', 'iteminstance' => $assign->id));
        $this->assertEquals($hiddenfieldbefore, 1);

        // Create a workflow which applies to 'assign' module with one step.
        $workflow = $this->create_activity_workflow('externalquiz', true);
        $state    = $this->assign_workflow($workflow);

        // Create the workflow command 'setgradeitemvisibility visible'.
        $wfc = block_workflow_command::create('block_workflow_command_setgradeitemvisibility');
        $wfc->execute('visible', $state);
        $hiddenfieldafter = $DB->get_field('grade_items', 'hidden',
                array('courseid' => $this->courseid, 'itemtype' => 'mod',
                        'itemmodule' => 'externalquiz', 'iteminstance' => $assign->id));
        $this->assertEquals($hiddenfieldafter, 0);
    }
}
