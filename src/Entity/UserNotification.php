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

    #[ORM\Column(type: 'integer', enumType: UserNotificationType::class)]
    private UserNotificationType $type;

    #[ORM\Column(options:['charset'=>'utf8mb4', 'collation'=>'utf8mb4_unicode_ci'])]
    private string $message;

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
     * Get the value of type
     */
    public function getType(): UserNotificationType
    {
        return $this->type;
    }

    /**
     * Set the value of type
     */
    public function setType(UserNotificationType $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get the value of message
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Set the value of message
     */
    public function setMessage(string $message): self
    {
        $this->message = $message;

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
     * Set the value of created_timestamp
     */
    public function setCreatedTimestamp(\DateTime $created_timestamp): self
    {
        $this->created_timestamp = $created_timestamp;

        return $this;
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
}
