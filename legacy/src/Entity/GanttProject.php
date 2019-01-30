<?php

declare(strict_types=1);

namespace IkastenBot\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Represents a GanttProject entity.
 *
 * @Entity(repositoryClass="IkastenBot\Repository\GanttProjectRepository")
 * @Table(name="ganttproject")
 */
class GanttProject
{
    /**
     * The ID of the Gant project.
     *
     * @Id @Column(type="integer") @GeneratedValue
     *
     * @var int
     */
    protected $id;

    /**
     * The file name associated to the Gantt project.
     *
     * @Column(type="string", name="file_name")
     *
     * @var string
     */
    protected $fileName;

    /**
     * The latest version of the Gantt project.
     *
     * @Column(type="integer", name="version")
     *
     * @var int
     */
    protected $version;

    /**
     * The related tasks of the Gantt project.
     *
     * @OneToMany(targetEntity="Task", mappedBy="ganttProject")
     *
     * @var Collection
     */
    protected $tasks;

    /**
     * The owner of the Gantt project.
     *
     * @ManyToOne(targetEntity="User", inversedBy="ganttProjects", cascade={"persist"})
     *
     * @var User
     */
    protected $user;

    public function __construct()
    {
        $this->tasks = new ArrayCollection();
    }

    /**
     * Get the ID of the Gant project.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set the ID of the Gant project.
     *
     * @param int $id The ID of the Gant project
     *
     * @return self
     */
    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the file name associated to the Gantt project.
     *
     * @return string
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * Set the file name associated to the Gantt project.
     *
     * @param string $fileName The file name associated to the Gantt project
     *
     * @return self
     */
    public function setFileName(string $fileName): self
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * Get the latest version of the Gantt project.
     *
     * @return int
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * Set the latest version of the Gantt project.
     *
     * @param int $version The latest version of the Gantt project
     *
     * @return self
     */
    public function setVersion(int $version): self
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get the related tasks of the Gantt project.
     *
     * @return Collection/Task[]
     */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    /**
     * Add a task.
     *
     * @param Task $task
     *
     * @return self
     */
    public function addTask(Task $task): self
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks[] = $task;
            $task->setGanttProject($this);
        }

        return $this;
    }

    /**
     * Remove a task.
     *
     * @param Task $task
     *
     * @return self
     */
    public function removeTask(Task $task): self
    {
        if ($this->tasks->contains($task)) {
            $this->tasks->removeElement($task);
            if ($task->getGanttProject() === $this) {
                $task->setGanttProject(null);
            }
        }

        return $this;
    }

    /**
     * Get the owner of the Gantt project.
     *
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Set the owner of the Gantt project.
     *
     * @param User $user The owner of the Gantt project
     *
     * @return self
     */
    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
