<?php

/**
 * Toggles whether a workflow is obsolete or not
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');

// Get the submitted paramaters
$workflowid = required_param('workflowid', PARAM_INT);
$returnto   = optional_param('returnto', '', PARAM_ALPHA);

// Require login and a valid session key
require_login();
require_sesskey();

// Require the workflow:editdefinitions capability
require_capability('block/workflow:editdefinitions', get_context_instance(CONTEXT_SYSTEM));

// Toggle the workflow
$workflow = new block_workflow_workflow($workflowid);
$workflow->toggle();

// redirect as appropriate
if ($returnto == 'editsteps') {
    redirect(new moodle_url('/blocks/workflow/editsteps.php', array('workflowid' => $workflowid)));
}
else {
    redirect(new moodle_url('/blocks/workflow/manage.php'));
}
