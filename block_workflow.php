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
 * Workflow block
 *
 * @package   block_workflow
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;
require_once($CFG->dirroot . '/lib/form/editor.php');

class block_workflow extends block_base {
    public function init() {
        global $CFG;
        $this->title = get_string('workflow', 'block_workflow');
        require_once($CFG->dirroot . '/blocks/workflow/locallib.php');
    }

    /**
     * Retrieve the contents of the block
     *
     * If the current context has a workflow assigned to it, then the
     * current state of the workflow, with current comments, instructions,
     * and other informative data is displayed.
     *
     * If no workflow is currently assigned to this context, and the user
     * has permission to manage workflows, then the option to select a
     * workflow valid for the context is displayed. If no workflows are
     * available for the context, then the block is not displayed.
     *
     * If no workflow is currently assinged to this context, and the user
     * does not have permission to manage workflows, then the block is not
     * displayed.
     *
     * @return  stdClass    containing the block's content
     */
    public function get_content() {
        global $PAGE;

        // Save loops if we have generated the content already.
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content  = new stdClass();

        if (!has_capability('block/workflow:view', $this->context)) {
            // We require the workflow:view capability at the very least.
            return $this->content;
        }

        $renderer = $this->page->get_renderer('block_workflow');

        $state = new block_workflow_step_state();
        // Retrieve the active state for this contextid.
        if ($state->load_active_state($this->instance->parentcontextid)) {

            // Update block title.
            $this->title = $state->step()->workflow()->name;

            // Prepare editor.
            $editor = new MoodleQuickForm_editor('comment_editor', null,
                    array('id' => 'wkf-comment-editor'), block_workflow_editor_options());
            $editor->setValue(array('text' => $state->comment));

            // Include the javascript libraries:
            // Add language strings.
            $PAGE->requires->strings_for_js(array('editcomments', 'nocomments', 'finishstep'), 'block_workflow');
            $PAGE->requires->strings_for_js(array('savechanges'), 'moodle');

            // Initialise the YUI module.
            $arguments = array(
                'stateid' => $state->id,
                'editorhtml' => $editor->toHtml(),
                'editorid' => $editor->getAttribute('id'),
                'editorname' => $editor->getName(),
            );
            $PAGE->requires->yui_module('moodle-block_workflow-comments', 'M.blocks_workflow.init_comments',
                array($arguments));
            $PAGE->requires->yui_module('moodle-block_workflow-todolist', 'M.blocks_workflow.init_todolist',
                array(array('stateid' => $state->id)));

            // Display the block for this state.
            $this->content->text = $renderer->block_display($state);
        } else {
            // The parent context currently has no workflow assigned.
            if (!has_capability('block/workflow:manage', $this->context)) {
                // We require workflow:manage to add a workflow.
                return $this->content;
            }

            // If this is a module, retrieve it's name, otherwise try the pagelayout to confirm
            // that this is a course.
            if ($PAGE->cm) {
                $appliesto = $PAGE->cm->modname;
            } else {
                $appliesto = 'course';
            }

            // Retrieve the list of workflows and display.
            $workflows = new block_workflow_workflow();
            $this->content->text = $renderer->assign_workflow($this->instance->parentcontextid,
                    block_workflow_workflow::available_workflows($appliesto),
                    $workflows->load_context_workflows($this->instance->parentcontextid));
        }

        return $this->content;
    }

    /**
     * Whether to allow multiple instance of the block (we do not)
     *
     * @return  boolean     We do not allow multiple instances of the block in the same context
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * The applicable formats for the block
     *
     * @return  array       An array of the applicable formats for the block
     */
    public function applicable_formats() {
        return array('course' => true, 'mod' => true);
    }

    /**
     * Whether the block has configuration (it does)
     *
     * @return  boolean     We do have configuration
     */
    public function has_config() {
        return true;
    }

    /**
     * Standard blocks api cron function, called every time cron runs.
     */
    public function cron() {
        global $CFG;
        // Call the workflow automatic step finisher.
        require_once(dirname(__FILE__) . '/cronlib.php');
        $bwfasf = new block_workflow_automatic_step_finisher();

        // Rund the cron and finish the steps.
        $bwfasf->cron();
    }
}
