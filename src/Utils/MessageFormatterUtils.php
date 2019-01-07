<?php

declare(strict_types=1);

namespace IkastenBot\Utils;

use IkastenBot\Entity\Milestone;
use IkastenBot\Entity\Task;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class MessageFormatterUtils
{
    /**
     * Twig templating engine
     *
     * @var Environment
     */
    protected $twig;

    public function __construct(Environment $twig = null)
    {
        if (\is_null($twig)) {
            $loader = new FilesystemLoader(PROJECT_ROOT . '/templates/');
            $this->twig = new Environment($loader, array(
                'cache' => PROJECT_ROOT . '/var/cache/',
            ));
        } else {
            $this->twig = $twig;
        }
    }

    /**
     * Append twig file to the given text. It appends a new line as well.
     *
     * @param string &$text         Text to which the file contents will
     *                              be appended
     * @param string $twigFilePath  Path of the twig file
     *
     * @return void
     */
    public function appendTwigFile(string &$text, string $twigFilePath): void
    {
        $text .= $this->twig->render($twigFilePath);
        $text .= PHP_EOL;
    }

    /**
     * Append a milestone to the given text. It appends a new line after each
     * milestone as well.
     *
     * @param string    &$text      Text to which the milestone will be appended
     * @param Milestone $milestone  The milestone
     * @param string    $daysLeft   Days left to reach the milestone
     *
     * @return void
     */
    public function appendMilestone(string &$text, Milestone $milestone, string $daysLeft = null): void
    {
        $parameters = [
            'milestone' => $milestone
        ];

        if (!\is_null($daysLeft)) {
            $parameters['daysLeft'] = $daysLeft;
        }

        $text .= $this->twig->render('notifications/milestone.twig', $parameters);
        $text .= PHP_EOL;
    }

    /**
     * Append a task to the given text. It appends a new line after each
     * task as well.
     *
     * @param string    &$text      Text to which the milestone will be appended
     * @param Task      $task       The milestone
     * @param string    $daysLeft   Days left to reach the milestone
     *
     * @return void
     */
    public function appendTask(string &$text, Task $task, string $daysLeft = null): void
    {
        $parameters = [
            'task' => $task
        ];

        if (!\is_null($daysLeft)) {
            $parameters['daysLeft'] = $daysLeft;
        }

        $text .= $this->twig->render('notifications/task/task.twig', $parameters);
        $text .= PHP_EOL;
    }
}
