# php-telegram-bot-gantt

A take on importing and processing [GanttProject's][1] milestones with the goal
of creating reminders for users.

[1]: https://www.ganttproject.biz/

# Configuration
1. Substitute the contents of `.env.dist` with your own data.
2. Rename `.env.dist` to `.env`.
3. Import `setUpDatabase.sql` file with
    `mysql -u USER -p DATABASE < setUpDatabase.sql`.

# Notes
* When using conversations, make sure the names don't contain spaces, as
    php-telegram bot seems not to be able to keep up with the conversation if
    such thing happens.
