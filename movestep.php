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
 * Move a step ordering
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');

// Get the submitted paramaters.
$stepid     = required_param('id', PARAM_INT);
$direction  = required_param('direction', PARAM_TEXT);
$step       = new block_workflow_step($stepid);

// Require login and a valid session key.
require_login();
require_sesskey();

// Require the workflow:manage capability.
require_capability('block/workflow:manage', get_context_instance(CONTEXT_SYSTEM));


$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/workflow/movestep.php', array('stepid' => $stepid, 'direction' => $direction));

// Grab the retrieve.
$returnurl = new moodle_url('/blocks/workflow/editsteps.php', array('workflowid' => $step->workflowid));

// Work out what we'll be waspping with.
$stepno = $step->stepno;
$swapwith = new block_workflow_step();

if ($direction == 'up') {
    if ($stepno == 1) {
        // We can't go any higher.
        redirect($returnurl);
    }
    $swapwith->load_workflow_stepno($step->workflowid, $stepno - 1);

} else {
    try {
        $swapwith->load_workflow_stepno($step->workflowid, $stepno + 1);
    } catch (block_workflow_invalid_workflow_step_exception $e) {
        // This is already the last step.
        redirect($returnurl);
    }
}

// Swap the steps around.
$step->swap_step_with($swapwith);

// Redirect.
redirect($returnurl);
