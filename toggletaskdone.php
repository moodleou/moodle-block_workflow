<?php

/**
 * Toggle a task as done
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/lib.php');

// Get the submitted paramaters
$todoid     = required_param('todoid', PARAM_INT);
$stateid    = required_param('stateid', PARAM_INT);
$returnto   = optional_param('returnto', '', PARAM_LOCALURL);

// Require login and a valid session key
require_login();
require_sesskey();

// Grab the state, and context
$state      = new block_workflow_step_state($stateid);
$context    = $state->context();

// Require the workflow:dostep capability
require_capability('block/workflow:dostep', $context);

// Toggle the todo item
$state->todo_toggle($todoid);

if ($returnto) {
    redirect(new moodle_url($returnto));
}
else {
    // redirect to our best guess for the correct page
    redirect(get_context_url($context));
}
