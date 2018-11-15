# php-telegram-bot-gantt

A take on importing and processing [GanttProject's][1] milestones with the goal
of creating reminders for users.

[1]: https://www.ganttproject.biz/

# Configuration
1. Substitute the contents of `.env.dist` with your own data.
2. Rename `.env.dist` to `.env`.
3. Import `ikastenbot.sql` file with
    `mysql -u USER -p DATABASE < ikastenbot.sql`
4. Import `setUpDatabase.sql` file with
    `mysql -u USER -p DATABASE < setUpDatabase.sql`.

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
4. Run `phpunit` with `vendor/bin/phpunit` from the project root.

# Notes
* When using conversations, make sure the names don't contain spaces, as
    php-telegram bot seems not to be able to keep up with the conversation if
    such thing happens.

[2]: https://help.ubuntu.com/community/CronHowto
[3]: https://crontab.guru/
