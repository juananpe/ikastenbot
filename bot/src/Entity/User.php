<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Represents the User entity.
 *
 * @ORM\Entity
 * @ORM\Table(name="user", indexes={@ORM\Index(name="username", columns={"username"})})
 */
class User
{
    /**
     * The id of the user.
     *
     * @ORM\Id @ORM\Column(type="bigint", options={"comment":"Unique user identifier"})
     *
     * @var string
     */
    protected $id;

    /**
     * The user is a bot.
     *
     * @ORM\Column(type="boolean", name="is_bot", nullable=true, options={"comment":"True if this user is a bot", "default": 0})
     *
     * @var bool
     */
    protected $bot;

    /**
     * First name of the user.
     *
     * @ORM\Column(type="string", name="first_name", length=255, nullable=false, options={"comment": "User's first name", "default": "",  "fixed": true})
     *
     * @var string
     */
    protected $firstName;

    /**
     * Last name of the user.
     *
     * @ORM\Column(type="string", name="last_name", length=255, nullable=true, options={"comment": "User's last name", "default": null, "fixed": true})
     *
     * @var string
     */
    protected $lastName;

    /**
     * Username of the user.
     *
     * @ORM\Column(type="string", name="username", length=191, nullable=true, options={"comment": "User's username", "default": null, "fixed": true})
     *
     * @var string
     */
    protected $username;

    /**
     * Language code of the user's system.
     *
     * @ORM\Column(type="string", name="language_code", length=10, nullable=true, options={"comment": "User's system language", "default": null, "fixed": true})
     *
     * @var string
     */
    protected $languageCode;

    /**
     * The date the user entry was created.
     *
     * @ORM\Column(type="datetime", name="created_at", nullable=true, options={"comment": "Entry date creation"})
     *
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * The date in which the user entry was updated.
     *
     * @ORM\Column(type="datetime", name="updated_at", nullable=true, options={"comment": "Entry date update"})
     *
     * @var \DateTime
     */
    protected $updatedAt;

    /**
     * Language the user wants to be contacted in.
     *
     * @ORM\Column(type="string", name="language", nullable=true, length=10, options={"default": "es", "fixed": true})
     *
     * @var string
     */
    protected $language;

    /**
     * The associated GanttProjects.
     *
     * @ORM\OneToMany(targetEntity="GanttProject", mappedBy="user", cascade={"persist"})
     *
     * @var GanttProject
     */
    protected $ganttProjects;

    public function __construct()
    {
        $this->ganttProjects = new ArrayCollection();
    }

    /**
     * Get the id of the user.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Set the id of the user.
     *
     * @param string $id The id of the user
     *
     * @return self
     */
    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Is the user a bot?
     *
     * @return bool
     */
    public function isBot(): bool
    {
        return $this->bot;
    }

    /**
     * Set the user is a bot.
     *
     * @param bool $bot The user is a bot
     *
     * @return self
     */
    public function setBot(bool $bot): self
    {
        $this->bot = $bot;

        return $this;
    }

    /**
     * Get first name of the user.
     *
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * Set first name of the user.
     *
     * @param string $firstName First name of the user
     *
     * @return self
     */
    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get last name of the user.
     *
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * Set last name of the user.
     *
     * @param string $lastName Last name of the user
     *
     * @return self
     */
    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get username of the user.
     *
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Set username of the user.
     *
     * @param string $username Username of the user
     *
     * @return self
     */
    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get language code of the user's system.
     *
     * @return string
     */
    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    /**
     * Set language code of the user's system.
     *
     * @param string $languageCode Language code of the user's system
     *
     * @return self
     */
    public function setLanguageCode(string $languageCode): self
    {
        $this->languageCode = $languageCode;

        return $this;
    }

    /**
     * Get the date the user entry was created.
     *
     * @return DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * Set the date the user entry was created.
     *
     * @param \DateTime $createdAt The date the user entry was created
     *
     * @return self
     */
    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get the date in which the user entry was updated.
     *
     * @return \DateTime
     */
    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    /**
     * Set the date in which the user entry was updated.
     *
     * @param \DateTime $updatedAt The date in which the user entry was updated
     *
     * @return self
     */
    public function setUpdatedAt(\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get language the user wants to be contacted in.
     *
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * Set language the user wants to be contacted in.
     *
     * @param string $language Language the user wants to be contacted in
     *
     * @return self
     */
    public function setLanguage(string $language): self
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Get the associated Gantt projects.
     *
     * @return Collection/GanttProject[]
     */
    public function getGanttProjects(): Collection
    {
        return $this->ganttProjects;
    }

    /**
     * Add a GanttProject.
     *
     * @param GanttProject $ganttProject
     *
     * @return self
     */
    public function addGanttProject(GanttProject $ganttProject): self
    {
        if (!$this->ganttProjects->contains($ganttProject)) {
            $this->ganttProjects[] = $ganttProject;
            $ganttProject->setUser($this);
        }

        return $this;
    }

    public function removeGanttProject(GanttProject $ganttProject): self
    {
        if ($this->ganttProjects->contains($ganttProject)) {
            $this->ganttProjects->removeElement($ganttProject);
            if ($ganttProject->getUser() === $this) {
                $ganttProject->setUser(null);
            }
        }

        return $this;
    }
}
