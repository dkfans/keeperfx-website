<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Xenokore\Utility\Helper\StringHelper;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class UserEmailVerification {

    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'email_verification')]
    private User $user;

    #[ORM\Column()]
    private string $token;

    #[ORM\Column]
    private bool $sent = false;

    #[ORM\Column]
    private \DateTime $created_timestamp;

    #[ORM\PrePersist]
    public function onPrePersist()
    {
        $this->created_timestamp = new \DateTime("now");
        $this->token = StringHelper::generate(32);
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
     * Get the value of token
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * Set the value of token
     */
    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get the value of sent
     */
    public function isSent(): bool
    {
        return $this->sent;
    }

    /**
     * Set the value of sent
     */
    public function setSent(bool $sent): self
    {
        $this->sent = $sent;

        return $this;
    }

    /**
     * Get the value of created_timestamp
     */
    public function getCreatedTimestamp(): \DateTime
    {
        return $this->created_timestamp;
    }
}
