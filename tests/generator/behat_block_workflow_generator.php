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
 * Generator class for behat setup steps
 *
 * @package    block_workflow
 * @copyright  2020 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class behat_block_workflow_generator extends behat_generator_base {

    protected function get_creatable_entities(): array {
        return [
            'workflows' => [
                'datagenerator' => 'workflow',
                'required' => [],
            ],
            'workflow steps' => [
                'datagenerator' => 'workflow_step',
                'required' => ['workflow'],
                'switchids' => ['workflow' => 'workflowid'],
            ],
        ];
    }

    /**
     * Look up the id of a workflow from its shortname.
     *
     * @param string $shortname the workflow shortname, for example 'coursewf'.
     * @return int corresponding id.
     */
    protected function get_workflow_id(string $shortname): int {
        global $DB;

        if (!$id = $DB->get_field('block_workflow_workflows', 'id', ['shortname' => $shortname])) {
            throw new Exception("There is no workflow with shortname '$shortname'.");
        }
        return $id;
    }
}
