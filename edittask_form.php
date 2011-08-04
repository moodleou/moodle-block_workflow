<?php

/**
 * Form for editing a task
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}

require_once($CFG->libdir . '/formslib.php');

class task_edit extends moodleform {
    function definition() {
        $mform = $this->_form;
        $mform->addElement('text', 'task', get_string('task', 'block_workflow'));
        $mform->setType('task', PARAM_TEXT);
        $mform->addRule('task', null, 'required', null, 'client');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'stepid');
        $mform->setType('stepid', PARAM_INT);

        $this->add_action_buttons();
    }
}
