<?php

/**
 * Workflow block role capability definitions
 *
 * @package    block
 * @subpackage workflow
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$capabilities = array(

    // By default given to manager
    // Allows access to, and use of the workflow definition
    'block/workflow:editdefinitions'    => array(
        'captype'       => 'write',
        'contextlevel'  => CONTEXT_SYSTEM,
        'archetypes'    => array(
            'manager'   => CAP_ALLOW,
        )
    ),

    // By default given to manager and editingteacher
    // Allows users to see the workflow block
    'block/workflow:view'   => array(
        'captype'       => 'read',
        'contextlevel'  => CONTEXT_COURSE,
        'archetypes'    => array(
            'manager'           => CAP_ALLOW,
            'editingteacher'    => CAP_ALLOW,
        )
    ),

    // By default given to manager
    // Allows use of the 'Jump to step' button
    'block/workflow:manage' => array(
        'captype'       => 'write',
        'contextlevel'  => CONTEXT_COURSE,
        'archetypes'    => array(
            'manager'   => CAP_ALLOW,
        )
    ),

    // By default given to manager
    // Allows use of the 'Finish step' button
    'block/workflow:dostep' => array(
        'captype'       => 'write',
        'contextlevel'  => CONTEXT_COURSE,
        'archetypes'    => array(
            'manager'   => CAP_ALLOW,
        )
    ),

);
