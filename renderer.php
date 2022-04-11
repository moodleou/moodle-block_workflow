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
 * Workflow block libraries
 *
 * @package   block_workflow
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * block_workflow Renderer
 *
 * Class for rendering various block_workflow objects
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_workflow_renderer extends plugin_renderer_base {
    /**
     * Render the block for the specified state
     *
     * @param   object  $state  The block_workflow_step_state to render for
     * @return  string          The rendered content
     */
    public function block_display(block_workflow_step_state $state, $ajax = false) {
        global $USER;

        $canmakechanges = block_workflow_can_make_changes($state);

        $output = '';

        // Create the title.
        $output .= html_writer::tag('h3', get_string('activetasktitle', 'block_workflow'));

        $output .= html_writer::tag('p', format_string($state->step()->name));

        // Roles overview.
        if ($roles = $state->step()->roles()) {
            $context = $state->context();
            $output .= html_writer::tag('h3', get_string('tobecompletedby', 'block_workflow'));

            $who = '';
            $whoelse = array();

            // Got through the list.
            foreach ($roles as $role) {
                if (user_has_role_assignment($USER->id, $role->id, $context->id)) {
                    $who = get_string('youandanyother', 'block_workflow');
                }
                $whoelse[] = $role->localname;
            }

            if (empty($who)) {
                // If the current user isn't in the list, make it 'Any ...'.
                $who = get_string('any', 'block_workflow');
            }

            if (count($whoelse)) {
                // If any roles are assigned, grab the last one and leave it to one side.
                $lastrole = array_pop($whoelse);

                if (count($whoelse) > 0) {
                    // If there are still other roles assigned, turn them into a list.
                    $who .= implode(', ', $whoelse);
                    $who .= get_string('youor', 'block_workflow') . $lastrole;
                } else {
                    // Just add the last role.
                    $who .= $lastrole;
                }
            }

            $output .= html_writer::tag('span', $who);
            $output .= $this->get_popup_button($roles, $context);
            $this->page->requires->yui_module('moodle-block_workflow-userinfo', 'M.block_workflow.userinfo.init');
        }

        // Instructions.
        $output .= html_writer::tag('h3', get_string('instructions', 'block_workflow'));

        $output .= html_writer::tag('div', $state->step()->format_instructions($state->context()));

        // Comments.
        $output .= html_writer::tag('h3', get_string('comments', 'block_workflow'));
        $commentsblock = html_writer::start_tag('div', array('class' => 'block_workflow_comments'));
        $commenttext = shorten_text(format_text($state->comment, $state->commentformat,
                array('context' => $state->context())), BLOCK_WORKFLOW_MAX_COMMENT_LENGTH);
        if ($commenttext) {
            $commentsblock .= $commenttext;
        } else {
            $commentsblock .= get_string('nocomments', 'block_workflow');
        }
        $commentsblock .= html_writer::end_tag('div');
        $output .= $commentsblock;

        // To-do list overview.
        if ($todos = $state->todos()) {
            $output .= html_writer::tag('h3', get_string('todolisttitle', 'block_workflow'));
            $list = html_writer::start_tag('ul', array('class' => 'block_workflow_todolist'));
            foreach ($state->todos() as $todo) {
                $list .= $this->block_display_todo_item($todo, $state->id, $canmakechanges);
            }
            $list .= html_writer::end_tag('ul');
            $output .= $list;
        }

        if ($canmakechanges) {
            // Edit comments.
            $url    = new moodle_url('/blocks/workflow/editcomment.php',
                    array('stateid' => $state->id));
            $editbutton = new single_button($url, get_string('editcomments', 'block_workflow'), 'get');
            $editbutton->class = 'singlebutton block_workflow_editcommentbutton';

            $output .= html_writer::tag('div', $this->output->render($editbutton));

            if (!$ajax) {
                // Output the contents of the edit comment dialogue, hidden.
                // Prepare editor.
                $editor = new MoodleQuickForm_editor('comment_editor', get_string('commentlabel', 'block_workflow'),
                        array('id' => 'wkf-comment-editor'), block_workflow_editor_options());
                $editor->setValue(array('text' => $state->comment));

                $output .= '<div class="block-workflow-panel">
                                <form class="wkf-comments" action=".">
                                    <div class="wfk-textarea">' .
                                        html_writer::label(get_string('commentlabel', 'block_workflow'),
                                                'wkf-comment-editor', false, array('class' => 'accesshide')) .
                                        $editor->toHtml() . '
                                    </div>
                                    <div class="wfk-submit">
                                        <input type="button" class="submitbutton" value="' . get_string('submit') . '" />
                                    </div>
                                </form>
                                <div class="loading-lightbox hidden">' .
                                    $this->pix_icon('i/loading', get_string('loading', 'admin'), 'moodle',
                                            array('class' => 'loading-icon')) . '
                                </div>
                            </div>';
            }

            // Finish step.
            $url = new moodle_url('/blocks/workflow/finishstep.php',
                    array('stateid' => $state->id));
            $finishbutton = new single_button($url, get_string('finishstep', 'block_workflow'), 'get');
            $finishbutton->class = 'singlebutton block_workflow_finishstepbutton';

            $output .= html_writer::tag('div', $this->output->render($finishbutton));
        }

        $output .= $this->workflow_overview_button($state->contextid, $state->step()->workflowid);

        return $output;
    }

    /**
     * Display a button to go to the workflow overview.
     * @param int $contextid the context to display the overiew for.
     * @param int $workflowid the workflow to display the overiew for.
     * @return string HTML of the button.
     */
    public function workflow_overview_button($contextid, $workflowid) {
        $url = new moodle_url('/blocks/workflow/overview.php', array(
                'contextid' => $contextid, 'workflowid' => $workflowid));
        $overviewbutton = new single_button($url,
                get_string('workflowoverview', 'block_workflow'), 'get');
        return html_writer::tag('div', $this->output->render($overviewbutton));
    }

    /**
     * Render the given todo list item as a <li> element with appropriate links
     *
     * @param   object  $todo     The todo stdClass to render
     * @param   integer $stateid  The ID of the state to render for (used for links)
     * @param   boolean $editable Whether this user has permission to make changes to todolist items
     * @return  string            The rendered list item
     */
    public function block_display_todo_item($todo, $stateid, $editable) {
        global $CFG;
        $todoattribs = array();

        // The contents of the list item.
        $text = format_string($todo->task);

        // Determine whether the task has been completed.
        if ($todo->userid) {
            $todoattribs['class']  = ' completed';
        }

        if ($editable) {
            // Generate the URL and Link.
            $returnurl = str_replace($CFG->wwwroot, '', $this->page->url->out(false));
            $url = new moodle_url('/blocks/workflow/toggletaskdone.php',
                    array('sesskey' => sesskey(), 'stateid' => $stateid, 'todoid' => $todo->id, 'returnurl' => $returnurl));
            $li  = html_writer::tag('li', html_writer::link($url, $text,
                    array('class' => 'block-workflow-todotask', 'id' => 'block-workflow-todoid-' . $todo->id)),
                    $todoattribs);
        } else {
            $li  = html_writer::tag('li', $text, $todoattribs);
        }

        // Return the generate list item.
        return $li;
    }

    /**
     * Render the content when there is no active workflow.
     * @param $context database record containing the context data
     * @param $addableworkflows array The list of available workflows
     * @param $previous array A list of the previous workflows on this
     * context
     * @return string the HTML to output.
     */
    public function block_display_no_more_steps($parentcontextid,
            $canadd, array $addableworkflows, array $previous = null) {
        $output = '';

        if ($previous) {
            $p = reset($previous);
            $output .= html_writer::tag('p', get_string('nomorestepsleft', 'block_workflow'));
            $output .= $this->workflow_overview_button($parentcontextid, $p->id);
        }

        if (!$canadd) {
            return $output;
        }

        if (!$previous) {
            // No workflow was previously assigned.
            $output .= html_writer::tag('p', get_string('noworkflow', 'block_workflow'));
        }

        if ($addableworkflows) {
            $url = new moodle_url('/blocks/workflow/addworkflow.php',
                    array('sesskey' => sesskey(), 'contextid' => $parentcontextid));

            $addoptions = array();
            foreach ($addableworkflows as $wf) {
                $addoptions[$wf->id] = $wf->name;
            }
            $list = new single_select($url, 'workflow', $addoptions);
            if ($previous) {
                $list->set_label(get_string('addanotherworkflow', 'block_workflow'));
            } else {
                $list->set_label(get_string('addaworkflow', 'block_workflow'));
            }

            // And generate the output.
            $output .= html_writer::tag('div', $this->output->render($list));
        }

        return $output;
    }

    /**
     * Render the content to display when no more steps remain
     *
     * This is used by the ajax library so that users get feedback when finishing the final step
     *
     * @return  string  The text to render
     */
    public function block_display_step_complete_confirmation() {
        return html_writer::tag('p', get_string('stepfinishconfirmation', 'block_workflow'));
    }

    /**
     * Display the interface to manage workflows
     *
     * @param   array   $workflows  The list of workflows to display
     * @param   array   $emails     The list of email templates to display
     * @return  string              The text to render
     */
    public function manage_workflows(array $workflows, array $emails) {
        $output  = '';

        // The manage workflows section.
        $output .= $this->output->heading(get_string('manageworkflows', 'block_workflow'));
        $output .= html_writer::tag('p', get_string('managedescription', 'block_workflow'));
        $output .= $this->list_workflows($workflows);

        // The manage workflows section.
        $output .= $this->output->heading(get_string('manageemails', 'block_workflow'));
        $output .= html_writer::tag('p', get_string('emaildescription', 'block_workflow'));
        $output .= $this->list_emails($emails);

        return $output;
    }

    /**
     * The workflow list table
     *
     * Called by manage_workflows
     * @param   array   $workflows  The list of workflows to display
     * @return  string              The text to render
     */
    protected function list_workflows($workflows) {
        $output  = '';

        // Display the current workflows.
        $table = new html_table();
        $table->attributes['class'] = '';
        $table->head        = array();
        $table->colclasses  = array();
        $table->data        = array();
        $table->head[]      = get_string('shortname', 'block_workflow');
        $table->head[]      = get_string('name', 'block_workflow');
        $table->head[]      = get_string('appliesto', 'block_workflow');
        $table->head[]      = '';

        // Check whether each workflow is deletable.
        foreach ($workflows as $workflow) {
            $workflow->is_deletable = block_workflow_workflow::is_workflow_deletable($workflow->id);
            $table->data[] = $this->workflow_row($workflow);
        }

        // Create a new workflow.
        $emptycell = new html_table_cell();
        $emptycell->colspan = 3;
        $actions = array();
        $add = html_writer::empty_tag('img', array('src'   => $this->output->image_url('t/add'),
                                                   'class' => 'iconsmall',
                                                   'title' => get_string('createworkflow', 'block_workflow'),
                                                   'alt'   => get_string('createworkflow', 'block_workflow')
                                                ));
        $url = new moodle_url('/blocks/workflow/editsettings.php');
        $actions[] = html_writer::link($url, $add);
        $add = html_writer::empty_tag('img', array('src'   => $this->output->image_url('t/restore'),
                                                   'class' => 'iconsmall',
                                                   'title' => get_string('importworkflow', 'block_workflow'),
                                                   'alt'   => get_string('importworkflow', 'block_workflow')
                                                ));
        $url = new moodle_url('/blocks/workflow/import.php');
        $actions[] = html_writer::link($url, $add);
        $addimportcell = new html_table_cell(implode(' ', $actions));
        $addimportcell->attributes['class'] = 'mdl-align';

        $row = new html_table_row(array($emptycell, $addimportcell));
        $table->data[] = $row;
        $output .= html_writer::table($table);
        return $output;
    }

    /**
     * The workflow list row
     *
     * Called by list_workflows
     * @param   object  $workflow   The workflow to display
     * @return  string              The text to render
     */
    protected function workflow_row(stdClass $workflow) {
        $row = new html_table_row();
        $row->attributes['class']   = 'workflow';
        if ($workflow->obsolete != BLOCK_WORKFLOW_ENABLED) {
            $row->attributes['class'] .= ' dimmed_text';
        }

        // Shortname.
        $cell = new html_table_cell(s($workflow->shortname));
        $row->cells[] = $cell;

        // Workflow name.
        $cell = new html_table_cell(format_string($workflow->name));
        $row->cells[] = $cell;

        // Applies to.
        $cell = new html_table_cell(block_workflow_appliesto($workflow->appliesto));
        $row->cells[] = $cell;

        // View/Edit steps.
        $url = new moodle_url('/blocks/workflow/editsteps.php', array('workflowid' => $workflow->id));
        $actions[] = html_writer::link($url, html_writer::empty_tag('img', array(
                'src'   => $this->output->image_url('t/edit'),
                'class' => 'iconsmall',
                'title' => get_string('vieweditworkflow', 'block_workflow'),
                'alt'   => get_string('vieweditworkflow', 'block_workflow')
            )));

        // Export workflow.
        $url = new moodle_url('/blocks/workflow/export.php', array('sesskey' => sesskey(), 'workflowid' => $workflow->id));
        $actions[] = html_writer::link($url, html_writer::empty_tag('img', array(
                'src'   => $this->output->image_url('t/backup'),
                'class' => 'iconsmall',
                'title' => get_string('exportworkflow', 'block_workflow'),
                'alt'   => get_string('exportworkflow', 'block_workflow')
            )));

        // Clone workflow.
        $url = new moodle_url('/blocks/workflow/clone.php', array('workflowid' => $workflow->id));
        $actions[] = html_writer::link($url, html_writer::empty_tag('img', array(
                'src'   => $this->output->image_url('t/copy'),
                'class' => 'iconsmall',
                'title' => get_string('cloneworkflow', 'block_workflow'),
                'alt'   => get_string('cloneworkflow', 'block_workflow')
            )));

        // Disable/Enable workflow.
        $cell = new html_table_cell();
        if ($workflow->obsolete == BLOCK_WORKFLOW_ENABLED) {
            $url = new moodle_url('/blocks/workflow/toggleworkflowobsolete.php',
                    array('sesskey' => sesskey(), 'workflowid' => $workflow->id));
            $actions[] = html_writer::link($url, html_writer::empty_tag('img', array(
                    'src'   => $this->output->image_url('t/hide'),
                    'class' => 'iconsmall',
                    'title' => get_string('disableworkflow', 'block_workflow'),
                    'alt'   => get_string('disableworkflow', 'block_workflow')
                )));
        } else {
            $url = new moodle_url('/blocks/workflow/toggleworkflowobsolete.php',
                    array('sesskey' => sesskey(), 'workflowid' => $workflow->id));
            $actions[] = html_writer::link($url, html_writer::empty_tag('img', array(
                    'src'   => $this->output->image_url('t/show'),
                    'class' => 'iconsmall',
                    'title' => get_string('enableworkflow', 'block_workflow'),
                    'alt'   => get_string('enableworkflow', 'block_workflow')
                )));
        }

        // Remove workflow.
        if ($workflow->is_deletable) {
            $url = new moodle_url('/blocks/workflow/delete.php', array('workflowid' => $workflow->id));
            $actions[] = html_writer::link($url, html_writer::empty_tag('img', array(
                    'src'   => $this->output->image_url('t/delete'),
                    'class' => 'iconsmall',
                    'title' => get_string('removeworkflow', 'block_workflow'),
                    'alt'   => get_string('removeworkflow', 'block_workflow')
                )));
        } else {
            $a = block_workflow_workflow::in_use_by($workflow->id);
            $actions[] = html_writer::empty_tag('img', array(
                    'src'   => $this->output->image_url('t/delete'),
                    'class' => 'iconsmall',
                    'title' => get_string('cannotdeleteworkflowinuseby', 'block_workflow', $a),
                    'alt'   => get_string('removeworkflow', 'block_workflow')
                ));
        }

        $cell = new html_table_cell(implode(' ', $actions));
        $row->cells[] = $cell;

        return $row;
    }

    /**
     * The email list table
     *
     * Called by manage_workflows
     * @param   array   $emails     The list of email templates to display
     * @return  string              The text to render
     */
    protected function list_emails(array $emails) {
        $output = '';

        // Table setup.
        $table = $this->setup_table();
        $table->attributes['class'] = '';
        $table->head[]      = get_string('shortname',       'block_workflow');
        $table->head[]      = get_string('emailsubject', 'block_workflow');
        $table->head[]      = '';

        // Add the individual emails.
        foreach ($emails as $email) {
            $table->data[] = $this->email_row($email);
        }

        // Create a new email.
        $emptycell  = new html_table_cell();
        $emptycell->colspan = 2;
        $add = html_writer::empty_tag('img', array('src'   => $this->output->image_url('t/add'),
                                                   'class' => 'iconsmall',
                                                   'title' => get_string('addemail', 'block_workflow'),
                                                   'alt'   => get_string('addemail', 'block_workflow')
                                                ));
        $url = new moodle_url('/blocks/workflow/editemail.php');
        $addnewcell = new html_table_cell(html_writer::link($url, $add));
        $addnewcell->attributes['class'] = 'mdl-align';
        $row = new html_table_row(array($emptycell, $addnewcell));
        $table->data[] = $row;

        $output .= html_writer::table($table);

        return $output;
    }

    /**
     * The e-mail template list row
     *
     * Called by list_emails
     * @param   object  $email      The e-mail template to display
     * @return  string              The text to render
     */
    protected function email_row(stdClass $email) {
        $row = new html_table_row();
        $row->attributes['class']   = 'email';

        // Shortname.
        $cell = new html_table_cell(s($email->shortname));
        $row->cells[] = $cell;

        // Subject.
        $cell = new html_table_cell(format_string($email->subject));
        $row->cells[] = $cell;

        // View/Edit steps.
        $url = new moodle_url('/blocks/workflow/editemail.php', array('emailid' => $email->id));
        $actions[] = html_writer::link($url, html_writer::empty_tag('img', array(
                'src'   => $this->output->image_url('t/edit'),
                'class' => 'iconsmall',
                'title' => get_string('vieweditemail', 'block_workflow'),
                'alt'   => get_string('vieweditemail', 'block_workflow'),
            )));

        // Remove email.
        if ($email->activecount || $email->completecount) {
            $actions[] = html_writer::empty_tag('img', array(
                    'src'   => $this->output->image_url('t/delete'),
                    'class' => 'iconsmall',
                    'title' => get_string('cannotremoveemailinuse', 'block_workflow'),
                    'alt'   => get_string('deleteemail', 'block_workflow'),
                ));
        } else {
            $url = new moodle_url('/blocks/workflow/deleteemail.php', array('emailid' => $email->id));
            $actions[] = html_writer::link($url, html_writer::empty_tag('img', array(
                    'src'   => $this->output->image_url('t/delete'),
                    'class' => 'iconsmall',
                    'title' => get_string('deleteemail', 'block_workflow'),
                    'alt'   => get_string('deleteemail', 'block_workflow'),
                )));
        }

        // Add the steps.
        $cell = new html_table_cell(implode(' ', $actions));
        $row->cells[] = $cell;

        return $row;
    }

    /**
     * Render a list of steps
     *
     * Used in editsteps.php
     *
     * @param   object  $workflow   The workflow to display
     * @return  string              The text to render
     */
    public function list_steps($workflow) {
        $output = '';

        // List of steps.
        $output .= $this->output->heading(get_string('workflowsteps', 'block_workflow'));

        // Set up the table and it's headers.
        $table = $this->setup_table();
        $table->attributes['class'] = '';
        $table->head[] = get_string('stepno', 'block_workflow');
        $table->head[] = get_string('stepname', 'block_workflow');
        $table->head[] = get_string('doerstitle', 'block_workflow');
        $table->head[] = get_string('stepinstructions', 'block_workflow');
        $table->head[] = get_string('finish', 'block_workflow');
        $table->head[] = '';

        // Retrieve a list of steps etc.
        $steps = $workflow->steps();
        $info = new stdClass();
        $info->stepcount    = count($steps);
        $info->workflowid   = $workflow->id;
        $info->appliesto    = $workflow->appliesto;

        // The image to add a new step.
        $add = html_writer::empty_tag('img', array('src'   => $this->output->image_url('t/add'),
                                                   'class' => 'iconsmall',
                                                   'title' => get_string('addstep', 'block_workflow'),
                                                   'alt'   => get_string('addstep', 'block_workflow')
                                                ));

        // Add a step to the beginning.
        $addempty = new html_table_cell();
        $addempty->colspan = 5;
        $addcell = new html_table_cell(html_writer::link(new moodle_url('/blocks/workflow/editstep.php',
                array('workflowid' => $workflow->id, 'beforeafter' => -1)), $add));
        $addcell->attributes['class'] = 'mdl-align';
        $addrow = new html_table_row(array($addempty, $addcell));
        $table->data[] = $addrow;

        // Process the other steps.
        while ($step = array_shift($steps)) {
            if (count($steps) == 0) {
                $step->finalstep = true;
            }
            $table->data[] = $this->workflow_step($step, $info);
        }

        // Add option to add a new step.
        $infocell  = new html_table_cell($this->atendgobackto($workflow));
        $infocell->colspan = 5;
        $infocell->attributes['class'] = 'mdl-align';

        $url = new moodle_url('/blocks/workflow/editstep.php', array('workflowid' => $workflow->id));
        $addnewcell = new html_table_cell(html_writer::link($url, $add));
        $addnewcell->attributes['class'] = 'mdl-align';

        $row = new html_table_row(array($infocell, $addnewcell));
        $table->data[] = $row;

        // Display the table.
        $output .= html_writer::table($table);

        return $output;
    }

    protected function workflow_step($step, $info) {
        $row = new html_table_row();

        // Step number.
        $cell = new html_table_cell($step->stepno);
        $cell->attributes['class'] = 'mdl-align';
        $row->cells[] = $cell;

        // Name.
        $cell = new html_table_cell(format_string($step->name));
        $row->cells[] = $cell;

        // Roles reponsible for this step.
        $cell = new html_table_cell($this->workflow_step_doers($step));
        $row->cells[] = $cell;

        // Instructions.
        $cell = new html_table_cell(format_text($step->instructions, $step->instructionsformat));
        $row->cells[] = $cell;

        // Automatically finish.
        $cell = new html_table_cell($this->workflow_step_auto_finish($step, $info->appliesto));
        $row->cells[] = $cell;

        // Modification.
        $actions = array();
        $url = new moodle_url('/blocks/workflow/editstep.php', array('stepid' => $step->id));
        $actions[] = html_writer::link($url, html_writer::empty_tag('img', array('src' => $this->output->image_url('t/edit'),
                                                                           'class' => 'iconsmall',
                                                                           'title' => get_string('editstep', 'block_workflow'),
                                                                           'alt'   => get_string('editstep', 'block_workflow')
                                                                        )));

        // Add step after this one.
        $url = new moodle_url('/blocks/workflow/editstep.php',
                array('workflowid' => $info->workflowid, 'beforeafter' => $step->stepno));
        $actions[] = html_writer::link($url, html_writer::empty_tag('img', array(
                'src' => $this->output->image_url('t/add'),
                'class' => 'iconsmall',
                'title' => get_string('addstepafter', 'block_workflow'),
                'alt'   => get_string('addstepafter', 'block_workflow')
            )));

        // Can't be removed if this is the only step or in use.
        if ($info->stepcount != 1 && !block_workflow_step::is_step_in_use($step->id)) {
            $url = new moodle_url('/blocks/workflow/deletestep.php', array('stepid' => $step->id));
            $actions[] = html_writer::link($url, html_writer::empty_tag('img', array(
                    'src' => $this->output->image_url('t/delete'),
                    'class' => 'iconsmall',
                    'title' => get_string('removestep', 'block_workflow'),
                    'alt'   => get_string('removestep', 'block_workflow')
                )));
        }

        // Move up if this is not the first step.
        if ($step->stepno != 1) {
            $url = new moodle_url('/blocks/workflow/movestep.php',
                    array('sesskey' => sesskey(), 'id' => $step->id, 'direction' => 'up'));
            $actions[] = html_writer::link($url, html_writer::empty_tag('img', array(
                    'src'   => $this->output->image_url('t/up'),
                    'class' => 'iconsmall',
                    'title' => get_string('moveup', 'block_workflow'),
                    'alt'   => get_string('moveup', 'block_workflow')
                )));
        }

        // Move down if this is not the final step.
        if (!isset($step->finalstep)) {
            $url = new moodle_url('/blocks/workflow/movestep.php',
                    array('sesskey' => sesskey(), 'id' => $step->id, 'direction' => 'down'));
            $actions[] = html_writer::link($url, html_writer::empty_tag('img', array(
                    'src'   => $this->output->image_url('t/down'),
                    'class' => 'iconsmall',
                    'title' => get_string('movedown', 'block_workflow'),
                    'alt'   => get_string('movedown', 'block_workflow')
                )));
        }

        $cell = new html_table_cell(implode(' ', $actions));
        $row->cells[] = $cell;

        return $row;
    }

    /**
     * Return a sting that indicates whether a given step is set to be finish automatically
     * @param object $stepdata raw data about the step, loaded from the DB.
     * @return string textual description of the settings.
     */
    protected function workflow_step_auto_finish($step, $appliesto) {
        // Do not finish this step automatically.
        if (!$step->autofinish || $step->autofinish == 'donotautomaticallyfinish') {
            return get_string('donotautomaticallyfinish', 'block_workflow');
        }

        list($options, $days) = block_workflow_step::get_autofinish_options($appliesto);

        if ($step->autofinishoffset > 0) {
            // Days after certain condition.
            $days = $step->autofinishoffset / (24 * 60 * 60);
            if ($days == 1) {
                $daysstring = get_string('dayafter', 'block_workflow', $days);
            } else {
                $daysstring = get_string('daysafter', 'block_workflow', $days);
            }
        } else if ($step->autofinishoffset < 0) {
            // Days before certain condition.
            $days = abs($step->autofinishoffset) / (24 * 60 * 60);
            if ($days == 1) {
                $daysstring = get_string('daybefore', 'block_workflow', $days);
            } else {
                $daysstring = get_string('daysbefore', 'block_workflow', $days);
            }
        } else {
            // Same day as certain condition.
            $daysstring = get_string('dayas', 'block_workflow');
        }

        list($table, $field) = explode(';', $step->autofinish);
        $key = $table . ';' . $field;
        if (array_key_exists($key, $options)) {
            return $daysstring . ' ' . $options[$key];
        }
        return '';
    }

    /**
     * Get all roles that are doers of a given step
     * @param object $stepdata raw data about the step, loaded from the DB.
     * @return string comma-separated list of role names.
     */
    protected function workflow_step_doers($stepdata) {
        $step = block_workflow_step::make($stepdata);
        $doernames = array();
        foreach ($step->roles() as $doer) {
            $doernames[] = $doer->localname;
        }
        return implode(', ', $doernames);
    }

    protected function workflow_information($workflow) {
        $output = '';

        // Header and general information.
        $output .= $this->output->heading(get_string('workflowinformation', 'block_workflow'), 3, 'title header');

        $table = $this->setup_table();
        // Workflow name and shortname.
        $row = new html_table_row(array(get_string('name', 'block_workflow')));
        $cell = new html_table_cell();
        $data = array('name' => format_string($workflow->name), 'shortname' => s($workflow->shortname));
        $cell->text = get_string('nameshortname', 'block_workflow', $data);
        $row->cells[] = $cell;
        $table->data[] = $row;

        // Description.
        $row = new html_table_row(array(get_string('description', 'block_workflow')));
        $cell = new html_table_cell();
        $cell->text = format_text($workflow->description, $workflow->descriptionformat);
        $row->cells[] = $cell;
        $table->data[] = $row;

        // What contexts does this block apply to.
        $row = new html_table_row(array(get_string('appliesto', 'block_workflow')));
        $cell = new html_table_cell();
        $cell->text = $workflow->appliesto;
        $row->cells[] = $cell;
        $table->data[] = $row;

        // Status information.
        $row = new html_table_row(array(get_string('status', 'block_workflow')));
        $cell = new html_table_cell();
        if ($workflow->obsolete == BLOCK_WORKFLOW_OBSOLETE) {
            $cell->text = get_string('obsoleteworkflow', 'block_workflow');
        } else {
            $cell->text = get_string('enabledworkflow', 'block_workflow');
        }
        $row->cells[] = $cell;
        $table->data[] = $row;

        // Other info.
        $row = new html_table_row(array(get_string('inuseby', 'block_workflow')));
        $cell = new html_table_cell('This workflow is active in x contexts');
        $row->cells[] = $cell;
        $table->data[] = $row;

        $output .= html_writer::table($table);
        return $output;
    }

    /**
     * Display the specified workflow settings and include links to edit these settings
     *
     * @param   object  $workflow   The workflow to display
     * @return  object              The renderer to display
     */
    public function display_workflow($workflow) {
        $output = '';

        // Start the box.
        $output .= $this->output->heading(get_string('workflowsettings', 'block_workflow'));

        // Setup the table.
        $table = $this->setup_table();
        $table->attributes['class'] = '';

        // Shortname.
        $row = new html_table_row(array(
            get_string('shortname', 'block_workflow'),
            s($workflow->shortname),
        ));
        $table->data[] = $row;

        // Name.
        $row = new html_table_row(array(
            get_string('name', 'block_workflow'),
            format_string($workflow->name),
        ));
        $table->data[] = $row;

        // Description.
        $row = new html_table_row(array(
            get_string('description', 'block_workflow'),
            format_text($workflow->description, $workflow->descriptionformat),
        ));
        $table->data[] = $row;

        // Applies to.
        $row = new html_table_row(array(
            get_string('thisworkflowappliesto', 'block_workflow'),
            block_workflow_appliesto($workflow->appliesto),
        ));
        $table->data[] = $row;

        // Current status.
        $togglelink = new moodle_url('/blocks/workflow/toggleworkflowobsolete.php',
                array('workflowid' => $workflow->id, 'returnto' => 'editsteps', 'sesskey' => sesskey()));
        if ($workflow->obsolete) {
            $status = get_string('workflowobsolete', 'block_workflow', $togglelink->out());
        } else {
            $status = get_string('workflowactive', 'block_workflow', $togglelink->out());
        }
        // Count the times the workflow is actively in use.
        if ($count = block_workflow_workflow::in_use_by($workflow->id, true)) {
            $status .= get_string('inuseby', 'block_workflow', $count);
        } else {
            $status .= get_string('notcurrentlyinuse', 'block_workflow');
        }

        $row = new html_table_row(array(
            get_string('workflowstatus', 'block_workflow'),
            $status
        ));
        $table->data[] = $row;

        // Workflow actions.
        $row = new html_table_row();
        $cell = new html_table_cell();
        $cell->colspan = 2;
        $cell->attributes['class'] = 'mdl-align';

        $actions = array();

        // Edit the workflow.
        $url = new moodle_url('/blocks/workflow/editsettings.php', array('workflowid' => $workflow->id));
        $actions[] = html_writer::link($url, get_string('edit', 'block_workflow'));

        // Clone the workflow.
        $url = new moodle_url('/blocks/workflow/clone.php', array('workflowid' => $workflow->id));
        $actions[] = html_writer::link($url, get_string('clone', 'block_workflow'));

        // Export the workflow.
        $url = new moodle_url('/blocks/workflow/export.php', array('sesskey' => sesskey(), 'workflowid' => $workflow->id));
        $actions[] = html_writer::link($url, get_string('export', 'block_workflow'));

        if (block_workflow_workflow::is_workflow_deletable($workflow->id)) {
            // Delete the workflow.
            $url = new moodle_url('/blocks/workflow/delete.php', array('workflowid' => $workflow->id));
            $actions[] = html_writer::link($url, get_string('delete', 'block_workflow'));
        }

        $cell->text = implode(', ', $actions);

        $row->cells[] = $cell;
        $table->data[] = $row;

        // Display the table.
        $output .= html_writer::table($table);

        return $output;
    }

    public function step_todolist($todos, $step) {
        $output = '';

        // Title area.
        $output .= $this->output->heading(get_string('todotitle', 'block_workflow'), 3, 'title header');

        // The to-do list.
        $table = $this->setup_table();
        $table->head[] = get_string('todotask', 'block_workflow');
        $table->head[] = '';

        foreach ($todos as $todo) {
            $todo->isremovable = true;
            $table->data[] = $this->step_todolist_item($todo);
        }

        // Add option to add a new task.
        $emptycell  = new html_table_cell();
        $add = html_writer::empty_tag('img', array('src'   => $this->output->image_url('t/add'),
                                                   'class' => 'iconsmall',
                                                   'title' => get_string('addtask', 'block_workflow'),
                                                   'alt'   => get_string('addtask', 'block_workflow')
                                                ));
        $url = new moodle_url('/blocks/workflow/edittask.php', array('stepid' => $step->id));
        $addnewcell = new html_table_cell(html_writer::link($url, $add));

        $row = new html_table_row(array($emptycell, $addnewcell));
        $table->data[] = $row;

        // Display the table.
        $output .= html_writer::table($table);

        return $output;
    }
    protected function step_todolist_item(stdClass $task) {
        $row    = new html_table_row();
        $name   = new html_table_cell(format_string($task->task));
        $actions = array();

        $url    = new moodle_url('/blocks/workflow/edittask.php', array('id' => $task->id));
        $actions[] = html_writer::link($url, html_writer::empty_tag('img', array('src'   => $this->output->image_url('t/edit'),
                                                                           'class' => 'iconsmall',
                                                                           'title' => get_string('edittask', 'block_workflow'),
                                                                           'alt'   => get_string('edittask', 'block_workflow')
                                                                        )));

        // Obsolete task.
        $url = new moodle_url('/blocks/workflow/toggletaskobsolete.php', array('sesskey' => sesskey(), 'taskid' => $task->id));
        if ($task->obsolete == BLOCK_WORKFLOW_ENABLED) {
            $actions[] = html_writer::link($url, html_writer::empty_tag('img', array('src'   => $this->output->image_url('t/hide'),
                                                                            'class' => 'iconsmall',
                                                                            'title' => get_string('hidetask', 'block_workflow'),
                                                                            'alt'   => get_string('hidetask', 'block_workflow')
                                                                            )));
        } else {
            $actions[] = html_writer::link($url, html_writer::empty_tag('img', array('src'   => $this->output->image_url('t/show'),
                                                                            'class' => 'iconsmall',
                                                                            'title' => get_string('showtask', 'block_workflow'),
                                                                            'alt'   => get_string('showtask', 'block_workflow')
                                                                            )));
        }

        // Delete task.
        if ($task->isremovable) {
            $url    = new moodle_url('/blocks/workflow/deletetask.php', array('id' => $task->id));
            $actions[] = html_writer::link($url,
                    html_writer::empty_tag('img', array('src' => $this->output->image_url('t/delete'),
                            'class' => 'iconsmall',
                            'title' => get_string('removetask', 'block_workflow'),
                            'alt'   => get_string('removetask', 'block_workflow')
                    )));
        }

        $actions = new html_table_cell(implode(' ', $actions));

        // Put it all together into a row and return the data.
        $row    = new html_table_row(array($name, $actions));
        return $row;
    }

    public function step_doers($roles, $doers, $stepid) {
        $output = '';

        // Title area.
        $output .= $this->output->heading(get_string('doertitle', 'block_workflow'), 3, 'title header');

        // The to-do list.
        $table = $this->setup_table();
        $table->head[] = get_string('roles', 'block_workflow');
        $table->head[] = '';

        $activedoers = array_map(function ($a) {
            return $a->id;
        }, $doers);

        foreach ($roles as $role) {
            if (in_array($role->id, $activedoers)) {
                $role->doer = true;
            } else {
                $role->doer = false;
            }
            $table->data[] = $this->step_doer($role, $stepid);
        }

        // Display the table.
        $output .= html_writer::table($table);

        return $output;
    }
    protected function step_doer($role, $stepid) {
        $row    = new html_table_row();
        $name   = new html_table_cell($role->localname);

        $url = new moodle_url('/blocks/workflow/togglerole.php',
                array('sesskey' => sesskey(), 'roleid' => $role->id, 'stepid' => $stepid));
        if ($role->doer) {
            $actions = html_writer::link($url, html_writer::empty_tag('img', array(
                    'src'   => $this->output->image_url('t/delete'),
                    'class' => 'iconsmall',
                    'title' => get_string('removerolefromstep', 'block_workflow'),
                    'alt'   => get_string('removerolefromstep', 'block_workflow')
                )));
        } else {
            $actions = html_writer::link($url, html_writer::empty_tag('img', array(
                    'src'   => $this->output->image_url('t/add'),
                    'class' => 'iconsmall',
                    'title' => get_string('addroletostep', 'block_workflow'),
                    'alt'   => get_string('addroletostep', 'block_workflow')
                )));
        }

        // Put it all together into a row and return the data.
        $row    = new html_table_row(array($name, $actions));
        return $row;
    }

    /**
     * Show the instructions for creating or editing an e-mail template
     *
     * @param   object  $data   The e-mail data
     * @return  string          The content to render
     */
    public function email_template_instructions($email) {
        $output = '';
        if ($email->id) {
            $output .= $this->output->heading(get_string('editemail', 'block_workflow', $email->shortname), 1, 'title');
        } else {
            $output .= $this->output->heading(get_string('createemail', 'block_workflow'), 1, 'title');
        }
        $output .= $this->output->container(get_string('edittemplateinstructions', 'block_workflow'));
        return $output;
    }

    /**
     * Show the instructions for cloning a workflow
     *
     * @param   object  $workflow   The workflow to clone
     * @return  string              The content to render
     */
    public function clone_workflow_instructions($workflow) {
        $output = '';
        $output .= $this->output->heading(
                get_string('cloneworkflowname', 'block_workflow', s($workflow->shortname)), 1, 'title');
        $output .= $this->output->container(get_string('cloneworkflowinstructions', 'block_workflow'));
        return $output;
    }

    /**
     * Show the instructions for editing a workflow
     * @param   stdClass    $workflow The workflow being editted
     * @return  string      The content to render
     */
    public function edit_workflow_instructions(stdClass $data) {
        $output = '';
        if (isset($data->workflowid)) {
            $output .= $this->output->heading(get_string('editworkflow', 'block_workflow', $data->shortname), 1, 'title');
        } else {
            $output .= $this->output->heading(get_string('createworkflow', 'block_workflow'), 1, 'title');
        }
        $output .= $this->output->container(get_string('editworkflowinstructions', 'block_workflow'));
        return $output;
    }

    // The following group of functions relate to managing a workflow step.
    /**
     * Show the instructions for editing a step
     * @param block_workflow_step $step The step being editted
     * @return String the content to render
     */
    public function edit_step_instructions(block_workflow_step $step) {
        $output = '';
        $output .= $this->output->heading(get_string('editstepname', 'block_workflow', $step->name), 1, 'title');
        $output .= $this->output->container(get_string('editstepinstructions', 'block_workflow'));
        return $output;
    }

    /**
     * Show the instructions for creating a new step
     * @param block_workflow_workflow $workflow The workflow that this step
     * will belong to
     * @return String the content to render
     */
    public function create_step_instructions(block_workflow_workflow $workflow) {
        $output = '';
        $output .= $this->output->heading(get_string('createstepname', 'block_workflow', $workflow->name), 1, 'title');
        $output .= $this->output->container(get_string('createstepinstructions', 'block_workflow'));
        return $output;
    }

    /**
     * The head work to create a standard table for the workflow block
     * @return html_table object with standard settings applied
     */
    protected function setup_table() {
        $table = new html_table();
        $table->head        = array();
        $table->colclasses  = array();
        $table->data        = array();
        return $table;
    }

    /**
     * Gets CSS classes for the workflow overview box.
     *
     * @return string Css classes
     */
    protected function get_box_start_css_classes() {
        return 'generalbox boxwidthwide boxaligncenter';
    }

    public function workflow_overview($workflow, array $states, $context) {
        $output = '';

        // Add the box, title and description.
        $output .= $this->box_start($this->get_box_start_css_classes(), 'block-workflow-overview');
        $output .= $this->output->heading(get_string('overview', 'block_workflow'));

        $table = $this->setup_table();
        $table->attributes['class'] = 'boxaligncenter';
        $table->head[] = get_string('stepno', 'block_workflow');
        $table->head[] = get_string('stepname', 'block_workflow');
        $table->head[] = get_string('roles', 'block_workflow');
        $table->head[] = get_string('comments', 'block_workflow');
        $table->head[] = get_string('state', 'block_workflow');
        $table->head[] = get_string('lastmodified', 'block_workflow');
        $table->head[] = '';

        // Add each step.
        foreach ($states as $state) {
            $table->data[] = $this->workflow_overview_step($state, $context);
        }
        $this->page->requires->yui_module('moodle-block_workflow-userinfo', 'M.block_workflow.userinfo.init');

        // Put everything together and return.
        $output .= html_writer::table($table);
        // Put text and button after the table.
        $output .= html_writer::tag('div', $this->atendgobackto($workflow), array('id' => 'text-after-table'));
        if (has_capability('block/workflow:manage', $context)) {
            $url = new moodle_url('/blocks/workflow/removeworkflow.php',
                array('contextid' => $context->id, 'workflowid' => $workflow->id));
            $output .= $this->output->render(new single_button($url, get_string('removeworkflow', 'block_workflow')));
        }
        $output .= $this->box_end();
        return $output;
    }

    private function workflow_overview_step($stepstate, $context) {
        $row = new html_table_row();
        $classes = array('step');

        // Add some CSS classes to help colour-code the states.
        if ($stepstate->state == BLOCK_WORKFLOW_STATE_ACTIVE) {
            $classes[] = 'active';
            $state = get_string('state_active', 'block_workflow', sprintf('%d', $stepstate->complete));
        } else if ($stepstate->state == BLOCK_WORKFLOW_STATE_COMPLETED) {
            $classes[] = 'completed';
            $state = get_string('state_completed', 'block_workflow');
        } else if ($stepstate->state == BLOCK_WORKFLOW_STATE_ABORTED) {
            $classes[] = 'aborted';
            $state = get_string('state_aborted', 'block_workflow', sprintf('%d', $stepstate->complete));
        } else {
            $state = get_string('state_notstarted', 'block_workflow');
        }

        if (!is_null($stepstate->complete)) {
            $complete = html_writer::tag('span',
                    get_string('percentcomplete', 'block_workflow',
                            format_float($stepstate->complete, 0)),
                    array('class' => 'completeinfo'));
        } else {
            $complete = '';
        }

        // Add all of the classes.
        $row->attributes['class'] = implode(' ', $classes);

        // Step Number.
        $cell = new html_table_cell($stepstate->stepno);
        $cell->attributes['class'] = 'mdl-align';
        $row->cells[] = $cell;

        // Step Name.
        $cell = new html_table_cell(format_string($stepstate->name));
        $cell->attributes['class'] = 'mdl-align';
        $row->cells[] = $cell;

        // Roles reponsible for this step.
        $stateobj = new block_workflow_step_state($stepstate->stateid);
        $roles = $stateobj->step()->roles();
        $step = $stateobj->step();
        $cell = new html_table_cell($this->workflow_step_doers($step));

        // Add the "Show names(N)" button to the role column.
        $cell->text .= $this->get_popup_button($roles, $context, $stepstate->stepno);

        $row->cells[] = $cell;

        // Comments.
        $cell = new html_table_cell();
        $cell->text = format_text($stepstate->comment, $stepstate->commentformat, array('context' => $context));
        if (!$cell->text) {
            $cell->text  = get_string('nocomment', 'block_workflow');
        }
        if ($history = $this->workflow_overview_step_history($stepstate->stateid)) {
            $cell->text .= print_collapsible_region($history, 'historyinfo',
                    'history-' . $stepstate->id, get_string('state_history', 'block_workflow'),
                    '', true, true);
        }
        $row->cells[] = $cell;

        // Step state.
        $cell = new html_table_cell($state . $complete);
        $cell->attributes['class'] = 'mdl-align';
        $row->cells[] = $cell;

        // Last modified.
        $cell = new html_table_cell();
        if ($stepstate->timemodified) {
            $cell->text = $stepstate->modifieduser . html_writer::tag('span',
                    userdate($stepstate->timemodified), array('class' => 'dateinfo'));
        }
        $cell->attributes['class'] = 'mdl-align';
        $row->cells[] = $cell;

        // Add finish step/jump to step buttons.
        $cell = new html_table_cell();
        if ($stepstate->state == BLOCK_WORKFLOW_STATE_ACTIVE) {
            $state = new block_workflow_step_state();
            $state->id               = $stepstate->stateid;
            $state->stepid           = $stepstate->id;
            $state->contextid        = $stepstate->contextid;
            $state->state            = $stepstate->state;
            $state->timemodified     = $stepstate->timemodified;
            $state->comment          = $stepstate->comment;
            $state->commentformat    = $stepstate->commentformat;
            if (block_workflow_can_make_changes($state)) {
                $cell->text = html_writer::tag('div', $this->finish_step($stepstate->stateid));
            }
        } else {
            if (has_capability('block/workflow:manage', $context)) {
                $cell->text = html_writer::tag('div', $this->jump_to_step($stepstate->id, $context->id));
            }
        }
        $row->cells[] = $cell;

        return $row;
    }

    private function workflow_overview_step_history($stateid) {
        $history = array();
        foreach (block_workflow_step_state::state_changes($stateid) as $change) {
            $a = array();
            $a['newstate']  = get_string('state_history_' . $change->newstate, 'block_workflow');
            $a['time']      = userdate($change->timestamp);
            $a['user']      = $change->username;
            $history[]      = html_writer::tag('p', get_string('state_history_detail', 'block_workflow', $a));
        }
        return implode("\n", $history);
    }

    /**
     * What to do at the end of the worklow
     *
     * @param   object  $workflow   The workflow to return the string for
     * @return  string  The formatted string
     */
    protected function atendgobackto($workflow) {
        // At end go back to ...
        $a = array();
        // ... count the steps.
        $a['stepcount'] = count($workflow->steps());

        if ($workflow->atendgobacktostep) {
            $a['atendgobacktostep'] = $workflow->atendgobacktostep;
            return get_string('atendgobacktostepinfo', 'block_workflow', $a);
        } else {
            return get_string('atendstop', 'block_workflow', $a);
        }
    }

    /**
     * Render a 'Finish step' button
     *
     * @param   integer $stateid The stateid to finish
     * @return  String  The rendered button to take the user to the finishstep form
     */
    protected function finish_step($stateid) {
        $url = new moodle_url('/blocks/workflow/finishstep.php', array('stateid' => $stateid));
        return $this->output->render(new single_button($url, get_string('finishstep', 'block_workflow'), 'get'));
    }

    /**
     * Render a 'Jump to step' button
     *
     * @param   integer $stateid The stateid to jump to
     * @return  String  The rendered button to take the user to the jumptostep form
     */
    protected function jump_to_step($stepid, $contextid) {
        $url = new moodle_url('/blocks/workflow/jumptostep.php', array('stepid' => $stepid, 'contextid' => $contextid));
        return $this->output->render(new single_button($url, get_string('jumptostep', 'block_workflow'), 'get'));
    }

    /**
     * Show the instructions for finishing a workflow step
     *
     * @return  string      The content to render
     */
    public function finish_step_instructions() {
        $output = '';
        $output .= $this->output->heading(get_string('finishstep', 'block_workflow'), 1, 'title');
        $output .= $this->output->container(get_string('finishstepinstructions', 'block_workflow'));
        return $output;
    }

    /**
     * Return user infor button
     * @param object $options, array of options passing to userinfo.js
     * @param int $numberofusers, number of users which
     * @return NULL|string
     */
    protected function get_userinfo_button($options, $numberofusers) {
        $stepno = $options['stepno'];
        $disabled = '';
        if ($numberofusers == 0) {
            $disabled = 'disabled="disabled"';
        }
        $userinfobutton = '<input id="userinfo' . $stepno . '"'. $disabled . '" type="submit" name="userinfo' . $stepno . '"
                            value="'. get_string('shownamesx', 'block_workflow', $numberofusers) . '"/>';
        $userinfobutton = html_writer::tag('span', $userinfobutton, $options);
        return $userinfobutton;
    }

    /**
     * Returns popup with a header and body where the body an html table
     * @param object $users, array of users who have roles
     */
    protected function get_popup_table($users, $stepno) {
        global $CFG;

        if (!$users) {
            return null;
        }

        // Get extra user information from the user policies settings.
        $extrafields = \core_user\fields::get_identity_fields($this->page->context, true);
        // Set up the table header.
        $tableheader = array();
        $tableheader[] = get_string('name');
        foreach ($extrafields as $field) {
            $tableheader[] = \core_user\fields::get_display_name($field);
        }
        $tableheader[] = get_string('roles');

        $data = array();
        foreach ($users as $key => $user) {
            $row = array();
            $row[0] = html_writer::tag('b', fullname($user));
            $extraindex = 1;
            if ($extrafields) {
                foreach ($extrafields as $field) {
                    if ($field == 'email') {
                        $row[$extraindex] = html_writer::link('mailto:' . $user->$field, $user->$field);
                    } else {
                        $row[$extraindex] = $user->$field;
                    }
                    $extraindex++;
                }
            }
            $row[$extraindex] = implode(', ', $user->roles);
            $data[] = $row;
        }
        if (!$data) {
            return null;
        }

        // Create an html table and collect header and the data.
        $table = new html_table();
        $table->head  = $tableheader;
        $table->data  = $data;

        $popupheader = get_string('showpeoplecandotask', 'block_workflow');
        if ($stepno > 0) {
            $popupheader .= " (Step $stepno)";
        }
        // Return header and body of the popup.
        return array($popupheader, html_writer::table($table));
    }

    /**
     * Return the button if there are roles
     * @param object $state, block_workflow_step_state object
     * @param object $roles
     * @param object $context
     */
    protected function get_popup_button($roles, $context, $stepno = 0) {
        $steptate = new block_workflow_step_state();
        $users = $steptate->get_all_users_and_their_roles($roles, $context);
        $numberofusers = $users === null ? 0 : count($users);
        list ($header, $body) = $this->get_popup_table($users, $stepno);
        $options = array('class' => 'userinfoclass', 'header' => $header, 'body' => $body, 'stepno' => $stepno);

        if (!$roles) {
            return null;
        }
        return  html_writer::tag('span', ' ' . $this->get_userinfo_button($options, $numberofusers));
    }
}
