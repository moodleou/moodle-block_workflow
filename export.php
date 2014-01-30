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
 * Workflow XML export.
 *
 * @package   block_workflow
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once($CFG->libdir . '/filelib.php');

// Get the submitted paramaters.
$workflowid = required_param('workflowid', PARAM_INT);

// Require login and a valid session key.
require_login();
require_sesskey();

// Require the workflow:editdefinitions capability.
require_capability('block/workflow:editdefinitions', context_system::instance());

// Retrieve the workflow.
$workflow = new block_workflow_workflow($workflowid);

// Use SimpleXML to create the XML structure.
$wx = new SimpleXMLElement("<?xml version='1.0' encoding='utf-8'?><workflow/>");

// Add a version for potential future use.
$wx->addAttribute('version', 2);

// Set the data for the workflow itself.
$wx->addChild('shortname',           $workflow->shortname);
$wx->addChild('name',                check_output_text($workflow->name));
$d = $wx->addChild('description',    check_output_text($workflow->description));
$d->addAttribute('format',
        block_workflow_editor_format($workflow->descriptionformat));
$wx->addChild('appliesto',           $workflow->appliesto);
$wx->addChild('atendgobacktostep',   $workflow->atendgobacktostep);

// We need to store the various steps.
$sx = $wx->addChild('steps');

// Create a list of the workflow's steps.
// We need to store any used templates here.
$templatelist = array();
foreach ($workflow->steps() as $step) {
    // Load the object as we'll need to call some of it's functions.
    $step = new block_workflow_step($step->id);

    // Add the step and it's children/attributes.
    $stepx = $sx->addChild('step');
    $stepx->addAttribute('no',              $step->stepno);
    $stepx->addChild('name',                check_output_text($step->name));
    $i = $stepx->addChild('instructions',   check_output_text($step->instructions));
    $i->addAttribute('format',
            block_workflow_editor_format($step->instructionsformat));
    $stepx->addChild('onactivescript',      check_output_text($step->onactivescript));
    $stepx->addChild('oncompletescript',    check_output_text($step->oncompletescript));
    $stepx->addChild('autofinish',          check_output_text($step->autofinish));
    $stepx->addChild('autofinishoffset',    $step->autofinishoffset);

    // Add the roles for this step.
    foreach ($step->roles() as $role) {
        $stepx->addChild('doer',            $role->shortname);
    }

    // Add the todos for this step.
    foreach ($step->todos() as $todo) {
        $stepx->addChild('todo',            check_output_text($todo->task));
    }

    // As we loop through each step, we need to check each script for use
    // of e-mail emails and retrieve those for later inclusion.
    $commands = array();
    $script = block_workflow_step::parse_script($step->onactivescript);
    $commands = array_merge($commands, $script->commands);

    $script = block_workflow_step::parse_script($step->oncompletescript);
    $commands = array_merge($commands, $script->commands);

    // Check each command and retreieve any templates in use.
    foreach ($commands as $c) {
        if ($c->command != 'email') {
            continue;
        }
        // Parse the command to retrieve the data.
        $class = new block_workflow_command_email();
        $d = $class->parse($c->arguments, $step);

        // And retrieve the template.
        $errors = array();
        $t = $class->email($d->emailname, $errors);
        $templatelist[$d->emailname] = $t;
    }
}

$emailsx = $wx->addChild('emailtemplates');

// Now add any e-mail templates that this workflow currently uses.
// To do this, we need to attempt to validate.
foreach ($templatelist as $t) {

    $etx = $emailsx->addChild('emailtemplate');
    $etx->addChild('shortname', $t->shortname);
    $etx->addChild('subject',   check_output_text($t->subject));
    $etx->addChild('body',      check_output_text($t->message));
}

// Calculate the filename.
$filename = preg_replace('/[^a-zA-Z0-9\.-_]/', '_', $workflow->shortname) . '.workflow.xml';

// Export the XML with linebreaks so its more human readable.
$dom = dom_import_simplexml($wx)->ownerDocument;
$dom->formatOutput = true;

// Actually download the file.
send_file($dom->saveXML(), $filename, 0, 0, true, true);

/**
 * Helper function to determine whether we need to add a CDATA tag for the
 * provided text and return the modifed string.
 *
 * @param   string $raw The raw string to check
 * @return  string      The checked and (potentially) modified text
 */
function check_output_text($raw) {
    return htmlspecialchars($raw, ENT_NOQUOTES, 'UTF-8');
}
