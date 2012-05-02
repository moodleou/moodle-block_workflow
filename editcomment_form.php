<?php

/**
 * Form for comments
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}

require_once(dirname(__FILE__) . '/locallib.php');
require_once($CFG->libdir . '/formslib.php');

class state_editcomment extends moodleform {
    function definition() {
        $mform = $this->_form;
        $state = $this->_customdata['state'];

        $mform->addElement('header', 'general', get_string('updatecomment', 'block_workflow'));

        // Workflow and step information
        $mform->addElement('static', 'workflowname',    get_string('workflow', 'block_workflow'));
        $mform->addElement('static', 'stepname',        get_string('step', 'block_workflow'));
        $mform->addElement('static', 'instructions',    get_string('instructions', 'block_workflow'));

        // The comment to update
        $mform->addElement('editor', 'comment_editor', get_string('commentlabel', 'block_workflow'),
                block_workflow_editor_options());
        $mform->setType('comment_editor', PARAM_RAW);

        // The stateid (we need this)
        $mform->addElement('hidden', 'stateid');
        $mform->setType('stateid', PARAM_INT);

        $this->add_action_buttons(true, get_string('updatecomment', 'block_workflow'));
    }
}
