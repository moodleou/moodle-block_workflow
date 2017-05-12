@ou @ou_vle @block @block_workflow
Feature: Workflow block - follow a workflow
  In order to create courses in a bureaucratic organisation
  as a manager and a teacher
  I need to follow a workflow.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email               |
      | manager1 | M1        | Manager1 | manager1@moodle.com |
      | teacher1 | T1        | Teacher1 | teacher1@moodle.com |
      | student1 | S1        | Student1 | student1@moodle.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | manager1 | C1     | manager        |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |

  @javascript
  Scenario: Try adding a workflow when none are defined.
    When I log in as "manager1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add the "Workflows" block
    Then I should see "There is currently no workflow assigned for this page"

  @javascript
  Scenario: Add a workflow to a course and step through it.
    When I log in as "admin"
    And I navigate to "Workflows" node in "Site administration > Plugins > Blocks"
    And I follow "Import workflow"
    And I upload "blocks/workflow/tests/fixtures/testworkflow.workflow.xml" file to "File" filemanager
    And I press "Import workflow"
    And I navigate to "Manage editors" node in "Site administration > Plugins > Text editors"
    And I click on "disable" "link" in the "Atto HTML editor" "table_row"
    And I log out

    When I log in as "manager1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add the "Workflows" block
    And I set the field "workflow" to "Test course workflow"

    Then I should see "Test course workflow"
    And I should see "Currently active task"
    And I should see "Configure basic site"
    And I should see "To be completed by"
    And I should see "You, or any other Manager"
    And I should see "Instructions"
    And I should see "Set up the basic site settings, such as course format and enrolment."
    And I should see "Comments"
    And I should see "No comments have been made about this step yet"
    And I should see "Tasks for completion"

    # Check that the finish step script did the right thing.
    When I navigate to "Edit settings" node in "Course administration"
    Then the field "Visible" matches value "Hide"
    And I press "Cancel"

    When I press "Show names (1)"
    Then I should see "People who can do this task"
    And I should see "manager1@moodle.com" in the "M1 Manager1" "table_row"
    And I click on "Close" "button" in the "People who can do this task" "dialogue"

    When I follow "Set the course format"
    Then I should see "Set the course format" in the "ul.block_workflow_todolist li.completed" "css_element"
    When I follow "Set the course format"
    Then I should see "Set the course format" in the "ul.block_workflow_todolist li" "css_element"

    When I press "Edit comments"
    Then I should see "Update comment"
    # Cannot actually set a comment, becuase Behat does not support TinyMCE any more.
    And I click on "#id_submitbutton" "css_element"

    # Finish task
    When I press "Finish step"
    Then I should see "Finish step"
    And I should see "Test course workflow"
    When I click on "#id_submitbutton" "css_element"
    Then I should see "Prepare your web site"
    And I should see "Any Teacher"

    # Workflow overview
    When I press "Workflow overview"
    Then I should see "Complete" in the "Configure basic site" "table_row"
    And I should see "Active" in the "Prepare your web site" "table_row"
    And I should see "After step 2, this workflow will end."
    When I click on "Show names (1)" "button" in the "Prepare your web site" "table_row"
    Then I should see "People who can do this task (Step 2)"
    And I should see "teacher1@moodle.com" in the "T1 Teacher1" "table_row"
    And I click on "Close" "button" in the "People who can do this task (Step 2)" "dialogue"

    When I press "Jump to step"
    And I press "Confirm"
    Then I should see "Configure basic site"
    When I press "Workflow overview"
    And I press "Finish step"
    And I press "id_submitbutton"
    Then I should see "Prepare your web site"

    # Teacher actions
    When I log out
    And I log in as "teacher1"
    And I follow "Course 1"
    Then I should see "Test course workflow"
    And I should see "Prepare your web site"
    And I should see "You, or any other Teacher"

    # Finish task & course visiblility again.
    When I press "Finish step"
    And I click on "#id_submitbutton" "css_element"
    Then I should see "The workflow has been completed."
    And I should see "Workflow overview"
    And I reload the page
    And I should see "The workflow has been completed."
    And "Workflow overview" "button" in the "Workflow" "block" should be visible

    # Check that the finish step script did the right thing.
    When I navigate to "Edit settings" node in "Course administration"
    Then the field "Visible" matches value "Show"

    When I log out
    And I log in as "manager1"
    And I follow "Course 1"
    Then I should see "The workflow has been completed."
    And I click on "Workflow overview" "button" in the "Workflow" "block"
    And I should see "Complete" in the "Configure basic site" "table_row"
    And I should see "Complete" in the "Prepare your web site" "table_row"
