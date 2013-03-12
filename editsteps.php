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
 * Script to display infromation about workflow and controls to edit it
 *
 * @package   block_workflow
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once($CFG->libdir . '/adminlib.php');

// Get the submitted paramaters.
$workflowid = required_param('workflowid', PARAM_INT);

// This is an admin page.
admin_externalpage_setup('blocksettingworkflow');

// Require login.
require_login();

// Require the workflow:editdefinitions capability.
require_capability('block/workflow:editdefinitions', get_context_instance(CONTEXT_SYSTEM));

// Grab the workflow.
$workflow   = new block_workflow_workflow($workflowid);

// Set the returnurl as we'll use this in a few places.
$returnurl  = new moodle_url('/blocks/workflow/editsteps.php', array('workflowid' => $workflowid));

// Set various page settings.
$title = get_string('editworkflow', 'block_workflow', $workflow->name);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_url($returnurl);

// Add the breadcrumbs.
$PAGE->navbar->add($workflow->name, $returnurl);

// Grab the renderer.
$renderer   = $PAGE->get_renderer('block_workflow');

// Display the page header.
echo $OUTPUT->header();

// List the current workflow settings.
echo $renderer->display_workflow($workflow);

// List the current workflow steps.
echo $renderer->list_steps($workflow);

// Display the page footer.
echo $OUTPUT->footer();
