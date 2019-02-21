<?php

declare(strict_types=1);

namespace App\Tests\Fixtures;

use App\Entity\Task;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class TaskDataLoader extends AbstractFixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        // Get today's date
        $today = new \DateTime();

        // Insert three tasks to be reached today
        for ($i = 0; $i < 3; ++$i) {
            $task = new Task();

            $task->setGanId(0);
            $task->setChat_id('12345');
            $task->setName('Task T');
            $task->setDate($today);
            $task->setIsMilestone(false);
            $task->setDuration(3);
            $task->setNotify(true);
            $task->setGanttProject(
                $this->getReference('ganttProject')
            );

            $manager->persist($task);
        }

        /*
         * Insert three tasks to be reached today, but that the user
         * specifically told no to be notified about
         */
        for ($i = 0; $i < 3; ++$i) {
            $task = new Task();

            $task->setGanId(0);
            $task->setChat_id('12345');
            $task->setName('Task T');
            $task->setDate($today);
            $task->setIsMilestone(false);
            $task->setDuration(3);
            $task->setNotify(false);
            $task->setGanttProject(
                $this->getReference('ganttProject')
            );

            $manager->persist($task);
        }

        // Insert one milestone to be reached today
        $milestone = new Task();
        $milestone->setGanId(0);
        $milestone->setChat_id('12345');
        $milestone->setName('Milestone T');
        $milestone->setDate($today);
        $milestone->setIsMilestone(true);
        $milestone->setDuration(0);
        $milestone->setNotify(true);
        $milestone->setGanttProject(
            $this->getReference('ganttProject')
        );

        $manager->persist($milestone);

        /**
         * Insert tasks to be reminded of, and three tasks that should
         * not be fetched in the queries.
         */
        $plusDays = [
            'P3D',
            'P3D',
            'P15D',
            'P30D',
            'P100D',
        ];

        $j = 0;
        for ($i = 1; $i <= 15; ++$i) {
            $today = new \DateTime();
            $todayPlusDays = $today->add(new \DateInterval($plusDays[$j]));

            $task = new Task();
            $task->setGanId(0);
            $task->setChat_id('12345');
            $task->setName($plusDays[$j]);
            $task->setDate($todayPlusDays);
            $task->setIsMilestone(false);
            $task->setDuration(3);
            $task->setNotify(true);
            $task->setGanttProject(
                $this->getReference('ganttProject')
            );

            $manager->persist($task);

            if (0 === $i % 3) {
                $milestone = new Task();
                $milestone->setGanId(0);
                $milestone->setChat_id('12345');
                $milestone->setName($plusDays[$j]);
                $milestone->setDate($todayPlusDays);
                $milestone->setIsMilestone(true);
                $milestone->setDuration(0);
                $milestone->setNotify(true);
                $milestone->setGanttProject(
                    $this->getReference('ganttProject')
                );

                $manager->persist($milestone);

                ++$j;
            }
        }

        $manager->flush();
    }

    public function getOrder()
    {
        return 3;
    }
}
