<?php

declare(strict_types=1);

namespace TelegramBotGanttProject\Entity;

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
     * Id of the chat to which the milestone is associated
     *
     * @Column(type="bigint")
     * @var string
     */
    protected $chat_id;

    /**
     * Name of the milestone
     *
     * @Column(type="string", name="milestone_name")
     * @var string
     */
    protected $name;

    /**
     * Date of the milestone
     *
     * @Column(type="datetime", name="milestone_date")
     * @var \DateTime
     */
    protected $date;

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
     * Get id of the chat to which the milestone is associated
     *
     * @return string
     */ 
    public function getChat_id(): string
    {
        return $this->chat_id;
    }

    /**
     * Set id of the chat to which the milestone is associated
     *
     * @param   string $chat_id  Id of the chat to which the milestone is associated
     *
     * @return  self
     */ 
    public function setChat_id(string $chat_id)
    {
        $this->chat_id = $chat_id;

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
     * Get date of the milestone
     *
     * @return \DateTime
     */ 
    public function getDate(): \DateTime
    {
        return $this->date;
    }

    /**
     * Set date of the milestone
     *
     * @param  \DateTime  $date  Date of the milestone
     *
     * @return  self
     */ 
    public function setDate(\DateTime $date)
    {
        $this->date = $date;

        return $this;
    }
}
