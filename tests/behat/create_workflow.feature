@ou @ouvle @block @block_workflow
Feature: Workflow block - create and edit workflows
  In order to manage course or activity production
  as an admin
  I need to create, edit and delete workflows.

  Background:
    Given I log in as "admin"
    And I navigate to "Workflows" node in "Site administration > Plugins > Blocks"
    And I follow "Add email template"
    And I set the following fields to these values:
      | Shortname | taskemail                                                        |
      | Subject   | Please do this workflow task                                     |
      | Message   | Please go and do task %%stepname%% of workflow %%workflowname%%. |
    And I press "Save changes"

  @javascript
  Scenario: Create and edit workflows
    When I follow "Create new workflow"
    And I set the following fields to these values:
      | Shortname      | testworkflow                           |
      | Name           | Test course workflow                   |
      | Description    | This workflow manages course creation. |
      | Current status | Enabled                                |
    And I press "Save changes"

    Then I should see "testworkflow" in the "Shortname" "table_row"
    And I should see "Test course workflow" in the "Name" "table_row"
    And I should see "This workflow manages course creation." in the "Description" "table_row"
    And I should see "Course" in the "This workflow applies to" "table_row"
    And I should see "This workflow is currently enabled (disable it). It is currently not in use." in the "Workflow status" "table_row"
    And I should see "Workflow steps"
    And I should see "After step 1, this workflow will end."

    # Edit the first step.
    When I click on "Edit step" "link" in the "First step" "table_row"
    Then I should see "Editing step 'First step'"
    When I set the following fields to these values:
      | Name         | Configure basic site                                                 |
      | Instructions | Set up the basic site settings, such as course format and enrolment. |
    And I press "Save changes"
    Then I should see "Set up the basic site settings, such as course format and enrolment." in the "Configure basic site" "table_row"

    # Change the assigned roles.
    When I click on "Edit step" "link" in the "Configure basic site" "table_row"
    And I click on "Add role to step" "link" in the "Manager" "table_row"
    And I click on "Add role to step" "link" in the "Student" "table_row"
    And I click on "Remove role from step" "link" in the "Student" "table_row"
    Then "Add role to step" "link" should exist in the "Student" "table_row"

    When I press "Save changes"
    Then I should see "Manager" in the "Configure basic site" "table_row"

    # Create and edit tasks.
    When I click on "Edit step" "link" in the "Configure basic site" "table_row"
    And I follow "Add task"
    And I set the field "Task" to "Set the course format"
    And I press "Save changes"
    And I follow "Add task"
    And I set the field "Task" to "Setup enrolment plugins"
    And I press "Save changes"
    And I follow "Add task"
    And I set the field "Task" to "Setup flters"
    And I press "Save changes"
    And I click on "Edit task" "link" in the "Setup flters" "table_row"
    And I set the field "Task" to "Setup filters"
    And I press "Save changes"
    And I click on "Edit task" "link" in the "Setup filters" "table_row"
    And I set the field "Task" to "Setup flters"
    And I press "Cancel"
    And I click on "Disable task" "link" in the "Setup filters" "table_row"
    And I click on "Enable task" "link" in the "Setup filters" "table_row"
    And I click on "Disable task" "link" in the "Setup filters" "table_row"
    And I follow "Add task"
    And I set the field "Task" to "Something stupid"
    And I press "Save changes"
    And I click on "Remove task" "link" in the "Something stupid" "table_row"
    Then I should see "Are you sure you wish to delete the task 'Something stupid' from step 'Configure basic site'?"
    When I press "Cancel"
    And I click on "Remove task" "link" in the "Something stupid" "table_row"
    When I press "Confirm"
    Then I should not see "Something stupid"

    # Explore start script (eventually hide course)
    When I set the field "On step activation" to "setcoursevisibility hidden"
    And I press "Save changes"

    # Add a second step
    And I click on "Add an additional step to this workflow" "link" in the "After step 1, this workflow will end." "table_row"
    And I set the following fields to these values:
      | Name               | Prepare your web site                             |
      | Instructions       | Please add learning content to your new web site. |
      | autofinish         | the course start date                             |
      | On step activation | email taskemail to teacher                        |
      | On step completion | setcoursevisibility visible                       |
      | autofinishoffset   | 1 day before                                      |
    And I press "Save changes"
    And I click on "Edit step" "link" in the "Prepare your web site" "table_row"
    And I click on "Add role to step" "link" in the "Teacher" "table_row"
        And I follow "Add task"
    And I set the field "Task" to "Add resources"
    And I press "Save changes"
    And I follow "Add task"
    And I set the field "Task" to "Add actvities"
    And I press "Save changes"
    And I follow "Add task"
    And I set the field "Task" to "Set up block"
    And I press "Save changes"
    And I follow "Add task"
    And I set the field "Task" to "Set up block"
    And I press "Save changes"
    And I follow "Add task"
    And I set the field "Task" to "Configure gradebook"
    And I press "Save changes"
    And I press "Save changes"
    Then I should see "Please add learning content to your new web site." in the "Prepare your web site" "table_row"
    And I should see "Teacher" in the "Prepare your web site" "table_row"
    And I should see "1 day before the course start date" in the "Prepare your web site" "table_row"

    # Add a step that will be used to test deleting.
    And I click on "Add a step after this point" "link" in the "Prepare your web site" "table_row"
    And I set the following fields to these values:
      | Name               | Throw-away step   |
      | Instructions       | Just for testing. |
    And I press "Save changes"

    And I click on "Edit" "link" in the "Edit, Clone, Export, Delete" "table_row"
    And I set the field "At the end of step 3" to "go back to step 3 (Throw-away step)"
    And I press "Save changes"
    Then I should see "After step 3, go back to step number 3."

    # Test the move step up and down links.
    Then "Move down" "link" should not exist in the "Throw-away step" "table_row"
    When I click on "Move up" "link" in the "Throw-away step" "table_row"
    Then "Move down" "link" should exist in the "Throw-away step" "table_row"
    When I click on "Move up" "link" in the "Throw-away step" "table_row"
    Then "Move up" "link" should not exist in the "Throw-away step" "table_row"

    # Delete the step created for that purpose.
    When I click on "Remove step" "link" in the "Throw-away step" "table_row"
    And I press "Cancel"
    And I click on "Remove step" "link" in the "Throw-away step" "table_row"
    And I press "Continue"
    Then I should not see "Throw-away step"
    And I should see "After step 2, go back to step number 2."

    When I click on "Edit" "link" in the "Edit, Clone, Export, Delete" "table_row"
    And I set the field "At the end of step 2" to "finish the workflow"
    And I press "Save changes"
    Then I should see "After step 2, this workflow will end."
