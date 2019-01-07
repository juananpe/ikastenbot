<?php

declare(strict_types=1);

namespace IkastenBot\Entity;

/**
 * Class that represents the tasks of a GanttProject project
 *
 * @Entity(repositoryClass="IkastenBot\Repository\TaskRepository")
 * @Table(name="task")
 */
class Task
{
    /**
     * Id of the task
     *
     * @Id @Column(type="integer") @GeneratedValue
     * @var int
     */
    protected $id;

    /**
     * Id of the chat to which the task is associated
     *
     * @Column(type="bigint")
     * @var string
     */
    protected $chat_id;

    /**
     * Name of the task
     *
     * @Column(type="string", name="task_name")
     * @var string
     */
    protected $name;

    /**
     * Date of the task
     *
     * @Column(type="datetime", name="task_date")
     * @var \DateTime
     */
    protected $date;

    /**
     * Is the task a milestone
     *
     * @Column(type="boolean", name="task_isMilestone")
     * @var bool
     */
    protected $isMilestone;

    /**
     * Duration of the task
     *
     * @Column(type="integer", name="task_duration")
     * @var int
     */
    protected $duration;

    public function __construct()
    {
    }

    /**
     * Get id of the task
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set id of the task
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
     * Get id of the chat to which the task is associated
     *
     * @return string
     */
    public function getChat_id(): string
    {
        return $this->chat_id;
    }

    /**
     * Set id of the chat to which the task is associated
     *
     * @param   string $chat_id  Id of the chat to which the task is associated
     *
     * @return  self
     */
    public function setChat_id(string $chat_id)
    {
        $this->chat_id = $chat_id;

        return $this;
    }

    /**
     * Get name of the task
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set name of the task
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
     * Get date of the task
     *
     * @return \DateTime
     */
    public function getDate(): \DateTime
    {
        return $this->date;
    }

    /**
     * Set date of the task
     *
     * @param  \DateTime  $date  Date of the task
     *
     * @return  self
     */
    public function setDate(\DateTime $date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get is the task a milestone
     */
    public function getIsMilestone()
    {
        return $this->isMilestone;
    }

    /**
     * Set is the task a milestone
     *
     * @return  self
     */
    public function setIsMilestone($isMilestone)
    {
        $this->isMilestone = $isMilestone;

        return $this;
    }

    /**
     * Get duration of the task
     *
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Set duration of the task
     *
     * @param int $duration  Duration of the task
     *
     * @return self
     */
    public function setDuration(int $duration)
    {
        $this->duration = $duration;

        return $this;
    }
}
