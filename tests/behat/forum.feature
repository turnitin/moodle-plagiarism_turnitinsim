@plugin @plagiarism @plagiarism_turnitinsim @plagiarism_turnitinsim_forum
Feature: Plagiarism plugin works with a Moodle forum
  In order to allow students to send forum posts to Turnitin
  As a user
  I need to create a forum and discussion with the plugin enabled.

  Background: Set up the users, course, forum and discussion with plugin enabled
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 1         |
    And I create a unique moodle user with username "student1"
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    When I log in as "admin"
    And I navigate to "Advanced features" in site administration
    And I set the field "Enable plagiarism plugins" to "1"
    And I press "Save changes"
    And I navigate to "Plugins > Plagiarism > TurnitinSim plagiarism plugin" in site administration
    And I configure TurnitinSim credentials
    And I set the following fields to these values:
      | Enable TurnitinSim for Forum | 1 |
    And I press "Save changes"
    # Check that features enabled are displayed.
    Then I should see "TurnitinSim features"
    And I should see "Repositories checked against"
    # Create Forum.
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Forum" to section "1" and I fill the form with:
      | Forum name                    | Test forum                     |
      | Forum type                    | Standard forum for general use |
      | Description                   | Test forum                     |
      | groupmode                     | 0                              |
      | turnitinenabled               | 1                              |
      | accessoptions[accessstudents] | 1                              |
    And I follow "Test forum"
    And I click on "Add a new discussion topic" "button"
    And I click on "I accept the Turnitin EULA" "button"
    And I set the following fields to these values:
      | Subject | Forum post 1                                                                                                                |
      | Message | This is the body of the forum post that will be submitted to Turnitin. It will be sent to Turnitin for Originality Checking |
    And I press "Post to forum"

  @javascript
  Scenario: Add a post to a discussion with a file attached and retrieve the originality score
    Given I log out
    # Student creates a forum discussion and replies to original post.
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test forum"
    And I click on "Add a new discussion topic" "button"
    And I click on "I accept the Turnitin EULA" "button"
    And I set the following fields to these values:
      | Subject | Forum post 2                                                                                                                |
      | Message | This is the body of the forum post that will be submitted to Turnitin. It will be sent to Turnitin for Originality Checking |
    And I press "Post to forum"
    And I reply "Forum post 1" post from "Test forum" forum with:
      | Subject    | Reply with attachment                                                                                                       |
      | Message    | This is the body of the forum reply that will be submitted to Turnitin. It will be sent to Turnitin for Originality Checking |
      | Attachment | plagiarism/turnitinsim/tests/fixtures/testfile.txt                                                                                |
    Then I should see "Reply with attachment"
    And I should see "testfile.txt"
    And I should see "Queued" in the "div.turnitinsim_links" "css_element"
    And I log out
    # Admin runs scheduled task to submit post and file to Turnitin.
    And I log in as "admin"
    And I run the scheduled task "plagiarism_turnitinsim\task\send_submissions"
    And I am on "Course 1" course homepage
    And I follow "Test forum"
    And I follow "Forum post 1"
    Then I should see "Pending" in the "div.turnitinsim_links" "css_element"
    And I log out
    # Student can see post has been sent to Turnitin.
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test forum"
    And I follow "Forum post 1"
    Then I should see "Pending" in the "div.turnitinsim_links" "css_element"
    And I log out
    # Admin runs scheduled task to request an originality report.
    And I log in as "admin"
    And I wait "10" seconds
    And I run the scheduled task "plagiarism_turnitinsim\task\get_reports"
    # Admin runs scheduled task to request originality report score.
    And I wait "20" seconds
    And I run the scheduled task "plagiarism_turnitinsim\task\get_reports"
    # Login as student and a score should be visible.
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test forum"
    And I follow "Forum post 1"
    Then I should see "%" in the "div.turnitinsim_links" "css_element"
