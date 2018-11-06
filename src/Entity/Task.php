<?php

declare(strict_types=1);

namespace MikelAlejoBR\TelegramBotGanttProject\Entity;

/**
 * Class that represents the tasks of a GanttProject project
 */
class Task
{
    /**
     * Start time of the task
     *
     * @var \DateTime
     */
    protected $start;

    /**
     * End time of the task
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
     * Get start time of the task
     *
     * @return \DateTime
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Set start time of the task
     *
     * @param \DateTime  $start  Start time of the task
     *
     * @return self
     */
    public function setStart(\DateTime $start)
    {
        $this->start = $start;

        return $this;
    }

    /**
     * Get end time of the task
     *
     * @return \DateTime
     */
    public function getFinish()
    {
        return $this->finish;
    }

    /**
     * Set end time of the task
     *
     * @param  \DateTime  $finish  End time of the task
     *
     * @return  self
     */
    public function setFinish(\DateTime $finish)
    {
        $this->finish = $finish;

        return $this;
    }

    /**
     * Get check if the task is a milestone
     *
     * @return bool
     */
    public function getMilestone()
    {
        return $this->milestone;
    }

    /**
     * Set check if the task is a milestone
     *
     * @param string $milestone   Check if the task is a milestone.
     *                            The value is casted to bool.
     *
     * @return self
     */
    public function setMilestone(string $milestone)
    {
        $this->milestone = (bool)$milestone;

        return $this;
    }
}
