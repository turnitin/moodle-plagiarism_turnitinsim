@plugin @plagiarism @plagiarism_turnitinsim @plagiarism_turnitinsim_assignment @plagiarism_turnitinsim_assignment_drafts
Feature: Plagiarism plugin works with a Moodle Assignment
  In order to allow students to send assignment submissions to Turnitin
  As a user
  I need to create an assignment with the plugin enabled and the assignment to launch successfully.

  Background: Set up the users, course and assignment with plugin enabled
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 1         |
    And I create a unique moodle user with username "student1"
    And I create a unique moodle user with username "student2"
    And I create a unique moodle user with username "instructor1"
    And the following "course enrolments" exist:
      | user        | course | role           |
      | student1    | C1     | student        |
      | student2    | C1     | student        |
      | instructor1 | C1     | editingteacher |
    # Enable and configure plugin.
    When I log in as "admin"
    And I navigate to "Advanced features" in site administration
    And I set the field "Enable plagiarism plugins" to "1"
    And I press "Save changes"
    And I navigate to "Plugins > Plagiarism > TurnitinSim plagiarism plugin" in site administration
    And I configure TurnitinSim credentials
    And I set the following fields to these values:
      | Enable TurnitinSim for Assign | 1 |
    And I press "Save changes"
    # Create Assignment.
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name               | Test assignment name |
      | submissiondrafts              | 1                    |
      | turnitinenabled               | 1                    |
      | accessoptions[accessstudents] | 1                    |
      | queuedrafts                   | 1                    |
    And I follow "Test assignment name"
    Then I should see "Grading summary"

  @javascript
  Scenario: A student can submit a draft and it is sent to Turnitin.
    Given I log out
    # Student submits.
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    And I press "Add submission"
    And I click on "I accept the Turnitin EULA" "button"
    And I upload "plagiarism/turnitinsim/tests/fixtures/testfile.txt" file to "File submissions" filemanager
    And I press "Save changes"
    Then I should see "Not graded"
    And I should see "Queued"
    And I log out
    # Admin runs scheduled task to send submission to Turnitin.
    And I log in as "admin"
    And I run the scheduled task "plagiarism_turnitinsim\task\send_submissions"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    When I navigate to "View all submissions" in current page administration
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
    And I log out
    # Open the Cloud Viewer as instructor1.
    And I log in as "instructor1"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    When I navigate to "View all submissions" in current page administration
    Then "student1 student1" row "File submissions" column of "generaltable" table should contain "%"
    And I click on ".or_score" "css_element"
    And I switch to "cvWindow" window
    And I click on "Accept" "button"
    Then I should see "testfile.txt"
    And I should see "student1 student1"

  @javascript
  Scenario: A student can submit a draft and it is not sent to Turnitin until it is submitted.
    Given I navigate to "Edit settings" in current page administration
    And I set the following fields to these values:
      | queuedrafts | 0 |
    And I press "Save and display"
    Given I log out
    # Student submits.
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    And I press "Add submission"
    And I click on "I accept the Turnitin EULA" "button"
    And I upload "plagiarism/turnitinsim/tests/fixtures/testfile.txt" file to "File submissions" filemanager
    And I press "Save changes"
    Then I should see "Not graded"
    And I should see "Not Sent"
    And I log out
    # Admin runs scheduled task to send submission to Turnitin.
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    When I navigate to "View all submissions" in current page administration
    Then "student2 student2" row "File submissions" column of "generaltable" table should not contain "Queued"
    And I run the scheduled task "plagiarism_turnitinsim\task\send_submissions"
    #Student finalises submission.
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    And I press "Submit assignment"
    And I press "Continue"
    Then I should see "Queued"
    # Admin runs scheduled task to send submission to Turnitin.
    And I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    When I navigate to "View all submissions" in current page administration
    Then "student2 student2" row "File submissions" column of "generaltable" table should contain "Queued"
    And I run the scheduled task "plagiarism_turnitinsim\task\send_submissions"
    # Admin runs scheduled task to request an originality report.
    And I wait "10" seconds
    And I run the scheduled task "plagiarism_turnitinsim\task\send_submissions"
    # Admin runs scheduled task to request originality report score.
    And I wait "20" seconds
    And I run the scheduled task "plagiarism_turnitinsim\task\send_submissions"
    And I log out
    # Open the Cloud Viewer as instructor1.
    And I log in as "instructor1"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    When I navigate to "View all submissions" in current page administration
    Then "student2 student2" row "File submissions" column of "generaltable" table should contain "%"
    And I click on ".or_score" "css_element"
    And I switch to "cvWindow" window
    And I click on "Accept" "button"
    Then I should see "testfile.txt"
    And I should see "student2 student2"