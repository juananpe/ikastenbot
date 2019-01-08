<?php

declare(strict_types=1);

namespace IkastenBot\Entity;

use IkastenBot\Entity\GanttProject;

/**
 * Represents the User entity
 *
 * @Entity
 * @Table(name="user")
 */
class User
{
    /**
     * The id of the user
     *
     * @Id @Column(type="integer") @GeneratedValue
     * @var int
     */
    protected $id;

    /**
     * The user is a bot
     *
     * @Column(type="boolean", name="is_bot")
     * @var bool
     */
    protected $bot;

    /**
     * First name of the user
     *
     * @Column(type="string", name="first_name")
     * @var string
     */
    protected $firstName;

    /**
     * Last name of the user
     *
     * @Column(type="string", name="last_name")
     * @var string
     */
    protected $lastName;

    /**
     * Username of the user
     *
     * @Column(type="string", name="username")
     * @var string
     */
    protected $username;

    /**
     * Language code of the user's system
     *
     * @Column(type="string", name="language_code")
     * @var string
     */
    protected $languageCode;

    /**
     * The date the user entry was created
     *
     * @Column(type="datetime", name="created_at")
     * @var DateTime
     */
    protected $createdAt;

    /**
     * The date in which the user entry was updated
     *
     * @Column(type="datetime", name="updated_at")
     * @var DateTime
     */
    protected $updatedAt;

    /**
     * Language the user wants to be contacted in
     *
     * @Column(type="string", name="language")
     * @var string
     */
    protected $language;

    /**
     * The associated Gantt project
     *
     * @OneToOne(targetEntity="GanttProject", inversedBy="user")
     * @JoinColumn(name="ganttproject_id", referencedColumnName="id")
     *
     * @var GanttProject
     */
    protected $ganttProject;

    /**
     * Get the id of the user
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set the id of the user
     *
     * @param int $id  The id of the user
     *
     * @return self
     */
    public function setId(int $id): self
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
     * Set the user is a bot
     *
     * @param bool $bot  The user is a bot
     *
     * @return self
     */
    public function setBot(bool $bot): self
    {
        $this->bot = $bot;

        return $this;
    }

    /**
     * Get first name of the user
     *
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * Set first name of the user
     *
     * @param string $firstName  First name of the user
     *
     * @return self
     */
    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get last name of the user
     *
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * Set last name of the user
     *
     * @param string $lastName  Last name of the user
     *
     * @return self
     */
    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get username of the user
     *
     * @return  string
     */
    public function getUsername():string
    {
        return $this->username;
    }

    /**
     * Set username of the user
     *
     * @param  string  $username  Username of the user
     *
     * @return  self
     */
    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get language code of the user's system
     *
     * @return  string
     */
    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    /**
     * Set language code of the user's system
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
     * Get the date the user entry was created
     *
     * @return DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * Set the date the user entry was created
     *
     * @param \DateTime $createdAt  The date the user entry was created
     *
     * @return self
     */
    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get the date in which the user entry was updated
     *
     * @return \DateTime
     */
    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    /**
     * Set the date in which the user entry was updated
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
     * Get language the user wants to be contacted in
     *
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * Set language the user wants to be contacted in
     *
     * @param string $language  Language the user wants to be contacted in
     *
     * @return self
     */
    public function setLanguage(string $language): self
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Get the associated Gantt project
     *
     * @return GanttProject
     */
    public function getGanttProject(): GanttProject
    {
        return $this->ganttProject;
    }

    /**
     * Set the associated Gantt project
     *
     * @param GanttProject $ganttProject The associated Gantt project
     *
     * @return self
     */
    public function setGanttProject(GanttProject $ganttProject): self
    {
        $this->ganttProject = $ganttProject;

        return $this;
    }
}