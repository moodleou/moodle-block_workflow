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
 * Workflow Import
 *
 * @package   block_workflow
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once(dirname(__FILE__) . '/import_form.php');
require_once($CFG->libdir . '/adminlib.php');

// This page is part of the workflow block settings system.
admin_externalpage_setup('blocksettingworkflow');

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/workflow/import.php');

require_login();
require_capability('block/workflow:editdefinitions', context_system::instance());

// Moodle form.
$importform = new import_workflow();

if ($importform->is_cancelled()) {
    // Has the form been cancelled.
    redirect(new moodle_url('/blocks/workflow/manage.php'));
} else if ($data = $importform->get_data()) {
    $filecontent = $importform->get_file_content('importfile');
    // Content UTF8 validation.
    if (!is_utf8($filecontent)) {
        throw new block_workflow_invalid_import_exception(get_string('notutfencoding', 'block_workflow'));
    }

    $xml = simplexml_load_string($filecontent);

    // Is XML valid?
    if (!$xml) {
        $errors = get_string('xmlloadfailed', 'block_workflow');
        foreach (libxml_get_errors() as $error) {
            $errors.= $error->message.', ';
        }
        throw new block_workflow_invalid_import_exception($errors);
    }
    // Is it a workflow XML?
    if (!($xml->getName() == 'workflow')) {
        throw new block_workflow_invalid_import_exception(
               get_string('notaworkflow', 'block_workflow'));
    }

    // Import email templates.
    $email   = new block_workflow_email();

    // Begin importing process.
    $transaction = $DB->start_delegated_transaction();

    foreach ($xml->emailtemplates->emailtemplate as $importedtemplate) {
        // Prepare and validate template data.
        $templatex = new stdClass();
        $templatex->shortname = clean_and_check_field_validity('shortname', $importedtemplate);
        $templatex->subject   = clean_and_check_field_validity('subject', $importedtemplate);
        $templatex->message   = clean_and_check_field_validity('body', $importedtemplate);

        if ($email->load_email_shortname($templatex->shortname)) {
            // Warning about existance as spec suggests.
            notify(get_string('emailtemplateexists', 'block_workflow', $templatex->shortname));
        } else {
            $email->create($templatex);
        }
    }

    // Prepare and validate workflow data.
    $workflowx = new stdClass();
    $workflowx->shortname = clean_and_check_field_validity('shortname', $xml);
    $workflowx->name = clean_and_check_field_validity('name', $xml);
    $workflowx->description = clean_and_check_field_validity('description', $xml, true, true);
    $descriptionattrs = $xml->description->attributes();
    $workflowx->descriptionformat = block_workflow_convert_editor_format((string)$descriptionattrs['format']);
    $workflowx->appliesto = clean_and_check_field_validity('appliesto', $xml);

    // Record atendgobackto tag.
    $atendgobacktostep = clean_and_check_field_validity('atendgobacktostep', $xml, false);

    // Create workflow.
    $workflow = new block_workflow_workflow();
    $workflow = $workflow->create_workflow($workflowx, false, true);

    // Check whether the steps are incorrectly ordered and sort them.
    $steporder = array();
    foreach ($xml->steps->step as $importedstep) {
        $attributes = $importedstep->attributes();
        $stepno = (string)$attributes['no'];
        if (in_array($stepno, $steporder)) {
            $transaction->rollback(new block_workflow_invalid_import_exception(
                    get_string('notuniquestep', 'block_workflow', $stepno)));
        }
        $steporder[] = $stepno;
    }
    $steporder = array_flip($steporder);
    ksort($steporder);

    if (!empty($atendgobacktostep)) {
        // Ensure atendgobackto points to existing step.
        if (!array_key_exists($atendgobacktostep, $steporder)) {
            $transaction->rollback(new block_workflow_invalid_import_exception(
                    get_string('stepnotexist', 'block_workflow', $atendgobacktostep)));
        }
        $atendgobacktostepkey = $steporder[$atendgobacktostep];
    } else {
        $atendgobacktostep = null;
        $atendgobacktostepkey = null;
    }

    // Prepare required instances.
    $step = new block_workflow_step();
    $todo = new block_workflow_todo();

    // Add steps.
    foreach ($steporder as $stepkey) {
        // Prepare and validate step data.
        $importedstep = $xml->steps->step[$stepkey];
        $stepx = new stdClass();
        $stepx->name = clean_and_check_field_validity('name', $importedstep);
        $stepx->instructions = clean_and_check_field_validity('instructions', $importedstep, true, true);
        $instructionsattrs = $importedstep->instructions->attributes();
        $stepx->instructionsformat = block_workflow_convert_editor_format((string)$instructionsattrs['format']);
        $stepx->onactivescript = clean_and_check_field_validity('onactivescript', $importedstep, false);
        $stepx->oncompletescript = clean_and_check_field_validity('oncompletescript', $importedstep, false);
        $stepx->autofinish = clean_and_check_field_validity('autofinish', $importedstep, false);
        $stepx->autofinishoffset = clean_and_check_field_validity('autofinishoffset', $importedstep, false);
        $stepx->workflowid = $workflow->id;

        // Create the step.
        $step = $step->create_step($stepx);

        // Record new atendgobackto tag.
        if ($atendgobacktostepkey === $stepkey) {
            $atendgobacktostep = $step->stepno;
        }

        // Create todo items.
        $todox = new stdClass();
        $todox->stepid = $step->id;
        foreach ($importedstep->todo as $task) {
            $todox->task = trim($task);
            $todo->create_todo($todox);
        }

        // Validate and assign required roles.
        $roles = block_workflow_contextlevel_roles($step->workflow()->context());
        $roles = array_map(create_function('$a', 'return $a->shortname;'), $roles);
        $roles = array_flip($roles);
        foreach ($importedstep->doer as $doer) {
            $doer = trim($doer);
            if (!array_key_exists($doer, $roles)) {
                $transaction->rollback(new block_workflow_invalid_import_exception(
                        get_string('nosuchrole', 'block_workflow', $doer)));
            }
            $step->toggle_role($roles[$doer]);
        }
    }

    if ($atendgobacktostep) {
        // Update workflow with new atendgobackto tag.
        $updatedata = new stdClass();
        $updatedata->atendgobacktostep = $atendgobacktostep;
        $workflow->update($updatedata);
    }

    // Commit changes at this stage.
    $transaction->allow_commit();

    // Redirect.
    redirect(new moodle_url('/blocks/workflow/editsteps.php', array('workflowid' => $workflow->id)),
            get_string('importsuccess', 'block_workflow'), 10);
}

// Display the page.
echo $OUTPUT->header();

// The clone form.
$importform->display();

// Footer.
echo $OUTPUT->footer();

/**
 * Helper function to clean data and validate imported fields
 *
 * @param   string $fieldname  The fieldname to process
 * @param   object $xml        XML data object
 * @param   bool   $noempy     Ensure the string is not empty
 * @param   bool   $cleanhtml  Clean html (decode entities and remove CDATA)
 * @return  string      The checked and (potentially) modified text
 */
function clean_and_check_field_validity($fieldname, $xml, $noempty = true, $html = false) {
    if (!isset($xml->$fieldname)) {
        throw new block_workflow_invalid_import_exception(
                get_string('missingfield', 'block_workflow', $fieldname));
    }

    if ($html) {
        $field = preg_replace('/(\<\!\[CDATA\[)|(\]\]\>)/', '', trim((string) $xml->$fieldname));
        $field = html_entity_decode($field, ENT_QUOTES, 'UTF-8');
        $field = clean_param($field, PARAM_RAW);
    } else {
        $field = clean_param(trim((string) $xml->$fieldname), PARAM_CLEANHTML); // Not a great param type.

    }

    if ($noempty && strlen($field) < 1) {
        throw new block_workflow_invalid_import_exception(
                get_string('emptyfield', 'block_workflow', $fieldname));
    }

    return $field;
}

/**
 * Returns true if $string is valid UTF-8 and false otherwise.
 * Taken from lib/minify/lib/FirePHP.php (Minify 2.1.3 inport into Moodle)
 *
 * @param    mixed   $str    String to be tested
 * @return   boolean
 */
function is_utf8($str) {
    $b = 0;
    $c = 0;
    $bits = 0;
    $len = strlen($str);
    for ($i = 0; $i < $len; $i++) {
        // Check each character of the string.
        $c = ord($str[$i]);
        if ($c > 128) {
            if ($c >= 254) {
                return false;
            } else if ($c >= 252) {
                $bits = 6;
            } else if ($c >= 248) {
                $bits = 5;
            } else if ($c >= 240) {
                $bits = 4;
            } else if ($c >= 224) {
                $bits = 3;
            } else if ($c >= 192) {
                $bits = 2;
            } else {
                return false;
            }

            if (($i + $bits) > $len) {
                return false;
            }

            while ($bits > 1) {
                $i++;
                $b = ord($str[$i]);
                if ($b < 128 || $b > 191) {
                    return false;
                }
                $bits--;
            }
        }
    }
    return true;
}
