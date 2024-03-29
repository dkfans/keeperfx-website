<?php

namespace App\Entity;

use App\Enum\UserNotificationType;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class UserNotification {

    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private User $user;

    #[ORM\Column]
    private string $class;

    #[ORM\Column(type: 'text', nullable:true)]
    private string|null $data;

    #[ORM\Column]
    private \DateTime $created_timestamp;

    #[ORM\Column]
    private bool $is_read = false;

    #[ORM\PrePersist]
    public function onPrePersist()
    {
        $this->created_timestamp = new \DateTime("now");
    }

    /**
     * Get the value of id
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the value of user
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Set the value of user
     */
    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get the value of data
     */
    public function getData(): array|string|null
    {
        return \json_decode($this->data, true);
    }

    /**
     * Set the value of data
     */
    public function setData(array|null $data): self
    {
        $this->data = \json_encode($data);

        return $this;
    }

    /**
     * Get the value of created_timestamp
     */
    public function getCreatedTimestamp(): \DateTime
    {
        return $this->created_timestamp;
    }

    /**
     * Get the value of is_read
     */
    public function isRead(): bool
    {
        return $this->is_read;
    }

    /**
     * Set the value of is_read
     */
    public function setRead(bool $is_read): self
    {
        $this->is_read = $is_read;

        return $this;
    }

    /**
     * Get the value of class
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * Set the value of class
     */
    public function setClass(string $class): self
    {
        $this->class = $class;

        return $this;
    }
}
