# php-telegram-bot-gantt

A take on importing and processing [GanttProject's][1] milestones with the goal
of creating reminders for users.

# Configuration for development mode
1. Substitute the contents of `.env.dist` with your own data.
2. Rename `.env.dist` to `.env`.
3. Import Longman's `.sql` file with
    `mysql -u USER -p DATABASE < structure.sql`.
4. Import `ikastenbot.sql` file with
    `mysql -u USER -p DATABASE < ikastenbot.sql`.
5. Import `setUpDatabase.sql` file with
    `mysql -u USER -p DATABASE < setUpDatabase.sql`.

# Configuration for production mode
1. Set [environment variables][2] that match `.env.dist` file.
2. Point the web server to the `public/` directory of this project.
3. Repeat steps from `3.` to `5.` from the previous section in the production
    server.

# Setting up cron jobs to remind users about their milestones
In order to notify users whenever their planned milestones are close, a cron
job can be set to achieve this. `LaunchMilestoneReminderService.php` deals with
obtaining the proper milestones from the database and sending reminders to the
users, and therefore it just needs to be launched. An example of doing so with
a cron job would be the one below, in which the check is performed every day at
2AM:

* `0 2 * * * /usr/bin/php {PATH_TO_THE_PROJECT}/src/LaunchMilestoneReminderService.php`

Check [CronHowto][2] and [crontab.guru][3] to create proper cron jobs which can
suit your needs.

# Running tests
In order to run tests you have to make the following steps:

1. Import Longman's `structure.sql` file, and this project's `setUpDatabase.sql`
    and `ikastenbot.sql` files to the testing database.
2. Copy `phpunit.xml.dist` to `phpunit.xml` as follows: `cp phpunit.xml.dist phpunit.xml`.
3. Set the testing database parameters in the `phpunit.xml` file.
4. Set the project root constant in the `phpunit.xml` file without the trailing
    `/`
5. Run `phpunit` with `vendor/bin/phpunit` from the project root.

# Notes
* When using conversations, make sure the names don't contain spaces, as
    php-telegram bot seems not to be able to keep up with the conversation if
    such thing happens.

[1]: https://www.ganttproject.biz/
[2]: https://httpd.apache.org/docs/2.4/mod/mod_env.html#setenv
[3]: https://help.ubuntu.com/community/CronHowto
[4]: https://crontab.guru/
