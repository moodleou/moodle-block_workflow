<?php

/**
 * Workflow block
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

class import_workflow extends moodleform {
    function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('workflowimport', 'block_workflow'));

        // File
        $mform->addElement('filepicker', 'importfile', get_string('importfile', 'block_workflow'),
                null, array('accepted_type' => '*.xml'));

        $this->add_action_buttons(true, get_string('importworkflow', 'block_workflow'));
    }
}

