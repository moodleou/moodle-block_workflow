<?php

/**
 * create or update an existing email template
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/editemail_form.php');
require_once($CFG->libdir . '/adminlib.php');

// Get the submitted paramaters
$emailid = optional_param('emailid', 0, PARAM_INT);
$email   = new block_workflow_email();

// This is an admin page
admin_externalpage_setup('blocksettingworkflow');

// Require login
require_login();

// Require the workflow:editdefinitions capability
require_capability('block/workflow:editdefinitions', get_context_instance(CONTEXT_SYSTEM));

// Set the page and return urls
$PAGE->set_url('/blocks/workflow/editemail.php');
$returnurl  = new moodle_url('/blocks/workflow/manage.php');

if ($emailid) {
    $email->load_email_id($emailid);
    $title = get_string('editemail', 'block_workflow', $email->shortname);
}
else {
    $title = get_string('createemail', 'block_workflow');
}

// Set the heading and page title
$PAGE->set_title($title);
$PAGE->set_heading($title);

// Add the breadcrumbs
if ($email->id) {
    $PAGE->navbar->add(get_string('edittemplate', 'block_workflow'));
}
else {
    $PAGE->navbar->add(get_string('createtemplate', 'block_workflow'));
}

// Create the form
$emailform = new email_edit();

if ($emailform->is_cancelled()) {
    // Form was cancelled
    redirect($returnurl);
}
else if ($formdata = $emailform->get_data()) {
    // Form has been submitted
    $data = new stdClass();
    $data->shortname    = $formdata->shortname;
    $data->subject      = $formdata->subject;
    $data->message      = $formdata->message;

    if ($formdata->emailid) {
        // emailid specified, so we're updating
        $email->update($data);
    }
    else {
        // Creating a new template
        $email->create($data);
    }
    redirect($returnurl);
}

// Set the form defaults
$email->emailid = $email->id;
$emailform->set_data($email);

// Grab the renderer
$renderer = $PAGE->get_renderer('block_workflow');

// Display the page and form
echo $OUTPUT->header();
echo $renderer->email_template_instructions($email);
$emailform->display();
echo $OUTPUT->footer();
