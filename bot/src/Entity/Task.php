<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class that represents the tasks of a GanttProject project.
 *
 * @ORM\Entity(repositoryClass="App\Repository\TaskRepository")
 * @ORM\Table(name="task")
 */
class Task
{
    /**
     * Id of the task.
     *
     * @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue
     *
     * @var int
     */
    protected $id;

    /**
     * The id of the task in the .gan file.
     *
     * @ORM\Column(type="integer", name="gan_id")
     *
     * @var int
     */
    protected $ganId;

    /**
     * Id of the chat to which the task is associated.
     *
     * @ORM\Column(type="bigint")
     *
     * @var string
     */
    protected $chat_id;

    /**
     * Name of the task.
     *
     * @ORM\Column(type="string", name="task_name")
     *
     * @var string
     */
    protected $name;

    /**
     * Date of the task.
     *
     * @ORM\Column(type="datetime", name="task_date")
     *
     * @var \DateTime
     */
    protected $date;

    /**
     * Is the task a milestone.
     *
     * @ORM\Column(type="boolean", name="task_isMilestone")
     *
     * @var bool
     */
    protected $isMilestone;

    /**
     * Duration of the task.
     *
     * @ORM\Column(type="integer", name="task_duration")
     *
     * @var int
     */
    protected $duration;

    /**
     * Should the user be notified about the task.
     *
     * @ORM\Column(type="boolean", name="notify")
     *
     * @var bool
     */
    protected $notify;

    /**
     * Associated Gantt Project.
     *
     * @ORM\ManyToOne(targetEntity="GanttProject", inversedBy="tasks", cascade={"persist"})
     * @ORM\JoinColumn(name="ganttproject_id", referencedColumnName="id")
     *
     * @var GanttProject
     */
    protected $ganttProject;

    public function __construct()
    {
    }

    /**
     * Delays date for the given amount of days.
     *
     * @param int $days The amount of days the date is to be delayed
     *
     * @return self
     */
    public function delayDate(int $days): self
    {
        /**
         * The following link explains why the object is cloned instead of
         * using ->add directly with $this->date.
         *
         * @see https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/cookbook/working-with-datetime.html
         */
        $interval = 'P'.$days.'D';
        $dateInterval = new \DateInterval($interval);

        $this->date = clone $this->date;
        $this->date->add($dateInterval);

        return $this;
    }

    /**
     * Get id of the task.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set id of the task.
     *
     * @param int $id Id of the task
     *
     * @return self
     */
    public function setId(int $id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the id of the task in the .gan file.
     *
     * @return int
     */
    public function getGanId()
    {
        return $this->ganId;
    }

    /**
     * Set the id of the task in the .gan file.
     *
     * @param int $ganId The id of the task in the .gan file
     *
     * @return self
     */
    public function setGanId(int $ganId)
    {
        $this->ganId = $ganId;

        return $this;
    }

    /**
     * Get id of the chat to which the task is associated.
     *
     * @return string
     */
    public function getChat_id(): string
    {
        return $this->chat_id;
    }

    /**
     * Set id of the chat to which the task is associated.
     *
     * @param string $chat_id Id of the chat to which the task is associated
     *
     * @return self
     */
    public function setChat_id(string $chat_id)
    {
        $this->chat_id = $chat_id;

        return $this;
    }

    /**
     * Get name of the task.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set name of the task.
     *
     * @param string $name Name of the task
     *
     * @return self
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get date of the task.
     *
     * @return \DateTime
     */
    public function getDate(): \DateTime
    {
        return $this->date;
    }

    /**
     * Set date of the task.
     *
     * @param \DateTime $date Date of the task
     *
     * @return self
     */
    public function setDate(\DateTime $date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get is the task a milestone.
     */
    public function getIsMilestone()
    {
        return $this->isMilestone;
    }

    /**
     * Set is the task a milestone.
     *
     * @param mixed $isMilestone
     *
     * @return self
     */
    public function setIsMilestone($isMilestone)
    {
        $this->isMilestone = $isMilestone;

        return $this;
    }

    /**
     * Get duration of the task.
     *
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Set duration of the task.
     *
     * @param int $duration Duration of the task
     *
     * @return self
     */
    public function setDuration(int $duration)
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * Get associated GanttProject.
     *
     * @return GanttProject
     */
    public function getGanttProject(): GanttProject
    {
        return $this->ganttProject;
    }

    /**
     * Set associated GanttProject.
     *
     * @param GanttProject $ganttProject Associated GanttProject
     *
     * @return self
     */
    public function setGanttProject(GanttProject $ganttProject): self
    {
        $this->ganttProject = $ganttProject;

        return $this;
    }

    /**
     * Get the notify flag.
     *
     * @return bool
     */
    public function getNotify(): bool
    {
        return $this->notify;
    }

    /**
     * Set the notify flag.
     *
     * @param bool $notify Notify flag
     *
     * @return self
     */
    public function setNotify(bool $notify): self
    {
        $this->notify = $notify;

        return $this;
    }
}
