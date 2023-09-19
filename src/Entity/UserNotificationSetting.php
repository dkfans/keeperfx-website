<?php

namespace App\Entity;

use App\Enum\UserNotificationType;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class UserNotificationSetting {

    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private User $user;

    #[ORM\Column(type: 'integer', enumType: UserNotificationType::class)]
    private UserNotificationType $type;

    #[ORM\Column]
    private bool $is_enabled = true;

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
     * Get the value of is_enabled
     */
    public function isEnabled(): bool
    {
        return $this->is_enabled;
    }

    /**
     * Set the value of is_enabled
     */
    public function setEnabled(bool $is_enabled): self
    {
        $this->is_enabled = $is_enabled;

        return $this;
    }
}
