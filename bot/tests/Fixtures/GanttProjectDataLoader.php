<?php

declare(strict_types=1);

namespace App\Tests\Fixtures;

use App\Entity\GanttProject;
use App\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class GanttProjectDataLoader extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * The filename of the GanttProject.
     *
     * @var string
     */
    protected $filename;

    /**
     * The version of the GanttProject.
     *
     * @var int
     */
    protected $version;

    /**
     * The user to which the GanttProject is bound to.
     *
     * @var User
     */
    protected $user;

    public function __construct(string $filename = null, int $version = null, User $user = null)
    {
        $this->filename = \is_null($filename) ? 'TwelveTasks.gan' : $filename;
        $this->version = \is_null($version) ? 1 : $version;
        /*
         * The reference repository is still not up at this point, and
         * therefore trying to 'getReference' here throws a 'call to a member
         * function on null'.
         */
        $this->user = $user;
    }

    public function load(ObjectManager $manager)
    {
        $ganttProject = new GanttProject();
        $ganttProject->setFileName($this->filename);
        $ganttProject->setVersion($this->version);
        $ganttProject->setUser(
            \is_null($this->user) ? $this->getReference('user') : $this->user
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
