<?php

declare(strict_types=1);

namespace IkastenBot\Tests\Fixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use IkastenBot\Entity\GanttProject;

class GanttProjectDataLoader extends AbstractFixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $ganttProject = new GanttProject();
        $ganttProject->setFileName('TwelveTasks.gan');
        $ganttProject->setVersion(1);
        $ganttProject->setUser(
            $this->getReference('user')
        );

        $manager->persist($ganttProject);
        $manager->flush();

        $this->addReference('ganttProject', $ganttProject);
    }

    public function getOrder()
    {
        return 2;
    }
}
