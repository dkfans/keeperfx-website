<?php

namespace App\Entity;

use App\Enum\UserNotificationType;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class UserNotificationSetting
{

    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'notification_settings')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column]
    private string $class;

    #[ORM\Column]
    private bool $website_enabled = true;

    #[ORM\Column]
    private bool $email_enabled = true;

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
     * Get the value of class
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * Set the value of type
     */
    public function setClass(string $class): self
    {
        $this->class = $class;

        return $this;
    }

    /**
     * Get the value of website_enabled
     */
    public function isWebsiteEnabled(): bool
    {
        return $this->website_enabled;
    }

    /**
     * Set the value of website_enabled
     */
    public function setWebsiteEnabled(bool $website_enabled): self
    {
        $this->website_enabled = $website_enabled;

        return $this;
    }

    /**
     * Get the value of email_enabled
     */
    public function isEmailEnabled(): bool
    {
        return $this->email_enabled;
    }

    /**
     * Set the value of email_enabled
     */
    public function setEmailEnabled(bool $email_enabled): self
    {
        $this->email_enabled = $email_enabled;

        return $this;
    }
}
