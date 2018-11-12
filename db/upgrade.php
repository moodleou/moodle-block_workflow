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
 * DB upgrade
 *
 * @package   block_workflow
 * @copyright 2012 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_block_workflow_upgrade($oldversion) {
    global $DB, $CFG;

    $dbman = $DB->get_manager();

    if ($oldversion < 2012101700) {
        $table = new xmldb_table('block_workflow_steps');

        $field = new xmldb_field('autofinish', XMLDB_TYPE_CHAR, '64', null, null, null, null, 'oncompletescript');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('autofinishoffset', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'autofinish');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_block_savepoint(true, 2012101700, 'workflow');
    }

    // Override the oldformat.
    if ($oldversion < 2013042300) {
        $sql = "SELECT step.*
            FROM {block_workflow_steps} step
            WHERE step.autofinish = 'quiz_timeopen'
                OR step.autofinish = 'quiz_timeclose'
                OR step.autofinish = 'course_startdate'
            ORDER BY step.id ASC";
        $steps = $DB->get_records_sql($sql);
        if ($steps) {
            foreach ($steps as $key => $step) {
                $step->autofinish = str_replace('_', ';', $step->autofinish);
                $DB->update_record('block_workflow_steps', $step);
            }
        }
        upgrade_block_savepoint(true, 2013042300, 'workflow');
    }

    // Add 'messageformat' field to the 'block_workflow_emails' table.
    if ($oldversion < 2013071600) {
        $table = new xmldb_table('block_workflow_emails');
        $field = new xmldb_field('messageformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'message');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            if ($CFG->texteditors !== 'textarea') {
                $rs = $DB->get_recordset('block_workflow_emails',
                        array('messageformat' => FORMAT_MOODLE), '', 'id,message,messageformat');
                foreach ($rs as $b) {
                    $b->message = text_to_html($b->message, false, false, true);
                    $b->messageformat = FORMAT_HTML;
                    $DB->update_record('block_workflow_emails', $b);
                    upgrade_set_timeout();
                }
                $rs->close();
            }
        }
        upgrade_block_savepoint(true, 2013071600, 'workflow');
    }

    // Replace 'course:startdate' with 'course;startdate'.
    if ($oldversion < 2013072200) {
        $sql = "UPDATE {block_workflow_steps} SET autofinish = :new WHERE autofinish = :old";
        $DB->execute($sql, array('new' => 'course;startdate', 'old' => 'course:startdate'));
        upgrade_block_savepoint(true, 2013072200, 'workflow');
    }

    // Fix broken autofinish values.
    if ($oldversion < 2014030500) {
        $DB->set_field_select('block_workflow_steps', 'autofinish', null,
                'autofinish IN (?, ?)', array('', 'donotautomaticallyfinish'));
        upgrade_block_savepoint(true, 2014030500, 'workflow');
    }

    if ($oldversion < 2015101500) {
        $table = new xmldb_table('block_workflow_steps');

        $field = new xmldb_field('extranotify', XMLDB_TYPE_CHAR, '64', null, null, null, null, 'autofinishoffset');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('extranotifyoffset', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'extranotify');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('onextranotifyscript', XMLDB_TYPE_TEXT, null, null, null, null, null, 'extranotifyoffset');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_block_savepoint(true, 2015101500, 'workflow');
    }

    if ($oldversion < 2017103100) {
        // For a while we were missing code when the step settings were saved, so
        // we need to fix up any bad data in the database.
        $DB->execute("
                UPDATE {block_workflow_steps}
                   SET extranotify = NULL
                 WHERE extranotify = ''
                ");

        upgrade_block_savepoint(true, 2017103100, 'workflow');
    }

    if ($oldversion < 2018111200) {

        // Because of previous issues, there may be a duplicate index
        // o_blocworkstepdoer_ste2_ix. We drop this manually, not using
        // $dbman->drop_key, because we want to be sure to drop the ...2... index.
        $indexes = $DB->get_indexes('block_workflow_step_doers');
        foreach ($indexes AS $indexname => $notused) {
            if ($indexname === 'o_blocworkstepdoer_ste2_ix') {
                $dropsql = $dbman->generator->drop_index_sql;
                $dropsql = str_replace('TABLENAME', 'block_workflow_step_doers', $dropsql);
                $dropsql = str_replace('INDEXNAME', 'o_blocworkstepdoer_ste2_ix', $dropsql);
                $DB->change_database_structure($dropsql, ['block_workflow_step_doers']);
            }
        }

        // Workflow savepoint reached.
        upgrade_block_savepoint(true, 2018111200, 'workflow');
    }

    if ($oldversion < 2018111201) {

        // Define key roleid (foreign) to be added to block_workflow_step_doers.
        $table = new xmldb_table('block_workflow_step_doers');
        $key = new xmldb_key('roleid', XMLDB_KEY_FOREIGN, array('roleid'), 'role', array('id'));

        // Launch add key roleid.
        $dbman->add_key($table, $key);

        // Workflow savepoint reached.
        upgrade_block_savepoint(true, 2018111201, 'workflow');
    }

    return true;
}
