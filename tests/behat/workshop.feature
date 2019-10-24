@plugin @plagiarism @plagiarism_turnitinsim @plagiarism_turnitinsim_workshop
Feature: Plagiarism plugin works with a Moodle Workshop
  In order to allow students to send workshop submissions to Turnitin
  As a user
  I need to create a workshop with the plugin enabled.

  Background: Set up the users, course and workshop with plugin enabled
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 1         |
    And I create a unique moodle user with username "student1"
    And the following "course enrolments" exist:
      | user        | course | role    |
      | student1    | C1     | student |
    When I log in as "admin"
    And I navigate to "Advanced features" in site administration
    And I set the field "Enable plagiarism plugins" to "1"
    And I press "Save changes"
    And I navigate to "Plugins > Plagiarism > TurnitinSim plagiarism plugin" in site administration
    And I configure TurnitinSim credentials
    And I set the following fields to these values:
      | Enable TurnitinSim for Workshop | 1 |
    And I press "Save changes"
    # Check that features enabled are displayed.
    Then I should see "TurnitinSim features"
    And I should see "Repositories checked against"
    # Create Workshop.
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Workshop" to section "1" and I fill the form with:
      | Workshop name                 | Test workshop |
      | turnitinenabled               | 1             |
      | accessoptions[accessstudents] | 1             |
    And I am on "Course 1" course homepage
    And I edit assessment form in workshop "Test workshop" as:"
      | id_description__idx_0_editor | Aspect1 |
      | id_description__idx_1_editor | Aspect2 |
      | id_description__idx_2_editor |         |

  @javascript
  Scenario: A submission can be queued and sent to Turnitin
    And I change phase in workshop "Test workshop" to "Submission phase"
    And I log out
    # Student makes submission to workshop.
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test workshop"
    And I press "Start preparing your submission"
    And I click on "I accept the Turnitin EULA" "button"
    And I set the following fields to these values:
      | Title              | Submission1                                                                                                                                            |
      | Submission content | This is a workshop submission that will be submitted to Turnitin. It will be sent to Turnitin for Originality Checking and matched against any sources |
      | Attachment         | plagiarism/turnitinsim/tests/fixtures/testfile.txt                                                                                                           |
    And I press "Save changes"
    Then I should see "My submission"
    And I should see "Queued" in the "div.turnitinsim_links" "css_element"
    And I log out
    # Admin runs scheduled task to send submission to Turnitin.
    And I log in as "admin"
    And I run the scheduled task "plagiarism_turnitinsim\task\send_submissions"
    And I am on "Course 1" course homepage
    And I follow "Test workshop"
    And I follow "Submission1"
    Then I should see "Pending" in the "div.turnitinsim_links" "css_element"
    And I log out
    # Student can see submission has been sent to Turnitin.
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test workshop"
    And I follow "Submission1"
    Then I should see "Pending" in the "div.turnitinsim_links" "css_element"
    And I log out
    # Admin runs scheduled task to request an originality report.
    And I log in as "admin"
    And I wait "10" seconds
    And I run the scheduled task "plagiarism_turnitinsim\task\get_reports"
    # Admin runs scheduled task to request originality report score.
    And I wait "20" seconds
    And I run the scheduled task "plagiarism_turnitinsim\task\get_reports"
    And I log out
    # Login as student and a score should be visible.
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test workshop"
    And I follow "Submission1"
    Then I should see "%" in the "div.turnitinsim_links" "css_element"