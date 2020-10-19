Turnitin Integrity Plugin for Moodle
=

Description:
-
Utilize **Turnitin Integrity’s** Similarity Report and Authorship investigating tools within Moodle's assignment workflow by integrating with the Turnitin Integrity plugin. **Turnitin Integrity** is a commercial plagiarism and authorship detection system whose features depend on which paid license has been selected. This plugin is developed and maintained by Turnitin.

Features:
-
- Plugin integrates into the existing Moodle assignment, Forum, and Workshop workflows
- Plugin provides Turnitin Originality, Similarity, and SimCheck services dependant on the license used
- Receive a similarity score for your Moodle assignment, Forum, and Workshop submissions
- Launch into the Turnitin viewer to review a detailed report on the similarity score produced
- Option for anonymized submissions which masks the student’s details when sent to Turnitin

Useful Links
-
[Creating your Turnitin API key](https://help.turnitin.com/simcheck/integrations/moodle/administrator/account-basics/creating-an-API-key.htm)

Installation
-
Before installing this plugin firstly make sure you are logged in as an Administrator and that you are using Moodle 3.5 or higher.

To install, all you need to do is copy all the files into the plagiarism/turnitinsim directory on your Moodle installation. You should then go to `"Site Administration" > "Notifications"` and follow the on screen instructions.

Plagiarism plugins also need to be enabled before this plugin can be used. This should happen as part of the install process but if it doesn't then you can do this by going to `"Site Administration" > "Advanced Features"` and ticking the `"Enable plagiarism plugins"` checkbox before saving.

Configuring
-
To configure the plugin go to `"Site administration" > "Plugins" > "Plagiarism" > "Turnitin Integrity plagiarism plugin"` and enter your API key and API URL.

Other options can also be set, such as which Moodle modules to enable the plugin for or logging. Logging can be useful in scenarios where there is a problem with your installation. Default settings for the plugin can also be enabled so that you don't have to configure every individual assignment each time.

Testing
-
This plugin contains a full suite of PHPUnit tests which can be run against your Moodle installation. 

Provided your environment is already configured to run PHPUnit tests, then run the following command from the Moodle root directory:  

`vendor/bin/phpunit --testsuite plagiarism_turnitinsim_testsuite`

Provided your environment is already configured to run Behat tests, you can download and install our behat tests from their repository by running the following command from the plugin root directory:

`git clone https://github.com/turnitin/moodle-plagiarism-turnitinsim-behat.git tests/behat`

Then run the following command from the Moodle root directory:

`php admin/tool/behat/cli/run.php --tags=@plagiarism_turnitinsim`

See [Running Acceptance Tests](https://docs.moodle.org/dev/Running_acceptance_test) for further information.