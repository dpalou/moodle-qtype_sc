@qtype @qtype_sc @qtype_sc_step_22
Feature: Step 22

  Background:
    Given the following "users" exist:
      | username | firstname   | lastname   | email               |
      | teacher1 | T1Firstname | T1Lasname  | teacher1@moodle.com |
      | student1 | S1Firstname | S1Lastname | student1@moodle.com |
      | student2 | S2Firstname | S2Lastname | student2@moodle.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | c1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | c1     | editingteacher |
      | student1 | c1     | student        |   
      | student2 | c1     | student        |   
    And the following "activities" exist:
      | activity | name   | intro              | course | idnumber |
      | quiz     | Quiz 1 | Quiz 1 for testing | c1     | quiz1    |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | c1        | Default for c1 |
    And the following "questions" exist:
      | questioncategory | qtype | name           | template       |
      | Default for c1   | sc    | SC-Question-3  | question_three |
    And quiz "Quiz 1" contains the following questions:
      | question       | page |
      | SC-Question-3 | 1    |


  @javascript @_switch_window
  Scenario: TESTCASE 22.
  # Regrade: After a test was submitted by a student,
  # change the grading options (e.g from SC1/0 to subpoints).
  # Then go to grades and click on "regrade selected attempts"
  # The regrading should be successful.
  # Check that already manually graded questions won't be affected


  # Set Scoring Method to subpoints
    And I output "[SC - TESTCASE 22 - begin]"
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Quiz 1"
    And I click on "Actions menu" "link"
    And I click on "Edit quiz" "link"
    And I click on "Edit question SC-Question-3" "link" in the "SC-Question-3" "list_item"
    And I click on "id_scoringmethod_subpoints" "radio"
    And I press "id_updatebutton"
    And I click on css ".usermenu"
    And I click on "Log out" "link"

  # Solving quiz as student1: 50% correct options
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Quiz 1"
    And I press "Attempt quiz now"
    And I click on css "tr:contains('Option Text 3') label[title='Click to cross out as incorrect option.']"
    And I click on css "tr:contains('Option Text 4') label[title='Click to cross out as incorrect option.']"
    And I click on css "tr:contains('Option Text 5') label[title='Click to cross out as incorrect option.']"
    And I press "Finish attempt ..."
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Confirmation" "dialogue"
    And I click on css ".usermenu"
    And I click on "Log out" "link"

  # Solving quiz as student2: 50% correct options
    When I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Quiz 1"
    And I press "Attempt quiz now"
    And I click on css "tr:contains('Option Text 3') label[title='Click to cross out as incorrect option.']"
    And I click on css "tr:contains('Option Text 4') label[title='Click to cross out as incorrect option.']"
    And I click on css "tr:contains('Option Text 5') label[title='Click to cross out as incorrect option.']"
    And I press "Finish attempt ..."
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Confirmation" "dialogue"
    And I click on css ".usermenu"
    And I click on "Log out" "link"

  # Login as teacher1 and grade student1 manually
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Quiz 1"
    And I click on "Actions menu" "link"
    And I click on "Responses" "link"
    And I click on css "tr:contains('student1@moodle.com') a:contains('Review attempt')"
    And I click on "Make comment or override mark" "link"
    And I switch to "commentquestion" window
    And I set the field "Mark" to "0.86"
    And I press "Save" and switch to main window

  # Set Scoring Method to SC1/0
    And I click on "Actions menu" "link"
    And I click on "Edit quiz" "link"
    And I click on "Edit question SC-Question-3" "link" in the "SC-Question-3" "list_item"
    And I click on "id_scoringmethod_sconezero" "radio"
    And I press "id_submitbutton"

  # Regrade
    And I follow "Quiz 1"
    And I click on "Actions menu" "link"
    And I click on "Results" "link"
    And I click on "Select all" "link"
    And I press "Regrade selected attempts"
    And I press "Continue"

  # Check if grades are correct
    Then element with css ".gradedattempt:contains('student1@moodle.com'):contains('86.00')" should exist
    And element with css ".gradedattempt:contains('student2@moodle.com'):contains('0.00')" should exist
    And I output "[SC - TESTCASE 22 - end]"

