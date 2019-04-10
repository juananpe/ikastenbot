<?php

declare(strict_types=1);

namespace App\Command;

use Longman\TelegramBot\Request;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BroadcastCommand extends Command
{
    /**
     * Command's name.
     *
     * @var string
     */
    protected static $defaultName = 'app:broadcast';

    protected function configure()
    {
        $this
            ->setDescription('Broadcast')
            ->addOption(
                'convocatorias',
                null,
                InputOption::VALUE_NONE,
                'Convocatorias'
            )
            ->addOption(
                'enviar',
                null,
                InputOption::VALUE_NONE,
                'Enviar'
            )
            ->addOption(
                'especial',
                null,
                InputOption::VALUE_NONE,
                'Especial'
            )
        ;
    }

    protected function insertDates(string $text, string $dates)
    {
        // $text = str_replace("##", $dates, $text);
        return $text.' '.$dates;
    }

    protected function convocatorias(\PDO $pdoInstance)
    {
        $pdo = $pdoInstance;

        $query = 'select id,`to`,what from dates where `to`=CURDATE() + INTERVAL 7 DAY and notification_prep=FALSE ';
        $sth = $pdo->prepare($query);
        $sth->execute();
        $fecha = $sth->fetchAll(\PDO::FETCH_ASSOC);

        $query = 'select id,`language` from `user`';
        $sth = $pdo->prepare($query);
        $sth->execute();
        $users = $sth->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($fecha as $convo) {
            switch ($convo['what']) {
                case 'matricula':
                    $id_msg = 2;

                    break;
                case 'secretaria':
                    $id_msg = 3;

                    break;
                case 'solicitud defensa':
                    $id_msg = 4;

                    break;
                case 'visto bueno':
                    $id_msg = 5;

                    break;
                case 'defensa':
                    $id_msg = 6;

                    break;
            }
            $query = "select es,eus from system_message where id=${id_msg}";
            $sth = $pdo->prepare($query);
            $sth->execute();
            $msg = $sth->fetch(\PDO::FETCH_ASSOC);

            $msg_date['es'] = $this->insertDates($msg['es'], $convo['to']);
            $msg_date['eus'] = $this->insertDates($msg['eus'], $convo['to']);

            foreach ($users as $user) {
                $user_id = $user['id'];
                $lang = $user['language'];
                if (array_key_exists($lang, $msg_date)) {
                    $user_msg = $msg_date[$lang];
                    $query = "INSERT INTO notification (user_id,`date`,message,sent) VALUES (${user_id},CURDATE(),'${user_msg}',0); ";
                    $sth = $pdo->prepare($query);
                    $sth->execute();
                }
            }

            $id = $convo['id'];
            $query = "Update `dates` SET notification_prep=true where id=${id}";
            $sth = $pdo->prepare($query);
            $sth->execute();
        }
    }

    protected function enviar(\PDO $pdoInstance)
    {
        $pdo = $pdoInstance;

        $query = 'SELECT id,user_id,message FROM notification where date=curdate() and sent=false;';
        //$sth = $pdo->prepare($query);
        //$sth->execute();
        $sth = $pdo->query($query);
        $notifs = $sth->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($notifs as $ntf) {
            $data = [
                'chat_id' => $ntf['user_id'],
                'text' => $ntf['message'],
            ];
            echo $ntf['user_id'];
            echo $ntf['message'];

            $result = Request::sendMessage($data);

            if ($result->isOk()) {
                $id = $ntf['id'];
                $query = "Update notification SET sent=true where id=${id};";
                $sth = $pdo->prepare($query);
                $sth->execute();
            }
        }
    }

    protected function especial(\PDO $pdoInstance)
    {
        $pdo = $pdoInstance;

        $query = 'select id,es,eus,`date` from special_notification where `date`<=CURDATE() + INTERVAL 3 DAY and notification_prep=FALSE ';
        $sth = $pdo->prepare($query);
        $sth->execute();
        $nots = $sth->fetchAll(\PDO::FETCH_ASSOC);

        $query = 'select id,`language` from `user`';
        $sth = $pdo->prepare($query);
        $sth->execute();
        $users = $sth->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($nots as $not) {
            $msg_date['es'] = $not['es'];
            $msg_date['eus'] = $not['eus'];

            foreach ($users as $user) {
                $user_id = $user['id'];
                $lang = $user['language'];
                $date = $not['date'];
                if (array_key_exists($lang, $msg_date)) {
                    $user_msg = $msg_date[$lang];
                    $query = "INSERT INTO notification (user_id,`date`,message,sent) VALUES (${user_id},'${date}','${user_msg}',0); ";
                    $sth = $pdo->prepare($query);
                    $sth->execute();
                }
            }
            $id = $not['id'];
            $query = "Update special_notification SET notification_prep=true where id=${id};";
            $sth = $pdo->prepare($query);
            $sth->execute();
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Get the options
        $convocatorias = $input->getOption('convocatorias');
        $enviar = $input->getOption('enviar');
        $especial = $input->getOption('especial');

        if (!($convocatorias || $enviar || $especial)) {
            $output->writeln('Nothing to do.');

            return;
        }

        $charset = getenv('MYSQL_CHARSET');
        $host = getenv('MYSQL_HOST');
        $db = getenv('MYSQL_DATABASE_NAME');
        $user = getenv('MYSQL_USERNAME');
        $pass = getenv('MYSQL_USER_PASSWORD');

        $dsn = "mysql:host=${host};dbname=${db};charset=${charset}";
        $opt = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $pdoInstance = new \PDO($dsn, $user, $pass, $opt);

        if ($convocatorias) {
            $this->convocatorias($pdoInstance);
        }

        if ($enviar) {
            $this->enviar($pdoInstance);
        }

        if ($especial) {
            $this->especial($pdoInstance);
        }
    }
}
