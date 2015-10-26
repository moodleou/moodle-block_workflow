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
 * Definition of Workflow scheduled tasks.
 * Default is to run once every minute.
 *
 * @package block_workflow
 * @category task
 * @copyright 2015 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tasks = array(
    // Run once a day after 05:01 AM.
    array(
        'classname' => 'block_workflow\task\send_extra_notification',
        'blocking' => 0,
        'minute' => '1',
        'hour' => '5',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*'
    ),
    // Run once a day after 01:01 AM.
    array(
        'classname' => 'block_workflow\task\finish_step_automatically',
        'blocking' => 0,
        'minute' => '1',
        'hour' => '1',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*'
    )
);
