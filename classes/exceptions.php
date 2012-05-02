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
 * Workflow block exception classes.
 *
 * @package   block_workflow
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');


/**
 * Base Block Workflow exception
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_workflow_exception                  extends moodle_exception{}

/**
 * Workflow not assigned exception
 *
 * This exception is typically thrown when trying to load the active workflow for a context which
 * has no workflow assigned
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_workflow_not_assigned_exception     extends block_workflow_exception{}

/**
 * Invalid Workflow exception
 *
 * This exception is typically thrown when attempting to load a workflow which does not exist
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_workflow_invalid_workflow_exception extends block_workflow_exception{}

/**
 * Invalid step exception
 *
 * This exception is typically thrown when attempting to load a step which does not exist
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_workflow_invalid_step_exception     extends block_workflow_exception{}

/**
 * Invalid command exception
 *
 * This exception is typically thrown when attempting to use a command which does not exist
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_workflow_invalid_command_exception  extends block_workflow_exception{}

/**
 * Invalid email exception
 *
 * This exception is typically thrown when attempting to load a email which does not exist
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_workflow_invalid_email_exception extends block_workflow_exception{}

/**
 * Invalid todo exception
 *
 * This exception is typically thrown when attempting to load a todo which does not exist
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_workflow_invalid_todo_exception extends block_workflow_exception{}

/**
 * AJAX exception
 *
 * This exception is typically thrown when an AJAX script attempts to use an invalid command
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_workflow_ajax_exception             extends block_workflow_exception{}

/**
 * Invalid import exception
 *
 * This exception is typically thrown on importing validation errors
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_workflow_invalid_import_exception extends block_workflow_exception{}
