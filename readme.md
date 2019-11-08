# Edit roles by capability tool [![Build Status](https://travis-ci.org/moodleou/moodle-block_workflow.svg?branch=master)](https://travis-ci.org/moodleou/moodle-block_workflow)

Administrators can define workflows which can then be applied to Courses of activities.
A workflow is a sequence of steps. Each step has a role who is responsible
for doing it, some instructions, and possibly some tasks to tick off.
When one task is complete, the people responsible for the next task can be notified.
Also, when a step starts or becomes complete, various automated actions can be scripted to occur.

For more documentation, see https://docs.moodle.org/en/The_OU_workflow_system.


## Acknowledgements

This block was created by the Open University (https://www.open.ac.uk/),
with help from developers at LUNS and NashTech as well as in-house staff.


## Installation and set-up

Note that some of the functionality (e.g. the Privacy API implementation) may only work
if you are using a Postgres database.)

If you are using this plugin, then you probably also want to install the workflow report:
https://moodle.org/plugins/report_workflow

### Install from the plugins database

See https://moodle.org/plugins/block_workflow.

### Install using git

Or you can install using git. Type this commands in the root of your Moodle install

    git clone https://github.com/moodleou/moodle-block_workflow.git blocks/workflow
    echo '/blocks/workflow/' >> .git/info/exclude

Then run the moodle update process
Site administration > Notifications
