# Ikastenbot

Ikastenbot is a Telegram bot that handles [GanttProject][1]s. The bot is able
to import the tasks from a `.gan` file and send reminders whenever those tasks
are close to be reached. The user can delay those tasks —the bot will take care
of the dependant tasks— and turn on/off the notifications.

# Configuration
## Variables
All the variables —database settings, the API keys, file directories...— are
stored in environment variables. When using `development` mode, these variables
are read from the `.env` file, and in `production` mode, these will have to be
set [in the web server's configuration][2].

## Webhook
In order to set or unset the web hook, run `php src/Misc/set.php` or
`php src/Misc/unset.php`. The `php-telegram-bot`'s `hook.php` file has been
renamed to `public/index.php`, following the [front controller pattern][3].

## For development
1. Copy `.env.dist` to `.env` with `cp .env.dsit .env`.
2. Fill the relevant data in the `.env` file.
3. Import Longman's `.sql` file with
    `mysql -u USER -p DATABASE < vendor/longman/telegram-bot/structure.sql`.
4. Import `structure.sql` file with
    `mysql -u USER -p DATABASE < structure.sql`.
5. Perform `Doctrine`'s migrations with
    `vendor/bin/doctrine-migrations migrations:migrate`

Regarding the database: the step \#3 imports Longman's `.sql` file and creates
the base tables. The step \#4 imports the legacy database's additions plus the
required rows for the bot to work with the legacy commands. Finally, the step
\#5 loads the model of this application —`src/Entity`— into the database.

## For production
1. Set [environment variables][2] that match the `.env.dist` file.
2. Set an environment variable `TBGP_ENV` to any value.
3. Point the web server to the `public/` directory of this project.
4. Repeat steps `3.` to `5.` from the previous section in the production
    server.
5. Generate Doctrine's proxy entities with
    `vendor/bin/doctriene orm:generate-proxies`.

Step number 2 makes the application read the environment variables from the
server, instead of the `.env` file.

## Send reminders to users
The bot will send notifications to the users whenever the tasks or milestones
are close. You can use a `cron` job to schedule notification dispatching
whenever you want to. There are two services,
`LaunchMilestoneReminderService.php` and `LaunchTaskReminderService.php`, that
send the reminders. The former will send the notifications about the milestones
only, and the second one will send the reminders about every task the user has
—including the milestones—.

The following example sets a `cron` job to dispatch the notifications every day
at 2AM.

* `0 2 * * * /usr/bin/php {PATH_TO_THE_PROJECT}/src/LaunchMilestoneReminderService.php`
* `0 2 * * * /usr/bin/php {PATH_TO_THE_PROJECT}/src/LaunchTaskReminderService.php`

[CronHowto][4] and [crontab.guru][5] can help you create `cron` jobs that may
suit your needs better.

# Running the tests
## Coding guidelines
The code of this project follows the rules from the `@PhpCsFixer` rule set. In
order to check whether the code you wrote follows the rules or not, you just
have to run:

`vendor/bin/php-cs-fixer fix --config=.php_cs.dist --dry-run --verbose --diff`

## Code tests
In order to run tests you have to make the following steps:

1. Import Longman's `vendor/longman/telegram-bot/structure.sql` file, and this
    project's `sql/structure.sql` into the testing database.
2. Copy `phpunit.xml.dist` to `phpunit.xml` with `cp phpunit.xml.dist phpunit.xml`.
3. Set the testing database parameters in the `phpunit.xml` file.
4. Run `phpunit` with `vendor/bin/phpunit` from the project root.

[1]: https://www.ganttproject.biz/
[2]: https://httpd.apache.org/docs/2.4/mod/mod_env.html#setenv
[3]: https://en.wikipedia.org/wiki/Front_controller
[4]: https://help.ubuntu.com/community/CronHowto
[5]: https://crontab.guru/
[6]: https://github.com/FriendsOfPHP/PHP-CS-Fixer#installation
