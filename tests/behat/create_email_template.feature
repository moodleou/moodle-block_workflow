@ou @ou_vle @block @block_workflow
Feature: Workflow block - email templates
  In order to notify users about workflow changes
  as an admin
  I need to create, edit and delete email templates.

  @javascript
  Scenario: Create edit then delete an email template
    When I log in as "admin"
    And I navigate to "Workflows" node in "Site administration > Plugins > Blocks"
    Then I should see "Manage email templates"

    When I follow "Add email template"
    And I set the following fields to these values:
      | Shortname | taskemail                                                        |
      | Subject   | Please do this workflow task                                     |
      | Message   | Please go and do task %%stepname%% of workflow %%workflowname%%. |
    And I press "Save changes"

    Then I should see "Manage email templates"
    And I should see "taskemail" in the "Please do this workflow task" "table_row"

    # It should be impossible to add another template with the same name.
    When I follow "Add email template"
    And I set the following fields to these values:
      | Shortname | taskemail                                      |
      | Subject   | Task %%stepname%% was completed by %%currentusername%% |
      | Message   | They left comment %%comment%%.                         |
    And I press "Save changes"

    Then I should see "Create new email template"
    And I should see "This shortname is already in use by another email template (taskemail)"
    When I set the field "Shortname" to "taskcompleteemail"
    And I press "Save changes"

    Then I should see "Manage email templates"
    And I should see "taskemail" in the "Please do this workflow task" "table_row"
    And I should see "taskcompleteemail" in the "Task %%stepname%% was completed by %%currentusername%%" "table_row"

    # Also, you cannot edit an existing email to make the names clash.
    When I click on "View/Edit email" "link" in the "Please do this workflow task" "table_row"
    And I set the field "Shortname" to "taskcompleteemail"
    And I press "Save changes"

    Then I should see "Edit email template 'taskemail'"
    And I should see "This shortname is already in use by another email template (taskcompleteemail)"
    When I press "Cancel"

    Then I should see "Manage email templates"
    And I should see "taskemail" in the "Please do this workflow task" "table_row"
    And I should see "taskcompleteemail" in the "Task %%stepname%% was completed by %%currentusername%%" "table_row"

    # Delete one of the emails.
    When I click on "Delete email" "link" in the "Please do this workflow task" "table_row"
    Then I should see "Are you absolutely sure that you want to completely delete the email template 'taskemail'?"
    When I press "Cancel"

    Then I should see "Manage email templates"
    And I should see "taskemail" in the "Please do this workflow task" "table_row"
    And I should see "taskcompleteemail" in the "Task %%stepname%% was completed by %%currentusername%%" "table_row"

    When I click on "Delete email" "link" in the "Please do this workflow task" "table_row"
    Then I should see "Are you absolutely sure that you want to completely delete the email template 'taskemail'?"
    When I press "Continue"

    Then I should see "Manage email templates"
    And I should see "taskcompleteemail" in the "Task %%stepname%% was completed by %%currentusername%%" "table_row"
    And I should not see "taskemail"
    And I should not see "Please do this workflow task"
