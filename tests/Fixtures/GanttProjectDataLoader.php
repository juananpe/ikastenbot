<?php

declare(strict_types=1);

namespace IkastenBot\Tests\Fixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use IkastenBot\Entity\GanttProject;

class GanttProjectDataLoader extends AbstractFixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $ganttProject = new GanttProject();
        $ganttProject->setFileName('Test.gan');
        $ganttProject->setVersion(1);
        $ganttProject->setUser(
            $this->getReference('user')
        );

        $manager->persist($ganttProject);
        $manager->flush();
    }

    public function getOrder()
    {
        return 2;
    }
}
