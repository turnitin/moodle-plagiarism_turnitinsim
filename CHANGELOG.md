### Date:       2022-September-21
### Release:    v2022092101

#### :wrench: Fixes and enhancements

#### Fixed a bug that would show instructors a resubmit button when an unsupported file type was submitted

Previously, if a student submitted an unsupported file type to a Moodle assignment, a Resubmit to Turnitin button would still appear for the instructor. This has now been fixed and the option to re-submit an unsupported file type has been removed.

---

### Date:       2021-May-13
### Release:    v2022051301

#### :zap: What's new

---

#### Support for Moodle 4.0

You can now confidently use Turnitin with Moodle 4.0.

#### :wrench: Fixes and enhancements

---

#### Submissions will no longer stick on ‘Pending’

When ‘Process draft submissions' was selected for an assignment, submissions would appear as stuck on the 'Pending’ status after a student  made their final submission to the assignment.

This bug has been fixed, and the correct submission state will be shown.

---

### Date:       2021-June-01
### Release:    v2021060101

#### :zap: What's new

---

#### Support for Moodle 3.11

You can now confidently use Turnitin with Moodle 3.11.

#### Retain assignment settings and submissions after a course restore in a new environment

Turnitin already supports Moodle’s backup and store functionality, this change gives the same functionality when restoring to a different environment from the original backup.

#### :wrench: Fixes and enhancements

---

#### New CSS class name prefix

To help conform to Moodle guidelines, all classes within the plugin are now prefixed with ‘turnitinsim_’. This change will help avoid any potential styling conflicts.

#### Draft submissions will not stick in pending status

A bug had caused some draft submissions to stick in ‘pending’ status and not generate a Similarity Report. This bug has now been fixed and draft submissions will generate Similarity Reports if enabled.

#### Accept the Turnitin EULA after forum posts have been made

If an Instructor had not previously accepted the Turnitin EULA, but students had already started to post to a Moodle Forum, the instructor wouldn’t be given another chance to accept it. We will now show the EULA acceptance option to any users who haven’t previously accepted it. 

#### Change the default activity tracking moodle setting

A bug had prevented users from being able to alter the default activity tracking setting within Moodle when Turnitin was also enabled. This bug has now been fixed, and you can use this feature alongside Turnitin without error.

---

### Date:       2021-March-09
### Release:    v2021030901

#### :wrench: Fixes and enhancements

---

#### Intro files no longer prevent submissions

As of our last release, a bug caused when attaching an intro file to a submission prevented students from submitting. We have fixed this bug as a matter of urgency, and the intended functionality has been restored.

---

### Date:       2021-March-02
### Release:    v2021030201

#### :zap: What's new

---

#### API URLs will self-correct when inputted incorrectly

When configuring the plug-in, the API URL used should end in /API. However, some users have copied just the first part of the URL into the field, which will cause the configuration to fail. We now proactively check for instances where the URL has not been configured correctly and self-correct them so that the configuration will succeed.

#### :wrench: Fixes and enhancements

---

#### Default value change for quizanswer filed in submissions table

We have updated the default value for a database field in the submissions table (quizanswer) from null to 0. This could potentially cause automatic integration problems. Thank you to OpenLMS for letting us know about the problem. It’s now been fixed.

#### Missing User ID - various fixes

We’ve performed a thorough investigation into an issue where Moodle does not pass a user ID to Turnitin, which can present in various ways. These include:

* The annotate PDF module attempts to save a submission for when a PDF is annotated.
* If a student who has not accepted our EULA tries to view the inbox after submissions have begun but before the instructor has viewed it.
* Text-submissions for group assignments would not save correctly if Turnitin was enabled after the submission was made.

All of these issues are fixed in this release.

---

### Date:       2021-January-18
### Release:    v2021011801

#### :zap: What's new

---

#### Support for Moodle 3.10

You can now confidently use Turnitin with Moodle 3.10.

#### Improved loading screen for the Turnitin Integrity Viewer

We’ve improved the loading screen you see when you launch the Turnitin Integrity viewer. The new loading screen includes an animation to indicate that the viewer is still opening and will be ready soon.

You can use this new animation to tell if something may have gone wrong and needs to be investigated.

#### Styling and workflow improvements to the Turnitin EULA

We haven’t made any changes to the EULA itself, but we have adjusted the styling of the box that is shown to ask you to review and accept it when a user first uses Turnitin. We’ve also taken the opportunity to fix a few bugs that could interrupt the intended EULA workflow

#### Get Help in all of Turnitin’s supported languages

Did you know we offer full step-by-step guidance for all Turnitin features? You’ll find direct links to the pages relevant for the language you view the Turnitin Integrity plugin in.

#### Use Turnitin with older submissions

If you enable Turnitin for an assignment after submissions have already been made, we will now queue these for report generation the next time you view the inbox in Moodle. These files will have the QUEUED status. If a student has not accepted the Turnitin EULA yet, we will instead show Awaiting EULA and will only process the file after the student has accepted it.

#### :wrench: Fixes and enhancements

---

#### Assignment settings have the correct spacing

The Turnitin settings configured when creating a Moodle assignment could have odd spacing. We’ve cleaned this up so the settings flow as they should.

#### Feedback and Intro files will no longer be checked for similarity

We no longer check any feedback or intro files you attach to an assignment for your students for similarity.

#### Deleting a course module will now also delete any attached Turnitin submissions

If a course module is deleted, along with its Moodle Assignments, we will now also delete any relevant Turnitin settings or entries in our database.

#### dateformat now uses the correct format

Thanks to our friends at OpenLMS who let us know that the download_as_dataformat() method was deprecated in Moodle 3.9. We’ve now updated this to the latest code to ensure everything works correctly for all users.

#### Accept the Turnitin EULA once for all assignment types

The EULA could show multiple times for each type of Moodle assignment. Now users only have to accept it once and we will remember this choice when generating Similarity Reports for Moodle Assignments, Workshops, Forums, and quizzes.

---

### Date:       2020-September-23
### Release:    v2020092301

#### :zap: What's new

---

#### Support for Moodle Quizzes

Turnitin will be usable as a part of a Moodle quiz when Moodle releases the feature. When enabled for your account, simply add an essay question as one of the quiz questions. A similarity report will be generated when the student submits the quiz. Track this release on the [Moodle Tracker](https://tracker.moodle.org/browse/MDL-32226).

#### Privacy API declartion now includes the submission’s contents

The [Moodle Privacy API](https://docs.moodle.org/dev/Privacy_API) helps plugins report what user data a plugin uses so they cna make informed decisions about their personal information. As a part of the Privacy API declartion, we will now includethe contents of the submission to fully support the Privacy APIs goals.

Thanks to [thepurpleblob](https://github.com/turnitin/moodle-mod_turnitintooltwo/issues/464) for the catch!

#### Permission to use the Turnitin Integrity plugin can be limited to individual instructors

Two new permission settings can now be configured that will allow you to specify certain users who have access to the Turnitin Integrity plugin. This can be used to limit use to certain departments or schools within your organization.

You are now able to choose if a user is able to Enable Turnitin Integrity for an assignment, and choose if they are able to view any generated similarity reports.

You can take advantage of this new setting by navigation to Site Administration > Users > Permissions > Check system permissions for the user your wish to give access to.

#### Support for Korean, Japanese, Chinese (Traditional), and Chinese (Simplified)

Our interface has been fully localized into four new languages. Check out our help site for full step-by-step guides in these languages too

#### :wrench: Fixes and enhancements

---

#### EULA screen is now not blank after accepting the Turnitin EULA

Rather than seeing a blank screen, we’ll now show you a simple message confirming that you have accepted our EULA when navigating to <Your Moodle Instance’s URL>/plagiarism/turnitinsim/eula.php?cmd=displayeula

#### Turnitin will only show on activity types we support

Turnitin only supports Moodle assignments, forums, and workshops (and quizzes once released by Moodle!). However, it was possible that the option to enable Turnitin would show on the settings page for activity types we don't support.

To help clear up any confusion, the option to enable Turnitin will now only show on activity types we support.

---

### Date:       2020-July-21
### Release:    v2020072101

#### :zap: What's new

---

#### We now support Moodle 3.9

You can find out more about Moodle 3.9 via Moodle's [release notes](https://docs.moodle.org/dev/Moodle_3.9_release_notes).

#### EULA update notifications will no longer automatically email users

When we make an update to our EULA, we send a Moodle message to users so they can read any changes and make sure they are comfortable with them before continuing to use Turnitin. Moodle messages had defaulted to also send an email when these messages were received. After user feedback, we’ve changed how the Turnitin Integrity Plugin interacts with the Moodle Message API to default this setting to Off and making emails now opt-in.

If you’d like your users to continue getting an email when these changes happen, navigate to Site administration > Messaging > Notification settings and enable the setting for your integration.

This change will only apply to users created after this plugin update.

#### Multi-task with submissions from the same student

Students in a Moodle assignment can upload up to 20 files to the same assignment. As a part of your grading workflow, you may want to view some of these documents side-by-side to compare them directly. We’ve enabled this option so that you’ll be able to open multiple windows with submissions from the same student at the same time.

#### :wrench: Fixes and enhancements

---

#### Collusion check on Due Date

A bug had prevented some collusion checks from running on the due date. Collusion checks will now work as intended and a new report is generated for all submissions in a Moodle assignment once the due date has passed. This will only apply when the setting to regenerate on due date is turned on.

#### Resubmit link removed when a student rejects the Turnitin EULA

When a student rejects the Turnitin EULA, we provide a message saying the EULA has not been accepted rather than process the file for Similarity and return a 451 error. However, when an instructor then tried to submit a file on behalf of that student using the resubmit link, the file would be queued for processing but then return a 451 error.

Now, when a student rejects the EULA, no option to resubmit will be shown.

#### More reliable API connection check

As a part of the plugin configuration, we run a quick check of the details your provide to make sure they connection correctly to Turnitin. We’ve improved this checker to ensure that it is constantly reliable and accurately shows your connection status.

#### Turn off Turnitin for Forums and Workshops at the global level

When disabled on the global configuration page, plagiarism/turnitincheck/settings.php it had been possible to still add Turnitin to a Workshop or Forum. We’ve now made sure this setting will update the permission settings when adding one of these settings so you can turn off Turnitin when you need to.

---

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
