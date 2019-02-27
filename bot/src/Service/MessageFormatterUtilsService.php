<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Task;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class MessageFormatterUtilsService
{
    /**
     * Twig templating engine.
     *
     * @var Environment
     */
    protected $twig;

    public function __construct(Environment $twig = null)
    {
        if (\is_null($twig)) {
            $loader = new FilesystemLoader(PROJECT_ROOT.'/templates/');
            $this->twig = new Environment($loader, [
                'cache' => PROJECT_ROOT.'/var/cache/Twig',
            ]);
        } else {
            $this->twig = $twig;
        }
    }

    /**
     * Append twig file to the given text. It appends a new line as well.
     *
     * @param string &$text        Text to which the file contents will
     *                             be appended
     * @param string $twigFilePath Path of the twig file
     */
    public function appendTwigFile(string &$text, string $twigFilePath): void
    {
        $text .= $this->twig->render($twigFilePath);
        $text .= PHP_EOL;
    }

    /**
     * Append twig file to the given text rendering the specified parameters if
     * they apply. It appends a new line as well.
     *
     * @param string &$text        Text to which the file contents will
     *                             be appended
     * @param string $twigFilePath Path of the twig file
     * @param array  $parameters   The parameters to be included in the
     *                             rendering
     */
    public function appendTwigFileWithParameters(string &$text, string $twigFilePath, array $parameters): void
    {
        $text .= $this->twig->render($twigFilePath, $parameters);
        $text .= PHP_EOL;
    }

    /**
     * Append a task to the given text. It appends a new line after each
     * task as well.
     *
     * @param string &$text       Text to which the milestone will be appended
     * @param Task   $task        The task
     * @param string $daysLeft    Days left to reach the milestone
     * @param bool   $isMilestone The task is a milestone
     */
    public function appendTask(string &$text, Task $task, string $daysLeft = null, bool $isMilestone = false): void
    {
        $parameters['task'] = $task;

        if (!\is_null($daysLeft)) {
            $parameters['daysLeft'] = $daysLeft;
        }

        if ($isMilestone) {
            $text .= $this->twig->render('notifications/milestone/milestone.twig', $parameters);
        } else {
            $text .= $this->twig->render('notifications/task/task.twig', $parameters);
        }

        $text .= PHP_EOL;
    }

    /**
     * Creates an inline keyboard with three buttons: 'Yes', 'No, let's delay
     * it' and 'Disable this <element>'s notifications'. The <element> is
     * replaced with the string 'milestone' or 'task' depending on the passed
     * flag.
     *
     * @param int  $elementId   The id of the element
     * @param bool $isMilestone Is the element a milestone?
     *
     * @return InlineKeyboard
     */
    public function createThreeOptionInlineKeyboard($elementId, $isMilestone): InlineKeyboard
    {
        $element = ($isMilestone) ? 'milestone' : 'task';

        return new InlineKeyboard(
            [
                [
                    'text' => '💪 Yes',
                    'callback_data' => 'affirmative_noop',
                ],
                [
                    'text' => '🥵 No, let\'s delay it',
                    'callback_data' => '/delaytask '.$elementId,
                ],
            ],
            [
                [
                    'text' => '💤 Disable this '.$element.'\'s notifications',
                    'callback_data' => '/disablenotifications '.$elementId,
                ],
            ]
        );
    }
}
