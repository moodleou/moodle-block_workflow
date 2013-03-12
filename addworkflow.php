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
 * Add a workflow to a context specified
 *
 * @package   block_workflow
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');

// Get the submitted paramaters.
$contextid  = required_param('contextid', PARAM_INT);
$workflowid = required_param('workflow', PARAM_INT);

// Determine the context and cm.
list($context, $course, $cm) = get_context_info_array($contextid);

// Require login and a valid session key.
require_login($course, false, $cm);
require_sesskey();

if ($cm) {
    $PAGE->set_cm($cm);
} else {
    $PAGE->set_context($context);
}

// Require the workflow:manage capability.
require_capability('block/workflow:manage', $context);

// Add the workflow to the specified context.
$workflow = new block_workflow_workflow($workflowid);
$workflow->add_to_context($contextid);

// Redirect based on the context's URL.
redirect(get_context_url($context));
