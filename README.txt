Workflow block
https://moodle.org/plugins/block_workflow

This block was created for the Open Unversity (https://www.open.ac.uk/) by
LUNS (http://www.luns.net.uk/services/virtual-learning-environments/vle-services/).
The specification was writted by Tim Hunt and Sharon Monie.

You can install this block from the Moodle plugins database using the link above.

Note that some of the functionality (e.g. the Privacy API implementation) may only work
if you are using a Postgres database.)

Alternatively, you can install it using git. Type this command in the top level
of your Moodle install:

    git clone git://github.com/moodleou/moodle-block_workflow.git blocks/workflow
    echo '/blocks/workflow/' >> .git/info/exclude

Once you have added the code to Moodle, visit the admin notifications page to
complete the installation.

If you are using this plugin, then you probably also want to install the workflow report:
https://moodle.org/plugins/report_workflow

For more documentation, see http://docs.moodle.org/en/The_OU_workflow_system.
