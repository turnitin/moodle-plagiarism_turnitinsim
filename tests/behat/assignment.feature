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
    # Check that features enabled are displayed.
    Then I should see "TurnitinSim features"
    And I should see "Repositories checked against"
    # Create Assignment.
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name               | Test assignment name |
      | turnitinenabled               | 1                    |
      | accessoptions[accessstudents] | 1                    |
      | Group mode                    | No group             |
    And I follow "Test assignment name"
    Then I should see "Grading summary"

  @javascript
  Scenario: A student can accept the EULA and submit to Turnitin, an Originality Report is retrieved and the Cloud Viewer can be launched.
    Given I log out
    # Student submits.
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    And I press "Add submission"
    And I click on "I accept the Turnitin EULA" "button"
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
    # Instructor should be able to view Cloud Viewer and be presented with the EULA.
    And I log out
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
    # Open the Cloud Viewer as student1.
    And I switch to the main window
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    Then I should see "%"
    And I click on ".or_score" "css_element"
    And I switch to "cvWindow" window
    Then I should see "testfile.txt"
    And I should see "student1 student1"

  @javascript
  Scenario: A student can decline the EULA, their submission still processes in Moodle but is not sent to Turnitin
    Given I log out
    # Student submits.
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    And I press "Add submission"
    And I click on "I decline the Turnitin EULA" "button"
    Then I should see "Your submissions will not be sent to Turnitin as you have not accepted the Turnitin End User Licence Agreement."
    And I upload "plagiarism/turnitinsim/tests/fixtures/testfile.txt" file to "File submissions" filemanager
    And I press "Save changes"
    Then I should see "Submitted for grading"
    And I should see "Awaiting EULA"
    And I log out
    # Admin runs scheduled task to send submissions to Turnitin.
    And I log in as "admin"
    And I run the scheduled task "plagiarism_turnitinsim\task\send_submissions"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    When I navigate to "View all submissions" in current page administration
    Then "student2 student2" row "File submissions" column of "generaltable" table should contain "Awaiting EULA"

  @javascript
  Scenario: An instructor can submit on behalf of multiple students, they are presented with the opportunity to accept the EULA and
  the submissions are then sent to Turnitin
    Given I am on "Course 1" course homepage
    And I set the following system permissions of "Teacher" role:
      | capability | permission |
      | mod/assign:editothersubmission | Allow |
    And I log out
    # Instructor submits for student1.
    And I log in as "instructor1"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    And I navigate to "View all submissions" in current page administration
    And I click on "Edit" "link" in the "student1 student1" "table_row"
    And I choose "Edit submission" in the open action menu
    And I click on "I accept the Turnitin EULA" "button"
    And I upload "plagiarism/turnitinsim/tests/fixtures/testfile.txt" file to "File submissions" filemanager
    And I press "Save changes"
    When I navigate to "View all submissions" in current page administration
    # Instructor submits for student2.
    And I click on "Edit" "link" in the "student2 student2" "table_row"
    And I choose "Edit submission" in the open action menu
    And I upload "plagiarism/turnitinsim/tests/fixtures/testfile.txt" file to "File submissions" filemanager
    And I press "Save changes"
    When I navigate to "View all submissions" in current page administration
    Then "student1 student1" row "File submissions" column of "generaltable" table should contain "Queued"
    And "student2 student2" row "File submissions" column of "generaltable" table should contain "Queued"
    And I log out
    # Admin runs scheduled task to send submission to Turnitin.
    And I log in as "admin"
    And I run the scheduled task "plagiarism_turnitinsim\task\send_submissions"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    And I navigate to "View all submissions" in current page administration
    Then "student1 student1" row "File submissions" column of "generaltable" table should contain "Pending"
    Then "student2 student2" row "File submissions" column of "generaltable" table should contain "Pending"
