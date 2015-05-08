@ou @ouvle @block @block_workflow
Feature: Workflow block - clone a workflow
  In order to reuse my workflows
  As a manager
  I need to ble able to clone them

  @javascript
  Scenario: Duplicate a workflow
    When I log in as "admin"
    And I navigate to "Workflows" node in "Site administration > Plugins > Blocks"
    And I follow "Import workflow"
    And I upload "blocks/workflow/tests/fixtures/testworkflow.workflow.xml" file to "File" filemanager
    And I press "Import workflow"
    And I follow "Continue"
    And I follow "Clone"
    And I press "id_submitbutton"
    Then I should see "testworkflow_cloned" in the "Shortname" "table_row"
    And I should see "Test course workflow (cloned)" in the "Name" "table_row"
    And I should see "This workflow manages course creation." in the "Description" "table_row"
    And I should see "Course" in the "This workflow applies to" "table_row"
    And I should see "This workflow is currently enabled (disable it). It is currently not in use." in the "Workflow status" "table_row"
    And I should see "Workflow steps"
    And I should see "After step 2, this workflow will end."
    And I should see "Manager" in the "Configure basic site" "table_row"
    And I should see "Teacher" in the "Prepare your web site" "table_row"
