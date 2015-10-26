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
 * A scheduled task for workflow automatic step finisher.
 *
 * @package block_workflow
 * @copyright 2015 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_workflow\task;

class finish_step_automatically extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('crontaskautostepfinisher', 'block_workflow');
    }

    public function execute() {
        global $CFG, $DB;

        require_once(dirname(__FILE__).'/../../locallib.php');

        // Run automatic step finisher.
        try {
            block_workflow_autofinish_steps();
        } catch (\Exception $e) {
            $error = $e->getMessage();
            mtrace('Workflow Automatic step finisher stopped at ' . date('H:i:s') );
            mtrace($error);
        }
    }
}
