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
 * Workflow block english language strings
 *
 * @package   block_workflow
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Core strings.
$string['workflow:addinstance'] = 'Add a new workflow block';
$string['pluginname']                   = 'Workflows';
$string['workflow']                     = 'Workflow';

$string['activetasktitle']              = 'Currently active task';
$string['addaworkflow']                 = 'Add a workflow';
$string['addanotherworkflow']           = 'Add another workflow';
$string['addemail']                     = 'Add email template';
$string['addroletostep']                = 'Add role to step';
$string['addstep']                      = 'Add an additional step to this workflow';
$string['addstepafter']                 = 'Add a step after this point';
$string['addtask']                      = 'Add task';
$string['any']                          = 'Any ';
$string['appliesto']                    = 'Applies to';
$string['atendfinishworkflow']          = 'finish the workflow';
$string['atendgobackatworkflowcreate']  = 'An atendgobacktostep setting cannot be specified at workflow creation as no steps exist to reference';
$string['atendgobacktostep']            = 'At the end of step {$a}';
$string['atendgobacktostepinfo']        = 'After step {$a->stepcount}, go back to step number {$a->atendgobacktostep}.';
$string['atendgobacktostepno']          = 'go back to step {$a->stepno} ({$a->name})';
$string['atendstop']                    = 'After step {$a->stepcount}, this workflow will end.';
$string['automaticallyfinish']          = 'Automatically finish';

$string['cannotdeleteworkflowinuseby']  = 'Cannot delete, this workflow is used in {$a} places.';
$string['cannotremoveemailinuse']       = 'Unable to remove email template -- this template is currently in use';
$string['cannotremoveonlystep']         = 'Unable to remove step. This is the only workflow in the step';
$string['cannotremovestepsinuse']       = 'Unable to remove step. This step is currently active in {$a} workflows';
$string['cannotremoveworkflowinuse']    = 'Unable to remove workflow -- this workflow is currently in use';
$string['cannotstepflowinuse']          = 'Unable to remove step -- this step is currently in use';
$string['clone']                        = 'Clone';
$string['clonedname']                   = '{$a} (cloned)';
$string['clonedshortname']              = '{$a}_cloned';
$string['cloneworkflow']                = 'Clone workflow';
$string['cloneworkflowinstructions']    = 'Clone workflow instructions';
$string['cloneworkflowname']            = 'Clone workflow \'{$a}\'';
$string['commentlabel']                 = 'Update workflow comment';
$string['comments']                     = 'Comments';
$string['confirm']                      = 'Confirm';
$string['confirmemaildeletetitle']      = 'Delete email template \'{$a}\'?';
$string['confirmfinishstep']            = 'Are you sure that you want to mark this step ({$a}) as complete?';
$string['confirmjumptostep']            = 'Are you sure that you want to jump to step {$a->stepno} ({$a->stepname})?';
$string['confirmstepdeletetitle']       = 'Delete step \'{$a}\'?';
$string['confirmworkflowdeletetitle']   = 'Delete workflow \'{$a}\'?';
$string['contexthasactiveworkflow']     = 'This context already has an active workflow.';
$string['coursestudentclose']           = 'the course is closed to students';
$string['coursestudentopen']            = 'the course is opened to students';
$string['coursetutorclose']             = 'the course is closed to tutors';
$string['coursetutoropen']              = 'the course is opened to tutors';
$string['coursestartdate']              = 'the course start date';
$string['create']                       = 'Create';
$string['createemail']                  = 'Create new email template';
$string['createstep']                   = 'Create step';
$string['createstepinstructions']       = 'Some instructions on how to create a step';
$string['createstepname']               = 'Create new step for workflow \'{$a}\'';
$string['createtask']                   = 'Creating new task for step {$a}';
$string['createtemplate']               = 'Create template';
$string['createworkflow']               = 'Create new workflow';
$string['crontaskautostepfinisher']     = 'Workflow step finisher';
$string['crontaskextranotify']          = 'Workflow step extra notify';
$string['currentlyinuseby']             = 'This workflow is currently in use by';

$string['days']                         = 'Days';
$string['dayas']                        = 'same day as';
$string['dayafter']                     = '{$a} day after';
$string['daysafter']                    = '{$a} days after';
$string['daybefore']                    = '{$a} day before';
$string['daysbefore']                   = '{$a} days before';
$string['defaultonactivescript']        = '# You may place a set of actions to complete when marking this step active here';
$string['defaultoncompletescript']      = '# You may place a set of actions to complete when marking this step finished here';
$string['defaultonextranotifyscript']   = '# You may place a set of actions to  marking this step send notification';
$string['defaultstepdescription']       = 'A description for this step should go here';
$string['defaultstepinstructions']      = 'Do x, then y, then z.';
$string['defaultstepname']              = 'First step';
$string['defaultworkflowdescription']   = 'A description for this workflow';
$string['delete']                       = 'Delete';
$string['deleteemail']                  = 'Delete email';
$string['deleteemailcheck']             = 'Are you absolutely sure that you want to completely delete the email template \'{$a}\'?';
$string['deletestep']                   = 'Delete step';
$string['deletestepcheck']              = 'Are you absolutely sure that you want to completely delete the step \'{$a}\'?';
$string['deletetask']                   = 'Delete task';
$string['deletetaskcheck']              = 'Are you sure you wish to delete the task \'{$a->taskname}\' from step \'{$a->stepname}\'?';
$string['deletetasktitle']              = 'Delete task \'{$a->taskname}\' for step \'{$a->stepname}\' confirmation';
$string['deletetemplate']               = 'Delete template';
$string['deleteworkflow']               = 'Delete workflow';
$string['deleteworkflowcheck']          = 'Are you absolutely sure that you want to completely delete the workflow {$a}?';
$string['description']                  = 'Description';
$string['disabled']                     = 'Disabled';
$string['disableworkflow']              = 'Disable workflow';
$string['doerstitle']                   = 'Roles';
$string['doertitle']                    = 'Roles responsible for this step';
$string['donotautomaticallyfinish']     = 'Do not automatically finish';
$string['donotnotify']                  = 'Do not send extra notification';

$string['edit']                         = 'Edit';
$string['editcomments']                 = 'Edit comments';
$string['editemail']                    = 'Edit email template \'{$a}\'';
$string['editingcommentfor']            = 'Editing comment for \'{$a->stepname}\' on {$a->contextname}';
$string['editstep']                     = 'Edit step';
$string['editstepinstructions']         = 'Some instructions on what this page is for and a general page introduction. Mention the scripts, but their help files should give more information on how they work.';
$string['editstepname']                 = 'Editing step \'{$a}\'';
$string['editsteps']                    = 'Edit steps for workflow \'{$a}\'';
$string['edittask']                     = 'Edit task';
$string['edittemplate']                 = 'Edit template';
$string['edittemplateinstructions']     = 'Some instructions on how to create an email template';
$string['editworkflow']                 = 'Editing workflow \'{$a}\'';
$string['editworkflowinstructions']     = 'Edit workflow instructions';
$string['emaildescription']             = 'E-mail templates may be used by the various scripts in a workflow step';
$string['emailfrom']                    = '{$a} workflow system';
$string['emaillist']                    = 'Email email templates';
$string['emailmessage']                 = 'Message';
$string['emailsettings']                = 'E-mail template settings';
$string['emailsubject']                 = 'Subject';
$string['emailtemplateexists']          = 'Email template \'{$a}\' which was attempted to import already exists. Existing template is preserved.';
$string['emptyfield']                   = 'The required field is empty: {$a}';
$string['enabled']                      = 'Enabled';
$string['enabledworkflow']              = 'Enabled';
$string['enableworkflow']               = 'Enable workflow';
$string['eventstepaborted']             = 'Step aborted';
$string['eventstepactivated']           = 'Step activated';
$string['eventstepcompleted']           = 'Step completed';
$string['eventtodotriggered']           = 'Todo triggered';
$string['eventstepextranotification']   = 'Step extra notification processed';
$string['export']                       = 'Export';
$string['exportworkflow']               = 'Export workflow';

$string['finish']                       = 'Finish';
$string['finishstep']                   = 'Finish step';
$string['finishstepautomatically']      = 'This step was automatically finished by workflow system at {$a}.';
$string['finishstepfor']                = 'Finish step \'{$a->stepname}\' on {$a->contextname}';
$string['finishstepinstructions']       = 'You are about to mark this step as complete, and move to the next step in the workflow. A summary of the step is shown below -- you may wish to update the comment below.';
$string['format_html']                  = 'html';
$string['format_plain']                 = 'plain';
$string['format_unknown']               = 'unknown';

$string['general']                      = 'General';

$string['hidetask']                     = 'Disable task';

$string['importfile']                   = 'File';
$string['importsuccess']                = 'Importing was sucessful. You will be redirected to workflow editing page shortly.';
$string['importworkflow']               = 'Import workflow';
$string['instructions']                 = 'Instructions';
$string['inuseby']                      = 'It is currently in use in {$a} locations.';
$string['invalidactivitysettingcolumn'] = 'The column specified ({$a}) does not exist.';
$string['invalidappliestomodule']       = 'An invalid appliesto value was specified';
$string['invalidappliestotable']        = 'The database table for {$a} was not found. It may not be possible to use this command for this type of workflow';
$string['invalidbody']                  = 'An invalid body was specified';
$string['invalidcapability']            = 'Invalid capability specified.';
$string['invalidclearmustendcommand']   = 'There should be nothing after the word \'clear\'.';
$string['invalidcommand']               = 'An invalid command was specified in the script. The command used was {$a}';
$string['invalidemailemail']            = 'An invalid email email was specified. The email specified was \'{$a}\'';
$string['invalidemailshortname']        = 'Invalid shortname specified ({$a})';
$string['invalidfield']                 = 'An invalid field was specified in the data. The field was \'{$a}\'';
$string['invalidformat']                = 'Invalid format has been specified: {$a}';
$string['invalidid']                    = 'An invalid id was specified';
$string['invalidinstructions']          = 'Invalid step instructions were specified';
$string['invalidmissingvalue']          = 'Invalid command, value is missing.';
$string['invalidname']                  = 'An invalid name was specified';
$string['invalidobsoletesetting']       = 'An invalid obsolete value was specified. Valid settings are 0, or 1';
$string['invalidpermission']            = 'Invalid permission specified. The valid permissions are inherit, allow, prevent, or prohibit.';
$string['invalidrole']                  = 'An invalid role ({$a}) was specified whilst processing the script';
$string['invalidscript']                = 'The script you specified was invalid. {$a}';
$string['invalidshortname']             = 'An invalid shortname was specified';
$string['invalidstate']                 = 'Invalid state';
$string['invalidstep']                  = 'Invalid step specified.';
$string['invalidstepid']                = 'Invalid step id specified.';
$string['invalidstepno']                = 'Invalid step number specified.';
$string['invalidsubject']               = 'An invalid subject was specified';
$string['invalidsyntaxmissingto']       = 'Invalid command syntax - missing to component';
$string['invalidsyntaxmissingx']        = 'Invalid command syntax - missing \'{$a}\'.';
$string['invalidtarget']                = 'Invalid activity target';
$string['invalidtodo']                  = 'Invalid todo specified';
$string['invalidvisibilitysetting']     = 'An invalid visibility option was specified. Valid options are visible and hidden. You specified {$a}.';
$string['invalidwordnotclearorset']     = 'Expected \'clear\' or \'set\'.';
$string['invalidworkflow']              = 'Invalid workflow specified.';
$string['invalidworkflowid']            = 'An invalid workflow was specified';
$string['invalidworkflowname']          = 'An invalid workflow name was specified';
$string['invalidworkflowstepno']        = 'The specified step number could not be found in this workflow';

$string['jumpstep']                     = 'Jump to step';
$string['jumptostep']                   = 'Jump to step';
$string['jumptostepcheck']              = 'Are you sure you wish to jump from step \'{$a->fromstep}\' to step \'{$a->tostep}\' for the workflow on {$a->workflowon}?';
$string['jumptostepcommentaddition']    = '<p>[Note: the workflow just jumped from step \'{$a->fromstep}\'. This comment may seem out-of-context.]</p>{$a->comment}';
$string['jumptostepon']                 = 'Jump to step \'{$a->stepname}\' on {$a->contextname}';
$string['jumptosteptitle']              = 'Jump to step \'{$a->tostep}\' for \'{$a->workflowon}\' confirmation';

$string['lastmodified']                 = 'Last modified';

$string['managedescription']            = 'On this page you can create end edit workflows and the email templates that they use.';
$string['manageemails']                 = 'Manage email templates';
$string['manageworkflows']              = 'Manage workflows';
$string['messageprovider:notification'] = 'Workflow notifications and alerts';
$string['missingfield']                 = 'The required field is missing: {$a}';
$string['movedown']                     = 'Move down';
$string['moveup']                       = 'Move up';

$string['name']                         = 'Name';
$string['nameinuse']                    = 'The name specified is already in use. Names must be unique';
$string['nameshortname']                = '{$a->name} ({$a->shortname})';
$string['noactiveworkflow']             = 'There is currently no active workflow.';
$string['nocomment']                    = 'No comment yet...';
$string['nocomments']                   = 'No comments have been made about this step yet';
$string['nomorestepsleft']              = 'The workflow has been completed.';
$string['norolesspecified']             = 'No roles were specified';
$string['nosuchrole']                   = 'Role {$a} does not exist';
$string['notacourse']                   = 'This is not a course';
$string['notallowedtodothisstep']       = 'You do not have permission to make changes to this step at present';
$string['notanactivity']                = 'The command {$a} may only be used with an activity';
$string['notaworkflow']                 = 'This is not a valid workflow file';
$string['notcontrollablegradeitem']     = 'The command {$a} may only be used with an activity where the grade item visibility is NOT controlled by the activity';
$string['notcurrentlyinuse']            = 'It is currently not in use.';
$string['notgradesupported']            = 'The command {$a} may only be used with an activity which spports grade';
$string['notificationdate']             = 'Notification date';
$string['notuniquestep']                = 'Step {$a} is not unique';
$string['notutfencoding']               = 'This file is not UTF-8 encoded';
$string['noworkflow']                   = 'There is currently no workflow assigned for this page';
$string['noworkflows']                  = 'There are currently no available workflows';

$string['obsoleteworkflow']             = 'Obsoleted';
$string['onactivescript']               = 'On step activation';
$string['oncompletescript']             = 'On step completion';
$string['onextranotifyscript']          = 'Notify while step is active';
$string['overview']                     = 'Overview';
$string['overviewtitle']                = 'Overview of {$a->workflowname} workflow on {$a->contexttitle}';

$string['percentcomplete']              = '{$a}% complete';

// GDPR.
$string['privacy:metadata:block_workflow_state_changes'] = 'Changes to states.';
$string['privacy:metadata:block_workflow_state_changes:userid'] = 'The user who caused the state change.';
$string['privacy:metadata:block_workflow_state_changes:newstate'] = 'The state that this step changed to.';

$string['privacy:metadata:block_workflow_todo_done'] = 'Changes to each step in the workflow.';
$string['privacy:metadata:block_workflow_todo_done:userid'] = 'The user who caused the state change.';
$string['privacy:metadata:block_workflow_todo_done:steptodoid'] = 'The todo step that user changed to.';
$string['privacy:metadata'] = 'The Calendar block only displays existing calendar data.';

$string['privacy_you'] = 'You';
$string['privacy_somebodyelse'] = 'Somebody else';
// End GDPR.

$string['quizopendate']                 = 'the quiz open date';
$string['quizclosedate']                = 'the quiz close date';

$string['remove']                       = 'Remove';
$string['removerolefromstep']           = 'Remove role from step';
$string['removestep']                   = 'Remove step';
$string['removetask']                   = 'Remove task';
$string['removeworkflow']               = 'Remove workflow';
$string['removeworkflowcheck']          = 'Are you sure that you wish to remove the workflow \'{$a->workflowname}\' from {$a->contexttitle}? This action removes all associated data, and cannot be reversed!';
$string['removeworkflowfromcontext']    = 'Remove workflow \'{$a->workflowname}\' from {$a->contexttitle}?';
$string['roles']                        = 'Roles';

$string['shortname']                    = 'Shortname';
$string['shortnameinuse']               = 'The shortname specified is already in use. Shortnames must be unique';
$string['shortnametaken']               = 'This short name is already in use by another workflow ({$a})';
$string['shortnametakenemail']          = 'This shortname is already in use by another email template ({$a})';
$string['shownamesx']                   = 'Show names ({$a})';
$string['showpeoplecandotask']          = 'People who can do this task';
$string['showtask']                     = 'Enable task';
$string['state']                        = 'State';
$string['state_aborted']                = 'Aborted';
$string['state_active']                 = 'Active';
$string['state_completed']              = 'Complete';
$string['state_history']                = 'History';
$string['state_history_aborted']        = 'Aborted';
$string['state_history_active']         = 'Activated';
$string['state_history_completed']      = 'Completed';
$string['state_history_detail']         = '{$a->newstate} by {$a->user} at {$a->time}.';
$string['state_notstarted']             = 'Not started';
$string['status']                       = 'Current status';
$string['step']                         = 'Step';
$string['stepactivation']               = 'Step activation';
$string['stepactivation_help']          = 'Step activation';
$string['stepactivation_link']          = 'block/workflow';
$string['stepcompletion']               = 'Step completion';
$string['stepcompletion_help']          = 'Step completion';
$string['stepcompletion_link']          = 'block/workflow';
$string['stepextranotify']              = 'Step extra notification';
$string['stepextranotify_help']         = 'Set up email notification to be sent automatically to the chosen recipients on selected notification date.';
$string['stepextranotify_link']         = 'block/workflow';
$string['stepfinishconfirmation']       = 'The step was successfully finished. You have completed all of the required work at this stage';
$string['stepinstructions']             = 'Instructions';
$string['stepname']                     = 'Step name';
$string['stepno']                       = 'Step No.';
$string['stepnotexist']                 = 'Step to go at the end does not exist in the imported data: {$a}';
$string['steps']                        = 'Steps';
$string['stepsettings']                 = 'Step settings';

$string['task']                         = 'Task';
$string['taskcomplete']                 = 'Task complete';
$string['tasknotspecified']             = 'No task was specified';
$string['thisworkflowappliesto']        = 'This workflow applies to';
$string['tobecompletedby']              = 'To be completed by';
$string['todocannotchangestepid']       = 'It is not possible to change the stepid for an existing todo task';
$string['tododone']                     = 'Marked {$a} as complete';
$string['todolisttitle']                = 'Tasks for completion';
$string['todotask']                     = 'Task';
$string['todotitle']                    = 'Items to complete for this step';
$string['todoundone']                   = 'Marked {$a} as incomplete';

$string['updatecomment']                = 'Update comment';

$string['vieweditemail']                = 'View/Edit email';
$string['vieweditworkflow']             = 'View/Edit workflow';

$string['workflow:dostep']              = 'Permission to perform a step';
$string['workflow:editdefinitions']     = 'Permission to edit workflow details';
$string['workflow:manage']              = 'Permission to manage workflows';
$string['workflow:view']                = 'Permission to view workflow information';
$string['workflowactive']               = 'This workflow is currently enabled (<a href="{$a}" title="Disable this workflow">disable it</a>). ';
$string['workflowalreadyset']           = 'A workflow has already been set for this step. Steps cannot be reassigned to a different workflow';
$string['workflowalreadyassigned']      = 'A workflow is already assigned to this context. Only one workflow may be assigned to any one context at a time.';
$string['workflowimport']               = 'Workflow importing';
$string['workflowinformation']          = 'Workflow information';
$string['workflowlist']                 = 'Workflows';
$string['workflownotassigned']          = 'The \'{$a->workflowname}\' workflow is not assigned to the specified context';
$string['workflownotassignedtocontext'] = 'The \'{$a->workflowname}\' workflow is not assigned to {$a->contexttitle}';
$string['workflowobsolete']             = 'This workflow is currently marked as disabled (<a href="{$a}" title="Re-enable this workflow">enable it</a>). ';
$string['workflowoverview']             = 'Workflow overview';
$string['workflowsettings']             = 'Workflow settings';
$string['workflowstatus']               = 'Workflow status';
$string['workflowsteps']                = 'Workflow steps';
$string['workflowusage']                = 'Workflow usage';

$string['xmlloadfailed']                = 'Failed loading XML with following problems:';

$string['youandanyother']               = 'You, or any other ';
$string['youor']                        = ', or ';
