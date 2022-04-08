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
 * Steps definitions related to the workflow block.
 *
 * @package   block_workflow
 * @category  test
 * @copyright 2020 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Mink\Exception\ExpectationException;

/**
 * Steps definitions related to the workflow block.
 */
class behat_block_workflow extends behat_base {

    /**
     * Add the workflow block, and select a particular workflow, in a given context.
     *
     * @Given quiz :quizname has workflow :workflowshortname applied
     * @param string $quizname the name of the model quiz.
     * @param string $workflowshortname the name of the workflow that applies to quiz
     */
    public function quiz_has_workflow_applied($quizname, $workflowshortname) {
        global $DB;
        $generator = testing_util::get_data_generator();

        // Find the objects of interest.
        $quiz = $DB->get_record('quiz', ['name' => $quizname], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $quiz->course);
        $context = context_module::instance($cm->id);

        $workflow = $DB->get_record('block_workflow_workflows',
                ['shortname' => $workflowshortname, 'appliesto' => 'quiz'], '*', MUST_EXIST);

        // Add the block.
        $data = [
            'blockname' => 'workflow',
            'pagetypepattern' => 'mod-quiz-*',
            'parentcontextid' => $context->id,
        ];
        $generator->create_block($data['blockname'], $data, $data);

        // Select the workflow.
        $workflow = new block_workflow_workflow($workflow->id);
        $workflow->add_to_context($context->id);
    }

    /**
     * Jump the workflow on a particular quiz to a given step.
     *
     * This currently assumes the step name is globally unique.
     *
     * @Given the :quizname quiz workflow is at step :stepname
     * @param string $quizname the name of the model quiz.
     * @param string $stepname the name of the step to go to.
     */
    public function quiz_workflow_is_at_step($quizname, $stepname) {
        global $DB;

        // Find the objects of interest.
        $quiz = $DB->get_record('quiz', ['name' => $quizname], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $quiz->course);
        $context = context_module::instance($cm->id);

        $step = $DB->get_record('block_workflow_steps', ['name' => $stepname], '*', MUST_EXIST);

        // Grab the current state and the intended state.
        $curretstate = new block_workflow_step_state();
        $curretstate->load_active_state($context->id);
        $curretstate->jump_to_step(null, $step->id);
    }

    /**
     * Check the course visibility.
     *
     * @Then /^course "(?P<coursefullname_string>(?:[^"]|\\")*)" is (hidden|visible) for block_workflow$/
     * @param string $coursefullname The full name of the course.
     * @param string $isvisible Visible|Hidden.
     */
    public function test_course_visibility(string $coursefullname, string $isvisible): void {
        global $DB;
        $course = $DB->get_record("course", array("fullname" => $coursefullname), 'visible', MUST_EXIST);
        $expectedvisibility = $isvisible == 'visible';
        if ($course->visible != $expectedvisibility) {
            throw new ExpectationException('"' . $coursefullname . '" should be ' . $isvisible . ' but isn\'t.', $this->getSession());
        }
    }
}
