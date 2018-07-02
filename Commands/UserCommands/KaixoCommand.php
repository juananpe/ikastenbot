<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;

/**
 * User "/kaixo" command
 *
 * Simply echo the input back to the user.
 */
class KaixoCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'kaixo';

    /**
     * @var string
     */
    protected $description = 'kaixo kaixo';

    /**
     * @var string
     */
    protected $usage = '/kaixo';

    /**
     * @var string
     */
    protected $version = '1.1.0';

    /**
     * Command execute method
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();

        $data = [
            'chat_id' => $chat_id,
            'text'    => 'Kaixo',
        ];

        return Request::sendMessage($data);
    }
}
