<?php

declare(strict_types=1);

namespace MikelAlejoBR\TelegramBotGanttProject\Entity;

/**
 * Class that represents the milestones of a GanttProject project
 *
 * @Entity @Table(name="milestone")
 */
class Milestone
{
    /**
     * Id of the milestone
     *
     * @Id @Column(type="integer") @GeneratedValue
     * @var int
     */
    protected $id;

    /**
     * Id of the user associated to the milestone
     *
     * @Column(type="bigint")
     * @var string
     */
    protected $user_id;

    /**
     * Name of the milestone
     *
     * @Column(type="string", name="milestone_name")
     * @var string
     */
    protected $name;

    /**
     * Start time of the milestone
     *
     * @Column(type="datetime", name="milestone_start_date")
     * @var \DateTime
     */
    protected $start;

    /**
     * End time of the milestone
     *
     * @Column(type="datetime", name="milestone_finish_date")
     * @var \DateTime
     */
    protected $finish;

    public function __construct()
    {
    }

    /**
     * Get id of the milestone
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set id of the milestone
     *
     * @param int $id Id of the milestone
     *
     * @return self
     */
    public function setId(int $id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id of the user associated to the milestone
     *
     * @return string
     */
    public function getUser_id(): string
    {
        return $this->user_id;
    }

    /**
     * Set id of the user associated to the milestone
     *
     * @param string $user_id Id of the user associated to the milestone
     *
     * @return self
     */
    public function setUser_id(string $user_id)
    {
        $this->user_id = $user_id;

        return $this;
    }

    /**
     * Get name of the milestone
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set name of the milestone
     *
     * @param string $name Name of the milestone
     *
     * @return self
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get start time of the milestone
     *
     * @return \DateTime
     */
    public function getStart(): \DateTime
    {
        return $this->start;
    }

    /**
     * Set start time of the milestone
     *
     * @param \DateTime $start Start time of the milestone
     *
     * @return self
     */
    public function setStart(\DateTime $start)
    {
        $this->start = $start;

        return $this;
    }

    /**
     * Get end time of the milestone
     *
     * @return \DateTime
     */
    public function getFinish(): \DateTime
    {
        return $this->finish;
    }

    /**
     * Set end time of the milestone
     *
     * @param \DateTime $finish End time of the milestone
     *
     * @return self
     */
    public function setFinish(\DateTime $finish)
    {
        $this->finish = $finish;

        return $this;
    }
}
