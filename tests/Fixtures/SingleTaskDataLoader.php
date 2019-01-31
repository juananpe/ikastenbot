<?php

declare(strict_types=1);

namespace App\Tests\Fixtures;

use App\Entity\Task;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class SingleTaskDataLoader extends AbstractFixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $fixedDate = new \DateTime('2021-01-01');

        $task = new Task();
        $task->setGanId(1);
        $task->setChat_id('12345');
        $task->setName('Test task');
        $task->setDate($fixedDate);
        $task->setIsMilestone(false);
        $task->setDuration(3);
        $task->setNotify(true);
        $task->setGanttProject(
            $this->getReference('ganttProject')
        );

        $manager->persist($task);
        $manager->flush();
    }

    public function getOrder()
    {
        return 3;
    }
}
