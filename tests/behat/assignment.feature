@plugin @plagiarism @plagiarism_turnitinsim @plagiarism_turnitinsim_assignment @plagiarism_turnitinsim_smoke
Feature: Plagiarism plugin works with a Moodle Assignment
  In order to allow students to send assignment submissions to Turnitin
  As a user
  I need to create an assignment with the plugin enabled and the assignment to launch successfully.

  Background: Set up the plugin
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 1         |
    And the following users will be created if they do not already exist:
      | username    | firstname   | lastname    | email                                   |
      | instructor1 | instructor1 | instructor1 | instructor1_$account_tiibehattesting@example.com |
      | student1    | student1    | student1    | student1_$account_tiibehattesting@example.com    |
    And the following "course enrolments" exist:
      | user        | course | role    |
      | student1    | C1     | student |
      | instructor1 | C1     | editingteacher |
    And I log in as "admin"
    And I navigate to "Advanced features" in site administration
    And I set the field "Enable plagiarism plugins" to "1"
    And I press "Save changes"
    And I navigate to "Plugins > Plagiarism > Turnitin Integrity plugin" in site administration
    And I set the following fields to these values:
      | Enable Turnitin Integrity for Assign | 1 |
    And I configure Turnitin Integrity credentials
    And I press "Save changes"
    And I navigate to "Plugins > Plugins overview" in site administration
    Then the following should exist in the "plugins-control-panel" table:
      | Plugin name         |
      | plagiarism_turnitin |
    # Create Assignment.
    And I add an "assign" activity to course "Course 1" section "1" and I fill the form with:
      | Assignment name                   | Test assignment name |
      | turnitinenabled               | 1                    |
      | accessoptions[accessstudents] | 1                    |
      | Group mode                    | No group             |
    Then I should see "Test assignment name"

  @javascript @_file_upload @_switch_window
  Scenario: A student can accept the EULA and submit to Turnitin, an Originality Report is retrieved and the Cloud Viewer can be launched.
    Given I log out
    # Student submits.
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    And I press "Add submission"
    And I click on "#turnitinsim_eula_accept" "css_element"
    And I upload "plagiarism/turnitinsim/tests/fixtures/testfile.txt" file to "File submissions" filemanager
    And I press "Save changes"
    Then I should see "Submitted for grading"
    And I should see "Queued"
    And I log out
    # Admin runs scheduled task to send submission to Turnitin.
    And I log in as "admin"
    And I run the scheduled task "plagiarism_turnitinsim\task\send_submissions"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    When I navigate to "Submissions" in current page administration
    Then "student1 student1" row "File submissions" column of "generaltable" table should contain "Pending"
    # Student can see post has been sent to Turnitin.
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    Then I should see "Pending"
    And I log out
    # Admin runs scheduled task to request an originality report.
    And I log in as "admin"
    And I wait "10" seconds
    And I run the scheduled task "plagiarism_turnitinsim\task\get_reports"
    # Admin runs scheduled task to request originality report score.
    And I wait "20" seconds
    And I run the scheduled task "plagiarism_turnitinsim\task\get_reports"
    And I wait "30" seconds
    And I run the scheduled task "plagiarism_turnitinsim\task\get_reports"
    # Instructor should be able to view Cloud Viewer and be presented with the EULA.
    And I log out
    And I log in as "instructor1"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    When I navigate to "Submissions" in current page administration
    Then "student1 student1" row "File submissions" column of "generaltable" table should contain "%"
    And I wait until "div.turnitinsim_or_score" "css_element" exists
    And I click on "div.turnitinsim_or_score" "css_element"
    And I switch to viewer window
    And I click on "Accept" "button"
    And I wait "10" seconds
    Then I should see "testfile.txt"
    And I should see "student1 student1"
    # Open the Cloud Viewer as student1.
    And I am on "Course 1" course homepage
    And I wait "20" seconds
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    Then I should see "%"
    And I click on "div.turnitinsim_or_score" "css_element"
    And I switch to viewer window
