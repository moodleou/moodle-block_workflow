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
 * Main workflow configuration page
 *
 * @package   block_workflow
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once($CFG->libdir . '/adminlib.php');

// This is an admin page.
admin_externalpage_setup('blocksettingworkflow');

// Require login.
require_login();

// Require the workflow:editdefinitions capability.
require_capability('block/workflow:editdefinitions', context_system::instance());

// Page settings.
$title = get_string('manageworkflows', 'block_workflow');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_context(context_system::instance());

// Grab the renderer.
$renderer       = $PAGE->get_renderer('block_workflow');

// Display the manage workflows interface.
$workflows      = block_workflow_load_workflows();
$emails         = block_workflow_email::load_emails();
$tableworkflows = $renderer->manage_workflows($workflows, $emails);

// Display the page.
echo $OUTPUT->header();
echo $tableworkflows;
echo $OUTPUT->footer();
