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
 * Privacy subsystem implementation for block_workflow.
 *
 * @package block_workflow
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_workflow\privacy;

use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\contextlist;
use \core_privacy\local\request\writer;
use \core_privacy\local\request\helper;
use \core_privacy\local\metadata\collection;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy subsystem implementation for block_workflow.
 *
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        // This plugin has data.
        \core_privacy\local\metadata\provider,

        // This plugin currently implements the original plugin\provider interface.
        \core_privacy\local\request\plugin\provider {

    /**
     * Returns meta data about this system.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection) : collection {
        // The 'block_workflow_state_changes' table stores the state change by user.
        $collection->add_database_table('block_workflow_state_changes', [
            'userid' => 'privacy:metadata:block_workflow_state_changes:userid',
            'newstate' => 'privacy:metadata:block_workflow_state_changes:newstate',
        ], 'privacy:metadata:block_workflow_state_changes');

        // The 'block_workflow_todo_done' table stores the to do task change by user.
        $collection->add_database_table('block_workflow_todo_done', [
            'userid' => 'privacy:metadata:block_workflow_todo_done:userid',
            'steptodoid' => 'privacy:metadata:block_workflow_todo_done:steptodoid',
        ], 'privacy:metadata:block_workflow_todo_done');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT DISTINCT c.id
                  FROM {context} c
             LEFT JOIN {block_instances} b ON b.id = c.instanceid OR b.parentcontextid = c.id
             LEFT JOIN {block_workflow_step_states} states ON states.contextid = c.id
             LEFT JOIN {block_workflow_steps} steps ON steps.id = states.stepid
             LEFT JOIN {block_workflow_state_changes} statechanges ON statechanges.stepstateid = states.id
             LEFT JOIN {block_workflow_step_todos} todos ON todos.stepid = steps.id
             LEFT JOIN {block_workflow_todo_done} done ON done.steptodoid = todos.id
                 WHERE b.blockname = 'workflow'
                       AND (statechanges.userid = :statechangesuserid OR done.userid = :tododoneuserid)";

        $params = [
            'statechangesuserid' => $userid,
            'tododoneuserid' => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);
        return $contextlist;
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist)) {
            return;
        }

        $user = $contextlist->get_user();

        foreach ($contextlist as $context) {
            // Fetch the generic module data.
            $contextdata = helper::get_context_data($context, $user);

            // Select workflow info name and description.
            // Select the step state and user who cause state change.
            $sql = "SELECT wf.name AS workflowname, wf.description,
                           step.name AS stepname,
                           statechanges.userid, statechanges.newstate
                      FROM {block_workflow_step_states} states
                 LEFT JOIN {block_workflow_steps} step ON step.id = states.stepid
                 LEFT JOIN {block_workflow_state_changes} statechanges ON statechanges.stepstateid = states.id
                 LEFT JOIN {block_workflow_workflows} wf ON wf.id = step.workflowid
                     WHERE statechanges.userid = :statechangesuserid
                           AND states.contextid = :stepstatecontextid
                  ORDER BY step.id ASC";
            $params = [
                'stepstatecontextid' => $context->id,
                'statechangesuserid' => $user->id
            ];
            $rs = $DB->get_recordset_sql($sql, $params);

            $index = 0;
            foreach ($rs as $rec) {
                // State change data.
                $statechangedata = [
                    'workflowname' => $rec->workflowname,
                    'description' => $rec->description,
                    'stepname' => $rec->stepname,
                    'userid' => self::you_or_somebody_else($rec->userid, $user),
                    'newstate' => $rec->newstate
                ];

                if (empty($contextdata->statechangedata)) {
                    $contextdata->statechangedata = [];
                }
                $contextdata->statechangedata[$index] = (object)$statechangedata;
                $index++;
            }
            $rs->close();

            // Select to-do done task and the user who done the task.
            $sql = "SELECT steps.name AS stepname,
                           todos.task AS taskdone, done.userid
                      FROM {block_workflow_todo_done} done
                 LEFT JOIN {block_workflow_step_todos} todos ON done.steptodoid = todos.id
                 LEFT JOIN {block_workflow_step_states} states ON states.id = done.stepstateid
                 LEFT JOIN {block_workflow_steps} steps ON steps.id = states.stepid
                     WHERE done.userid = :doneuserid
                           AND states.contextid = :statescontextid
                  ORDER BY done.id ASC";

            $params = [
                'statescontextid' => $context->id,
                'doneuserid' => $user->id
            ];
            $rs = $DB->get_recordset_sql($sql, $params);

            $index = 0;
            foreach ($rs as $rec) {
                // To-do done data.
                $donedata = [
                    'stepname' => $rec->stepname,
                    'taskdone' => $rec->taskdone,
                    'userid' => self::you_or_somebody_else($rec->userid, $user)
                ];

                if (empty($contextdata->tododonedata)) {
                    $contextdata->tododonedata = [];
                }
                $contextdata->tododonedata[$index] = (object)$donedata;
                $index++;
            }
            $rs->close();

            // Write out context data.
            writer::with_context($context)->export_data([get_string('pluginname', 'block_workflow')], $contextdata);
        }
    }

    /**
     * Removes personally-identifiable data from a user id for export.
     *
     * @param int $userid User id of a person
     * @param \stdClass $user Object representing current user being considered
     * @return string 'You' if the two users match, 'Somebody else' otherwise
     */
    protected static function you_or_somebody_else($userid, $user) {
        if ($userid == $user->id) {
            return get_string('privacy_you', 'block_workflow');
        } else {
            return get_string('privacy_somebodyelse', 'block_workflow');
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        $params = [
            'statescontextid' => $context->id,
            'adminuserid' => get_admin()->id
        ];

        // Keep all the data but anonymise with the admin user id.
        // Block_workflow_todo_done table.
        $tododonesql = "UPDATE {block_workflow_todo_done}
                           SET userid = :adminuserid
                         WHERE id IN (SELECT done.id
                                        FROM {block_workflow_todo_done} done
                                   LEFT JOIN {block_workflow_step_states} states
                                             ON states.id = done.stepstateid
                                       WHERE states.contextid = :statescontextid)";

        $DB->execute($tododonesql, $params);

        // Block_workflow_state_changes table.
        $statechangesql = "UPDATE {block_workflow_state_changes}
                              SET userid = :adminuserid
                            WHERE id IN (SELECT statechanges.id
                                           FROM {block_workflow_state_changes} statechanges
                                      LEFT JOIN {block_workflow_step_states} states
                                                ON states.id = statechanges.stepstateid
                                          WHERE states.contextid = :statescontextid)";

        $DB->execute($statechangesql, $params);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }
        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            $params = [
                'statescontextid' => $context->id,
                'userid' => $userid,
                'adminuserid' => get_admin()->id
            ];

            // Delete block_workflow_todo_done owned by user.
            // To_do done task is shared thing. Update userid to admin user.
            $tododonesql = "UPDATE {block_workflow_todo_done}
                               SET userid = :adminuserid
                             WHERE id IN (SELECT done.id
                                            FROM {block_workflow_todo_done} done
                                       LEFT JOIN {block_workflow_step_states} states
                                                 ON states.id = done.stepstateid
                                           WHERE done.userid = :userid
                                                 AND states.contextid = :statescontextid)";

            $DB->execute($tododonesql, $params);

            // To delete block_workflow_state_changes caused by user. 
            // Workflow is shared thing. Do not go back to earlier step. 
            // So change the userid to admin user.
            $statechangesql = "UPDATE {block_workflow_state_changes}
                                  SET userid = :adminuserid
                                WHERE id IN (SELECT statechanges.id
                                               FROM {block_workflow_state_changes} statechanges
                                          LEFT JOIN {block_workflow_step_states} states
                                                    ON states.id = statechanges.stepstateid
                                              WHERE statechanges.userid = :userid
                                                    AND states.contextid = :statescontextid)";

            $DB->execute($statechangesql, $params);
        }
    }
}
