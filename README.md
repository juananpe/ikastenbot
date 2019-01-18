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

## For production
1. Set [environment variables][2] that match the `.env.dist` file.
2. Point the web server to the `public/` directory of this project.
3. Repeat steps from `3.` and `4.` from the previous section in the production
    server.
4. Generate Doctrine's proxy entities with
    `vendor/bin/doctriene orm:generate-proxies`.

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
4. Set the project root constant in the `phpunit.xml` file without the trailing
    `/`
5. Run `phpunit` with `vendor/bin/phpunit` from the project root.

[1]: https://www.ganttproject.biz/
[2]: https://httpd.apache.org/docs/2.4/mod/mod_env.html#setenv
[3]: https://en.wikipedia.org/wiki/Front_controller
[4]: https://help.ubuntu.com/community/CronHowto
[5]: https://crontab.guru/
[6]: https://github.com/FriendsOfPHP/PHP-CS-Fixer#installation
