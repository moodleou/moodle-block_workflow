@ou @ou_vle @block @block_workflow @javascript
Feature: Workflow block visibility check for quiz activities
  As a staff member managing quiz workflows
  I want to see the visibility status of quiz dates and availability
  So that I can spot when exams are misconfigured

  Background:
    Given the following "courses" exist:
      | fullname    | shortname | format |
      | Test Course | TC1       | topics |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | TC1    | editingteacher |
    And the following "activities" exist:
      | activity | course | name      | idnumber | timeopen | timeclose |
      | quiz     | TC1    | Test Quiz | quiz1    | 0        | 0         |
    And the following "block_workflow > workflows" exist:
      | shortname | name          | appliesto |
      | quizwf    | Quiz Workflow | quiz      |
    And the following "block_workflow > workflow steps" exist:
      | workflow | name   | instructions |
      | quizwf   | Step 1 |              |
    And quiz "Test Quiz" has workflow "quizwf" applied
    And I log in as "teacher1"

  Scenario: Visibility check section shows warnings when quiz dates are not set
    When I am on the "Test Quiz" "quiz activity" page
    Then I should see "Visibility check" in the ".block-workflow-visibility-check" "css_element"
    And I should see "Open date:" in the ".block-workflow-visibility-check" "css_element"
    And I should see "Not set" in the ".block-workflow-visibility-check" "css_element"
    And I should see "Close date:" in the ".block-workflow-visibility-check" "css_element"
    And I should see "[Is it OK?]" in the ".block-workflow-visibility-check" "css_element"
    And I should see "Quiz availability" in the ".block-workflow-visibility-check" "css_element"
