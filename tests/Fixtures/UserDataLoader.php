<?php

declare(strict_types=1);

namespace IkastenBot\Tests\Fixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use IkastenBot\Entity\User;

class UserDataLoader extends AbstractFixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $user = new User();

        $fixedDate = new \DateTime('2021-01-01');

        $user = new User();
        $user->setId(12345);
        $user->setBot(false);
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setUsername('TestUser');
        $user->setLanguageCode('en');
        $user->setCreatedAt($fixedDate);
        $user->setUpdatedAt($fixedDate);
        $user->setLanguage('en');

        $manager->persist($user);
        $manager->flush();

        $this->addReference('user', $user);
    }

    public function getOrder()
    {
        return 1;
    }
}
