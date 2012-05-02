<?php

/**
 * Toggle the use of a role as a doer for a step
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');

// Get the submitted paramaters
$stepid = required_param('stepid', PARAM_INT);
$roleid = required_param('roleid', PARAM_INT);

// Require login and a valid session key
require_login();
require_sesskey();

// Require the workflow:editdefinitions capability
require_capability('block/workflow:editdefinitions', get_context_instance(CONTEXT_SYSTEM));

// Grab the step
$step = new block_workflow_step($stepid);

// Toggle the role
$step->toggle_role($roleid);

// Redirect
redirect(new moodle_url('/blocks/workflow/editstep.php', array('stepid' => $stepid)));
