### Date:      2020-April-22
### Release:   v2020042201

#### :wrench: Fixes and enhancements

---

#### Deleted files will no longer cause cron errors

We will now check a file exists before attempting to upload it to Turnitin. This check will prevent cron errors from occurring as it looks for a non-existing file.

#### Files unsuccessfully uploaded to Turnitin will now automatically retry

In the rare instance that a file is unable to be uploaded to Turnitin during submission, we will automatically retry to send it to Turnitin again without further action from the user.

#### Webhooks can now be recreated

We have fixed an issue where webhooks would not be recreated when running the “Update local configuration” scheduled task. This could cause problems when a user would try to change the URL of their Moodle instance.

---

### Date:      2020-April-17
### Release:   v2020041701

#### :wrench: Fixes and enhancements

---

#### Plugin successfully installs via zip file.

We have fixed an issue where the plugin may not install if downloaded from the Moodle plugin directory due to it containing an empty behat directory.

---

### Date:      2020-April-15
### Release:   v2020041501

#### :zap: What's new

---

#### Turnitin Integrity now available in 9 languages

You can now use all elements of the plugin using English, Danish, German, Mexican Spanish, French, Dutch, Norweigan, Brazillian Portuguese, or Swedish.

#### New user role mappings

We’ve mapped Moodle user roles more accurately with our system so we know more information about the role used when using the plugin. For example, if a teacher submits on behalf of a student, the student will now be registered as the owner of the submission but the teacher can be logged as the submitter.

#### Check for plagiarism on assignments you previously didn’t enable it for

When creating an assignment you chose if Turnitin should be enabled for it. If enabled, we’ll automatically create a Similarity Report on any files we can.

You can now retroactively enable Turnitin for an assignment, forum, or workshop, even if students have already begun to submit. You can enable Turnitin when editing an assignment by [following the usual process](https://help.turnitin.com/simcheck/integrations/moodle/instructor/assignments/adding-turnitin-to-a-moodle-assignment.htm).

#### :wrench: Fixes and enhancements

---

#### MS SQL databases are now supported

As Moodle supports MS SQL, we have changed our plugin to also offer support to prevent any potential problems when using MS SQL databases.

#### Improved retry logic for Similarity Report generation

We’ve implemented more efficient retry logic that helps to flag to customers when there is a problem with their submission sooner. After files are uploaded to Moodle we will try to generate a Similarity Report. In the vast majority of uploads this will be successful. In the rare case where report generation is not successful, we will no longer repeatedly try to generate a Similarity report. Now, the plugin will wait one hour before trying again. If after an hour the Similarity Report fails to generate, we will show an error message so the user can follow this up with our support team.
