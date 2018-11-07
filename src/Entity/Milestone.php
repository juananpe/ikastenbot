<?php

declare(strict_types=1);

namespace MikelAlejoBR\TelegramBotGanttProject\Entity;

/**
 * Class that represents the milestones of a GanttProject project
 */
class Milestone
{
    /**
     * Name of the milestone
     *
     * @var string
     */
    protected $name;

    /**
     * Start time of the milestone
     *
     * @var \DateTime
     */
    protected $start;

    /**
     * End time of the milestone
     *
     * @var \DateTime
     */
    protected $finish;

    /**
     * Check if the task is a milestone
     *
     * @var bool
     */
    protected $milestone;

    public function __construct()
    {
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

    /**
     * Get check if the milestone is a milestone
     *
     * @return bool
     */
    public function getMilestone(): bool
    {
        return $this->milestone;
    }

    /**
     * Set check if the milestone is a milestone
     *
     * @param string $milestone Check if the milestone is a milestone.
     *                          The value is casted to bool.
     *
     * @return self
     */
    public function setMilestone(string $milestone)
    {
        $this->milestone = (bool)$milestone;

        return $this;
    }
}
