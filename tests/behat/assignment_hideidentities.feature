@plugin @plagiarism @plagiarism_turnitinsim @plagiarism_turnitinsim_assignment
Feature: Plagiarism plugin works with a Moodle Assignment
  In order to allow students to send assignment submissions to Turnitin
  As a user
  I need to create an assignment with the plugin enabled and the assignment to launch successfully.

  Background: Set up the users, course and assignment with plugin enabled
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 1         |
    And I create a unique moodle user with username "student1"
    And I create a unique moodle user with username "instructor1"
    And the following "course enrolments" exist:
      | user        | course | role           |
      | student1    | C1     | student        |
      | instructor1 | C1     | editingteacher |
    # Enable and configure plugin.
    When I log in as "admin"
    And I navigate to "Advanced features" in site administration
    And I set the field "Enable plagiarism plugins" to "1"
    And I press "Save changes"
    And I navigate to "Plugins > Plagiarism > Turnitin Integrity plugin" in site administration
    And I configure Turnitin Integrity credentials
    And I set the following fields to these values:
      | Enable Turnitin Integrity for Assign       | 1 |
      | Hide student's identity from Turnitin | 1 |
    And I press "Save changes"
    # Check that features enabled are displayed.
    Then I should see "Turnitin Integrity features"
    And I should see "Repositories checked against"
    # Create Assignment.
    And I am on "Course 1" course homepage with editing mode on
    And I add an "assign" activity to course "Course 1" section "1" and I fill the form with:
      | Assignment name               | Test assignment name |
      | turnitinenabled               | 1                    |
      | accessoptions[accessstudents] | 1                    |
      | Group mode                    | No group             |
    And I click on "div.activityname" "css_element"
    Then I should see "Grading summary"

  @javascript @_file_upload
  Scenario: A student can submit and if hide student's identity is set their name can not be seen in the Cloud Viewer.
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
    And I click on "div.activityname" "css_element"
    When I navigate to "Submissions" in current page administration
    Then "student1 student1" row "File submissions" column of "generaltable" table should contain "Pending"
    # Admin runs scheduled task to request an originality report.
    And I wait "10" seconds
    And I run the scheduled task "plagiarism_turnitinsim\task\get_reports"
    # Admin runs scheduled task to request originality report score.
    And I wait "20" seconds
    And I run the scheduled task "plagiarism_turnitinsim\task\get_reports"
    And I wait "30" seconds
    And I run the scheduled task "plagiarism_turnitinsim\task\get_reports"
    And I log out
    # Instructor should be able to view Cloud Viewer and be presented with the EULA.
    And I log in as "instructor1"
    And I am on "Course 1" course homepage
    And I click on "div.activityname" "css_element"
    When I navigate to "Submissions" in current page administration
    Then "student1 student1" row "File submissions" column of "generaltable" table should contain "%"
    And I click on ".turnitinsim_or_score" "css_element"
    And I switch to viewer window
    And I click on "Accept" "button"
    And I wait "10" seconds
    Then I should see "testfile.txt"
    And I should not see "student1 student1"
