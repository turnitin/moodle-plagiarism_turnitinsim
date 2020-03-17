Turnitin Integrity Plugin for Moodle
=

Description:
-
Utilize **Turnitin Integrity’s** Similarity Report and Authorship investigating tools within Moodle’s assignment workflow by integrating with the Turnitin Integrity plugin. **Turnitin Integrity** is a commercial plagiarism and authorship detection system whose features depend on which paid license has been selected. This plugin is developed and maintained by Turnitin.

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

To install, all you need to do is copy all the files into the plagiarism/turnitinsim directory on your moodle installation. You should then go to `"Site Administration" > "Notifications"` and follow the on screen instructions.

Plagiarism plugins also need to be enabled before this plugin can be used. This should happen as part of the install process but if it doesn't then you can do this by going to `"Site Administration" > "Advanced Features"` and ticking the `"Enable plagiarism plugins"` checkbox before saving.

Configuring
-
To configure the plugin go to `"Site administration" > "Plugins" > "Plagiarism" > "Turnitin Integrity plagiarism plugin"` and enter your API key and API URL.

Testing
-
This plugin contains a full suite of PHPUnit and behat tests which can be run against your moodle installation. 

Provided your environment is already configured to run PHPUnit tests, then run the following command from the Moodle root directory:  

`vendor/bin/phpunit --testsuite plagiarism_turnitinsim_testsuite`

Provided your environment is already configured to run Behat tests, then run the following command from the Moodle root directory:

`php admin/tool/behat/cli/run.php --tags=@plagiarism_turnitinsim`

See [Running Acceptance Tests](https://docs.moodle.org/dev/Running_acceptance_test) for further information.