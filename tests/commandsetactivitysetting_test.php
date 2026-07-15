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
 * Tests for block_workflow_command_setactivitysetting.
 *
 * @package   block_workflow
 * @copyright 2026 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group block_workflow
 */

namespace block_workflow;

use block_workflow_command;
use grade_item;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once(dirname(__FILE__) . '/lib.php');
require_once($CFG->libdir . '/grade/grade_item.php');

/**
 * Unit tests for block_workflow_command_setactivitysetting.
 */
final class commandsetactivitysetting_test extends \block_workflow_testlib {
    /**
     * Test that executing setactivitysetting also updates the related grade item visibility.
     *
     * This is a regression test for the bug where changing reviewmarks via a workflow
     * script updated the quiz table but left the grade item hidden/visible state out of sync.
     *
     * The quiz is created with default review settings (marks visible), so the grade item
     * starts with hidden = 0. After setting reviewmarks to 0 (marks hidden in all review
     * periods), the grade item should be updated to hidden = 1.
     *
     * @covers \block_workflow_command_setactivitysetting::execute
     */
    public function test_execute_updates_grade_item_visibility(): void {
        global $DB;

        $workflow = $this->create_activity_workflow('quiz', false);
        $this->create_step($workflow);
        $state = $this->assign_workflow($workflow);

        $sql = "SELECT m.id AS instanceid
                  FROM {quiz} m
            INNER JOIN {course_modules} cm ON cm.instance = m.id
            INNER JOIN {modules} md ON md.id = cm.module
                 WHERE md.name = 'quiz' AND cm.course = ? LIMIT 1";
        $instance = $DB->get_record_sql($sql, [$this->courseid]);

        // The quiz generator defaults enable marks in open and closed review periods,
        // so quiz_grade_item_update sets the grade item to visible on creation.
        $gradeitems = grade_item::fetch_all([
            'itemtype'     => 'mod',
            'itemmodule'   => 'quiz',
            'iteminstance' => $instance->instanceid,
            'courseid'     => $this->courseid,
        ]);
        $this->assertNotEmpty($gradeitems, 'A grade item should exist for the quiz');
        $gradeitem = reset($gradeitems);
        $this->assertEquals(0, $gradeitem->hidden, 'Grade item should start visible');

        // Run the workflow command: hide marks in all review periods.
        $class = block_workflow_command::create('block_workflow_command_setactivitysetting');
        $class->execute('reviewmarks to 0', $state);

        // The quiz DB row must have the new value.
        $quiz = $DB->get_record('quiz', ['id' => $instance->instanceid]);
        $this->assertEquals(0, $quiz->reviewmarks);

        // The grade item must now be hidden.
        $gradeitems = grade_item::fetch_all([
            'itemtype'     => 'mod',
            'itemmodule'   => 'quiz',
            'iteminstance' => $instance->instanceid,
            'courseid'     => $this->courseid,
        ]);
        $gradeitem = reset($gradeitems);
        $this->assertEquals(1, $gradeitem->hidden, 'Grade item should be hidden after reviewmarks set to 0');
    }
}
