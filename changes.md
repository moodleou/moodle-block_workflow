# Change log for the Workflow block

## Changes in 2.5
* This version works with Moodle 5.1.


## Changes in 2.4
* This version works with Moodle 5.0.
* Automation test failures are fixed.
* Cherry-picked commits since february 2024 till now:
  * Fix PHP8 deprecation errors (optional params before required ones)
  * Behat: blocks/workflow (tt)
  * blocks/workflow: Fix PHP  8.1 issues
  * M4.2: fix uses of depreated cron_setup_user
  * Workflow error when moving workflow on - and error reporting code is buggy
  * YUI->AMD: Rewrite old JavaScript in block_workflow
  * Behat: PHP8.2: fix block_/report_workflow failures
  * Workflow block: convert use of ajax.php into moodle web services
  * Workflow block: convert use of ajax.php into moodle web services
  * Workflow/TinyEditor: incorrectly keeps the cached data when comment and finish step
  * Moodle 4.5 merge - replace deprecated get_plugin_list
  * Moodle 4.5 merge - fix miscellaneous Behat and PHPunit failures
  * Theme: Technical debt - remove IE-specific rules
  * Workflow: Enable logs to show whether a workflow email is sent
* Upgrade the CI to support Moodle 5.0 (PHP 8.3), and update the branch to support branch MOODLE_405_STABLE, and MOODLE_500_STABLE.


## Changes in 2.3

* This version works with Moodle 4.0.
* Update to display custom profile fields in the places where user identity is shown.
* Improve the wording of some error strings.


## Changes in 2.2

* Improved test data generators (mainly for the benefit of other OU plugins which integrate with this).


## Changes in 2.1

* Fix malformed HTML.


## Changes in 2.0

* New tokens %%coursestartdate%%, %%courseenddate%%, %%activityopendate%%
  and %%activityclosedate%% which can be used in email templates.
* On the admin screens, the styling has been improved, so it is
  more obvious which workflows are inactive.
* Fix issue with the styling of popups.
* Fix failing Behat tests.


## Changes in 1.9

* When creating an email templates, you are shown a list of available placeholder.
* Extended privacy provider support for Moodle 3.6.
* Fix compatibility with PHP 7.3.
* Coding style fixes.


## Changes in 1.8

* Add logging/Moodle events for workflow changes.
  Thanks to Henrik Thorn from https://itkartellet.dk/.
* Fix an incorrect database index definition.
* Fix Behat tests for Moodle 3.6.


## Changes in 1.7

* Privacy API implementation.
* New script action setgradeitemvisibility.
* Improve the styling of the workflow overview table.
* Make email sending more robust.
* Fix compatibility with recent Moodle versions.
* Setup Travis-CI automated testing integration.
* Fix some automated tests to pass with newer versions of Moodle.
* Fix some coding style.
* Due to privacy API support, this version now only works in Moodle 3.4+
  For older Moodles, you will need to use a previous version of this plugin.


## 1.6 and before

Changes were not documented here.
