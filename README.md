# Ikastenbot

Ikastenbot is a Telegram bot that handles [GanttProjects][gantt-project-page].
The bot is able to import the tasks from a `.gan` file and send reminders
whenever those tasks are close to be reached. The user can delay those tasks
—the bot will take care of the dependant tasks— and turn on/off the
notifications.

# Configuration
## Note
All the commands and paths are considered to be run inside the `bot` directory,
and therefore you might have to do `cd bot` before running them.

## Variables
All the variables —database settings, the API keys, file directories...— are
stored in environment variables. When using `development` mode, these variables
are read from the `.env` files, and in `production` mode, these will have to be
set [in the web server's configuration][apache-docs-env].

## Installing dependencies
In order to install the project's dependencies, you must issue the following
command:

`composer install`

## Webhook
In order to set or unset the web hook follow these steps:

* Run `php bin/console app:webhook --set` to set the webhook.
* Run `php bin/console app:webhook --unset` to unset the webhook.

All the requests coming from `Telegram` go through the
[front controller][wiki-front-controller] located in `public/index.php`, and
skip the `Symfony` framework entirely. Any other request is processed by
`Symfony`.

## Setting up the variables for development —database, paths, etc.—
1. Copy `.env` to `.env.local` with `cp .env .env.local`.
2. Fill the relevant data in the `.env.local` file.
3. Import database schema and content with
    `php bin/console doctrine:migrations:migrate --no-interaction`

## Setting up the application in production
### Generic configuration
1. Set [environment variables][apache-docs-env] that match the `.env` file.
2. Point the web server to the `public/` directory of this project.
3. Repeat step `3.` from the previous section in the production server.
4. Make sure `www-data` has writing permissions for the `var/` directory.

Step number \#4 is needed as `cache` is stored in `var`, as well as `gan`
files. Without the proper permissions the application will complain and fail
to work properly.

### Apache
If you use `Apache` as the web server, the following configuration is
recommended —taken from [Symfony docs][symfony-docs-apache-prod]—:
```
<VirtualHost *:80>
    ServerName domain.tld
    ServerAlias www.domain.tld

    DocumentRoot /var/www/project/public
    <Directory /var/www/project/public>
        AllowOverride None
	Require all granted
        Order Allow,Deny
        Allow from All

        FallbackResource /index.php
    </Directory>

    # optionally disable the fallback resource for the asset directories
    # which will allow Apache to return a 404 error when files are
    # not found instead of passing the request to Symfony
    <Directory /var/www/project/public/bundles>
        FallbackResource disabled
    </Directory>
    ErrorLog /var/log/apache2/project_error.log
    CustomLog /var/log/apache2/project_access.log combined

    # optionally set the value of the environment variables used in the application
    #SetEnv APP_ENV prod
    #SetEnv APP_SECRET <app-secret-id>
    #SetEnv ...
</VirtualHost>
```

Step number 1 makes the application read the environment variables from the
server, instead of the `.env` file.

# Sending reminders to users
The bot has a command that allows sending notifications to the users whenever
their tasks' or milestones' deadlines are close. That can be done issuing the
following command:

`php bin/console app:mt-send-reminders`

The command can accept the `--milestones`, `--today` and `--task` options.
Check what each of those options do by issuing the command with the `--help`
option.

In order to automate the notification dispatching, you can use a `cron` job for
the job. The following example sets a `cron` job to dispatch the notifications
every day at 2AM.

* `0 2 * * * /usr/bin/php {PATH_TO_THE_PROJECT}/bot/bin/console app:mt-send-reminders`

[CronHowto][ubuntu-docs-cron-howto] and [crontab.guru][crontab-guru-page] can
help you create `cron` jobs that may suit your needs better.

# Running the tests
## Coding guidelines
The code of this project follows the rules from the `@PhpCsFixer` rule set. In
order to check whether the code you wrote follows the rules or not, you just
have to run:

`vendor/bin/php-cs-fixer fix --config=.php_cs.dist --dry-run --verbose --diff`

## PHPStan
> [PHPStan](phpstan-github) focuses on finding errors in your code without
actually running it. It catches whole classes of bugs even before you write
tests for the code. It moves PHP closer to compiled languages in the sense that
the correctness of each line of the code can be checked before you run the
actual line.

Run `PHPStan` with:

`vendor/bin/phpstan analyse`

## Code tests
In order to run tests you have to make the following steps:

1. Prepare the database with
    `php bin/console doctrine:migrations:migrate --no-interaction`
2. Copy `.env.test` to `.env.test.local` with `cp .env.test .env.test.local`.
3. Set the testing database parameters in the `.env.test.local` file.
4. Run `phpunit` by issuing the following command from the project root:
    `bin/phpunit`

# Docker
## Development environment
This will set up a quick development environment will all the needed
dependencies met. The web application will be exposed on port `8000`, and
`MariaDB` will be accessible on port `8500`. `Xdebug` is enabled, and works on
the regular `9000` port.

1. Do `docker-compose up` —it will build the images and start the containers—.
2. Create `cache` and `log` directories inside `var/`, and give them `777`
    permissions<sup>1</sup>.
3. Install Composer's dependencies:
    `docker-compose exec php-fpm composer install`
4. Set up the environment variables in a local `env` file. Do
    `cp .env .env.local` and fill it with the parameters that work for
    you<sup>2</sup>.
5. Set up the database:
    `docker-compose exec php-fpm php bin/console doctrine:migrations:migrate`

**1:** The reasons for giving such loose permissions are:
1. Both the web server and the host user will need write permissions to the
    directories —e.g. when running a php bin/console command—.
2. This setup is supposed to be used in a development environment.

**2:** The database connection parameters are specified in the
`docker-compose.override.yml` file. The `host` of the database must be set to
be the same as the service name in the `docker-compose.override.yml` file:
`mariadb`.

[gantt-project-page]: https://www.ganttproject.biz/
[apache-docs-env]: https://httpd.apache.org/docs/2.4/mod/mod_env.html#setenv
[wiki-front-controller]: https://en.wikipedia.org/wiki/Front_controller
[symfony-docs-apache-prod]: https://symfony.com/doc/current/setup/web_server_configuration.html#apache-with-mod-php-php-cgi
[ubuntu-docs-cron-howto]: https://help.ubuntu.com/community/CronHowto
[crontab-guru-page]: https://crontab.guru/
