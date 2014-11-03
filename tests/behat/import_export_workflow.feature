@ou @ouvle @block @block_workflow
Feature: Workflow block - import and export workflows
  In order to reuse a workflow from another Moodle site
  as a manager
  I need to be able to import and export workflows.

  @javascript @_file_upload
  Scenario: Add a workflow to a course and step through it.
    When I log in as "admin"
    And I navigate to "Workflows" node in "Site administration > Plugins > Blocks"
    And I follow "Import workflow"
    And I upload "blocks/workflow/tests/fixtures/testworkflow.workflow.xml" file to "File" filemanager
    And I press "Import workflow"
    Then I should see "Importing was sucessful. You will be redirected to workflow editing page shortly."
    When I follow "Continue"

    Then I should see "testworkflow" in the "Shortname" "table_row"
    And I should see "Test course workflow" in the "Name" "table_row"
    And I should see "This workflow manages course creation." in the "Description" "table_row"
    And I should see "Course" in the "This workflow applies to" "table_row"
    And I should see "This workflow is currently enabled (disable it). It is currently not in use." in the "Workflow status" "table_row"
    And I should see "Workflow steps"
    And I should see "After step 2, this workflow will end."
    And I should see "Manager" in the "Configure basic site" "table_row"
    And I should see "Teacher" in the "Prepare your web site" "table_row"

    # Import the same workflow again, and verify the name is made unique.
    And I follow "Workflows"
    And I follow "Import workflow"
    And I upload "blocks/workflow/tests/fixtures/testworkflow.workflow.xml" file to "File" filemanager
    And I press "Import workflow"
    Then I should see "Email template 'taskemail' which was attempted to import already exists. Existing template is preserved."
    Then I should see "Importing was sucessful. You will be redirected to workflow editing page shortly."
    When I follow "Continue"

    Then I should see "testworkflow1" in the "Shortname" "table_row"
    And I should see "Test course workflow1" in the "Name" "table_row"
    And I should see "This workflow manages course creation." in the "Description" "table_row"
    And I should see "Course" in the "This workflow applies to" "table_row"
    And I should see "This workflow is currently enabled (disable it). It is currently not in use." in the "Workflow status" "table_row"
    And I should see "Workflow steps"
    And I should see "After step 2, this workflow will end."
    And I should see "Manager" in the "Configure basic site" "table_row"
    And I should see "Teacher" in the "Prepare your web site" "table_row"

    # Test deleting the second workflow.
    When I follow "Delete"
    Then I should see "Are you absolutely sure that you want to completely delete the workflow Test course workflow1?"
    When I press "Cancel"
    And I click on "Remove workflow" "link" in the "testworkflow1" "table_row"
    And I press "Continue"
    Then I should not see "testworkflow1"

    # Export the workflow.
    # TODO update the next step once MDL-47497 is available in OUVLE.
    Then downloading SC link "Export workflow" results in "1726" bytes
