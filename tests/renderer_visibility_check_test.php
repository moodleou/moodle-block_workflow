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
 * Tests for block_workflow_renderer visibility check methods.
 *
 * @package   block_workflow
 * @copyright 2026 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group block_workflow
 */

namespace block_workflow;

defined('MOODLE_INTERNAL') || die();

// Include the shared test library for block_workflow helpers.
global $CFG;
require_once(dirname(__FILE__) . '/lib.php');

/**
 * Tests for the visibility check methods on {@link block_workflow_renderer}.
 *
 * Covers get_visibility_check_status() and block_display_visibility_check()
 * for quiz (module), externalquiz (module), and course contexts.
 *
 * Extends block_workflow_testlib (which extends advanced_testcase) to access
 * the workflow creation helpers required by integration tests.
 *
 * @copyright 2026 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class renderer_visibility_check_test extends \block_workflow_testlib {
    /**
     * Returns an initialised renderer for the block_workflow component.
     *
     * @return \block_workflow_renderer
     */
    private function get_renderer(): \block_workflow_renderer {
        global $PAGE;
        $PAGE->set_url('/');
        return $PAGE->get_renderer('block_workflow');
    }

    /**
     * Quiz workflow triggers block_display_quiz_extra_information and renders the section.
     *
     * @covers \block_workflow_renderer::block_display_quiz_extra_information
     */
    public function test_dispatcher_calls_quiz_method(): void {
        $workflow = $this->create_activity_workflow('quiz', false);
        $this->create_step($workflow);
        $state = $this->assign_workflow($workflow);

        $renderer = $this->get_renderer();
        $html = $renderer->block_display_quiz_extra_information($state);

        $this->assertNotSame('', $html);
        $this->assertStringContainsString('block-workflow-visibility-check', $html);
        $this->assertStringContainsString(get_string('visibilitycheck', 'block_workflow'), $html);
    }

    /**
     * Externalquiz workflow triggers block_display_externalquiz_extra_information and renders the section.
     *
     * @covers \block_workflow_renderer::block_display_externalquiz_extra_information
     */
    public function test_dispatcher_calls_externalquiz_method(): void {
        if (!\core_plugin_manager::instance()->get_plugin_info('mod_externalquiz')) {
            $this->markTestSkipped();
        }
        // Create an externalquiz activity in the test course so assign_workflow() can find it.
        $this->getDataGenerator()->get_plugin_generator('mod_externalquiz')
            ->create_instance(['course' => $this->courseid]);

        $workflow = $this->create_activity_workflow('externalquiz', false);
        $this->create_step($workflow);
        $state = $this->assign_workflow($workflow);

        $renderer = $this->get_renderer();
        $html = $renderer->block_display_externalquiz_extra_information($state);

        $this->assertNotSame('', $html);
        $this->assertStringContainsString('block-workflow-visibility-check', $html);
        $this->assertStringContainsString(get_string('visibilitycheck', 'block_workflow'), $html);
    }

    /**
     * Data provider for {@see test_extra_info_date_not_set}.
     * @return array<string, array{string, string}>
     */
    public static function date_field_provider(): array {
        return [
            'timeopen zeroed by step command'  => ['timeopen', 'visibilitycheck_opendate'],
            'timeclose zeroed by step command' => ['timeclose', 'visibilitycheck_closedate'],
        ];
    }

    /**
     * When a workflow step zeros a date field, the section shows 'Not set' and a warning indicator.
     *
     * @dataProvider date_field_provider
     * @covers \block_workflow_renderer::block_display_quiz_extra_information
     * @covers \block_workflow_renderer::render_quiz_visibility_check
     * @param string $field     Quiz DB field name ('timeopen' or 'timeclose').
     * @param string $stringkey Lang string key for the label line.
     */
    public function test_extra_info_date_not_set(string $field, string $stringkey): void {
        global $DB;

        $workflow = $this->create_activity_workflow('quiz', false);
        $step = $this->create_step($workflow);

        // Step command zeros the field.
        $stepdata = new \stdClass();
        $stepdata->onactivescript = "setactivitysetting $field to 0";
        $step->update_step($stepdata);

        // Pre-set the field to a future timestamp so the command (not a DB default) produces 'Not set'.
        $DB->set_field('quiz', $field, time() + DAYSECS, ['course' => $this->courseid]);

        // Activating the workflow runs the onactivescript, zeroing the field.
        $state = $this->assign_workflow($workflow);

        $renderer = $this->get_renderer();
        $html = $renderer->block_display_quiz_extra_information($state);

        // Label line must read 'Not set'.
        $this->assertStringContainsString(
            get_string($stringkey, 'block_workflow', get_string('visibilitycheck_nodateset', 'block_workflow')),
            $html
        );
        // A warning indicator must be present.
        $this->assertStringContainsString('block-workflow-vischeck-warning', $html);
    }

    /**
     * When a workflow step with 'setactivityvisibility hidden' is activated,
     * the extra-info section shows 'Hidden' and an OK indicator.
     *
     * @covers \block_workflow_renderer::block_display_quiz_extra_information
     * @covers \block_workflow_renderer::render_quiz_visibility_check
     */
    public function test_extra_info_visibility_hidden(): void {
        $workflow = $this->create_activity_workflow('quiz', false);
        $step = $this->create_step($workflow);

        // Set the step's onactivescript to hide the activity.
        $stepdata = new \stdClass();
        $stepdata->onactivescript = 'setactivityvisibility hidden';
        $step->update_step($stepdata);

        // The quiz course module is visible by default; activating the workflow
        // runs the onactivescript which calls set_coursemodule_visible(cm->id, 0).
        $state = $this->assign_workflow($workflow);

        $renderer = $this->get_renderer();
        $html = $renderer->block_display_quiz_extra_information($state);

        // The 'Quiz availability' heading must be present.
        $this->assertStringContainsString(
            get_string('visibilitycheck_quizavailability', 'block_workflow'),
            $html
        );
        // Visibility line should read 'Hidden'.
        $this->assertStringContainsString(
            get_string('visibilitycheck_hidden', 'block_workflow'),
            $html
        );
        // An OK indicator must be present because hidden=0 is the desired pre-release state.
        $this->assertStringContainsString('block-workflow-vischeck-ok', $html);
    }

    /**
     * When a quiz has a future open date, a future close date after the open date, and is hidden,
     * all three visibility checks show OK indicators and no warnings appear.
     *
     * @covers \block_workflow_renderer::block_display_quiz_extra_information
     * @covers \block_workflow_renderer::render_quiz_visibility_check
     */
    public function test_extra_info_all_ok(): void {
        global $DB;

        $workflow = $this->create_activity_workflow('quiz', false);
        $step = $this->create_step($workflow);

        // Step only hides the activity; open/close dates are set directly to future values.
        $stepdata = new \stdClass();
        $stepdata->onactivescript = 'setactivityvisibility hidden';
        $step->update_step($stepdata);

        $now = time();
        $DB->set_field('quiz', 'timeopen', $now + DAYSECS, ['course' => $this->courseid]);
        $DB->set_field('quiz', 'timeclose', $now + (2 * DAYSECS), ['course' => $this->courseid]);

        // Activating the workflow hides the activity; dates remain future.
        $state = $this->assign_workflow($workflow);

        $renderer = $this->get_renderer();
        $html = $renderer->block_display_quiz_extra_information($state);

        // No warning indicators, all three checks are satisfied.
        $this->assertStringNotContainsString('block-workflow-vischeck-warning', $html);
        // Exactly three OK indicators, one each for open date, close date, and hidden visibility.
        $this->assertSame(3, substr_count($html, 'block-workflow-vischeck-ok'));
    }
}
