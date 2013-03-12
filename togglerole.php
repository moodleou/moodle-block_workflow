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
 * Toggle the use of a role as a doer for a step
 *
 * @package   block_workflow
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');

// Get the submitted paramaters.
$stepid = required_param('stepid', PARAM_INT);
$roleid = required_param('roleid', PARAM_INT);

// Require login and a valid session key.
require_login();
require_sesskey();

// Require the workflow:editdefinitions capability.
require_capability('block/workflow:editdefinitions', get_context_instance(CONTEXT_SYSTEM));

// Grab the step.
$step = new block_workflow_step($stepid);

// Toggle the role.
$step->toggle_role($roleid);

// Redirect.
redirect(new moodle_url('/blocks/workflow/editstep.php', array('stepid' => $stepid)));
