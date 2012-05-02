<?php

/**
 * Toggles a task as obsolete
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');

// Get the submitted paramaters
$taskid = required_param('taskid', PARAM_INT);

// Require login and a valid session key
require_login();
require_sesskey();

// Require the workflow:editdefinitions capability
require_capability('block/workflow:editdefinitions', get_context_instance(CONTEXT_SYSTEM));

// Toggle the todo item
$task = block_workflow_todo::toggle($taskid);

// Redirect
redirect(new moodle_url('/blocks/workflow/editstep.php', array('stepid' => $task->stepid)));
