<?php

declare(strict_types=1);

namespace MikelAlejoBR\TelegramBotGanttProject\Service;

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Telegram;
use MikelAlejoBR\TelegramBotGanttProject\Entity\Milestone;
use Symfony\Component\Dotenv\Dotenv;

class MilestoneReminderService
{
    /**
     * The function to be used in order to calculate the difference between
     * dates
     */
    const DATEDIFFFUNCTION = 'DATE_DIFF(m.start, CURRENT_DATE())';

    /**
     * Entity manager
     *
     * @var EntityManager
     */
    protected $em;

    /**
     * Constructor. Reads the database parameters from the environment variables
     * and creates a database connection.
     *
     * @param boolean $isDevMode Initialize Doctrine in development mode
     */
    public function __construct(bool $isDevMode = false)
    {
        $dotenv = new Dotenv();
        $dotenv->load(__DIR__.'/../../.env');

        $config = Setup::createAnnotationMetadataConfiguration(array(__DIR__."/../Entity/"), $isDevMode);

        // database configuration parameters
        $connectionParams = array(
            'driver'   => 'pdo_mysql',
            'user'     => getenv('MYSQL_USERNAME'),
            'password' => getenv('MYSQL_USER_PASSWORD'),
            'dbname'   => getenv('MYSQL_DATABASE_NAME'),
        );

        $this->em = EntityManager::create($connectionParams, $config);
    }

    /**
     * Finds milestones that are to be reached in 30, 15, 3 or 2 days.
     *
     * @return Milestone[] Array of Milestones
     */
    private function findMilestonesToNotifyAbout()
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('m', self::DATEDIFFFUNCTION)
            ->from(Milestone::class, 'm')
            ->where(self::DATEDIFFFUNCTION . ' = 30')
            ->orWhere(self::DATEDIFFFUNCTION . ' = 15')
            ->orWhere(self::DATEDIFFFUNCTION . ' BETWEEN 2 AND 3')
            ->orderBy(self::DATEDIFFFUNCTION);

        return $qb->getQuery()->getResult();
    }

    /**
     * Finds milestones that are to be reached today.
     *
     * @return mixed[Milestone][int] Nested array of Milestones and their
     *                               corresponding days to be reached.
     */
    private function findMilestonesReachToday()
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('m')
            ->from(Milestone::class, 'm')
            ->where(self::DATEDIFFFUNCTION . ' = 0');

        return $qb->getQuery()->getResult();
    }

    /**
     * Notify users about the milestones they should reach today according to
     * their planning.
     */
    private function notifyUsersMilestonesToday()
    {
        $results = $this->findMilestonesReachToday();
        foreach ($results as $milestone) {
            $text = 'This is a reminder to inform you that your milestone '
                . '<b>' . $milestone->getName() . '</b> should be reached '
                . '<b>today</b> according to your planning. The details of the '
                . 'milestone are: ' . PHP_EOL . PHP_EOL
                . '<b>Milestone name:</b> ' . $milestone->getName() . PHP_EOL
                . '<b>Start date:</b> ' . $milestone->getStart()->format('Y-m-d H:i:s') . PHP_EOL
                . '<b>Finish date:</b> ' . $milestone->getFinish()->format('Y-m-d H:i:s')
            ;

            $data = [
                'chat_id'       => $milestone->getUser_id(),
                'parse_mode'    => 'HTML',
                'text'          => $text
            ];
            Request::sendMessage($data);
        }
    }

    /**
     * Notify users about milestones that are close to be reached according
     * to their planning.
     */
    private function notifyUsersMilestonesClose()
    {
        $results = $this->findMilestonesToNotifyAbout();
        $text = 'This is a reminder to inform you that the following '
            . 'milestones are relatively close to be reached. The details of '
            . 'these milestones are:' . PHP_EOL . PHP_EOL;

        foreach ($results as $row) {
            $milestone = $row[0];
            $daysLeft = $row[1];

            $text .= '<b>Milestone name:</b> ' . $milestone->getName() . PHP_EOL;
            $text .= '<b>Start date:</b> ' . $milestone->getStart()->format('Y-m-d H:i:s') . PHP_EOL;
            $text .= '<b>Finish date:</b> ' . $milestone->getFinish()->format('Y-m-d H:i:s') . PHP_EOL;
            $text .= 'You have <b>' . $daysLeft . ' days</b> to reach this milestone!' . PHP_EOL;
            $text .= PHP_EOL;

            $data = [
                'chat_id'       => $milestone->getUser_id(),
                'parse_mode'    => 'HTML',
                'text'          => $text
            ];
            Request::sendMessage($data);
        }
    }

    /**
     * Notify users about their milestones that are as close as 30, 15, 3, 2 or
     * 1 days.
     */
    public function notifyUsers()
    {
        $telegramBot = new Telegram(getenv('TELEGRAM_BOT_API_KEY'), getenv('TELEGRAM_BOT_USERNAME'));

        $this->notifyUsersMilestonesToday();
        $this->notifyUsersMilestonesClose();
    }
}
