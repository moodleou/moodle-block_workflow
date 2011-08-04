<?php

/**
 * Settings for workflow bock
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$settings = new admin_externalpage('blocksettingworkflow', get_string('pluginname', 'block_workflow'), 
                                   new moodle_url('/blocks/workflow/manage.php'), 'block/workflow:editdefinitions');
