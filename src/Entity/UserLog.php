<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class UserLog
{

    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'user_logs')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column]
    private string $log_type;

    #[ORM\Column(type: 'text')]
    private string $variables;

    #[ORM\Column]
    private string $ip;

    #[ORM\Column]
    private \DateTime $timestamp;

    #[ORM\PrePersist]
    public function onPrePersist()
    {
        $this->timestamp = new \DateTime("now");
    }

    /**
     * Get the value of id
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set the value of id
     */
    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
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
     * Get the value of log_type
     */
    public function getLogType(): string
    {
        return $this->log_type;
    }

    /**
     * Set the value of log_type
     */
    public function setLogType(string $log_type): self
    {
        $this->log_type = $log_type;

        return $this;
    }

    /**
     * Get the value of variables
     */
    public function getVariables(): array
    {
        return \json_decode($this->variables, true);
    }

    /**
     * Set the value of variables
     */
    public function setVariables(array $variables): self
    {
        $this->variables = \json_encode($variables);

        return $this;
    }

    /**
     * Get the value of ip
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * Set the value of ip
     */
    public function setIp(string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get the value of timestamp
     */
    public function getTimestamp(): \DateTime
    {
        return $this->timestamp;
    }
}
