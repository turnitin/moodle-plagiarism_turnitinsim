TurnitinCheck Plagiarism plugin for Moodle
=

Installation
-

To install, all you need to do is copy all the files into the plagiarism/turnitincheck directory on your moodle installation. You should then go to `"Site Administration" > "Notifications"` and follow the on screen instructions.

Plagiarism plugins also need to be enabled before this plugin can be used. This should happen as part of the install process but if it doesn't then you can do this by going to `"Site Administration" > "Advanced Features"` and ticking the `"Enable plagiarism plugins"` checkbox before saving.

Configuring
-
To configure the plugin go to `"Site administration" > "Plugins" > "Plagiarism" > "TurnitinCheck plagiarism plugin"` and enter your API key and API URL.

Testing
-
This plugin contains a full suite of PHPUnit and behat tests which can be run against your moodle installation. 

Provided your environment is already configured to run PHPUnit tests, then run the following command from the Moodle root directory:  

`vendor/bin/phpunit --testsuite plagiarism_turnitincheck_testsuite`

Provided your environment is already configured to run Behat tests, then run the following command from the Moodle root directory:

`php admin/tool/behat/cli/run.php --tags=@plagiarism_turnitincheck`

See [Running Acceptance Tests](https://docs.moodle.org/dev/Running_acceptance_test) for further information.