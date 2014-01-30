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
 * Toggles whether a workflow is obsolete or not
 *
 * @package   block_workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');

// Get the submitted paramaters.
$workflowid = required_param('workflowid', PARAM_INT);
$returnto   = optional_param('returnto', '', PARAM_ALPHA);

// Require login and a valid session key.
require_login();
require_sesskey();

// Require the workflow:editdefinitions capability.
require_capability('block/workflow:editdefinitions', context_system::instance());

// Toggle the workflow.
$workflow = new block_workflow_workflow($workflowid);
$workflow->toggle();

// Redirect as appropriate.
if ($returnto == 'editsteps') {
    redirect(new moodle_url('/blocks/workflow/editsteps.php', array('workflowid' => $workflowid)));
} else {
    redirect(new moodle_url('/blocks/workflow/manage.php'));
}
