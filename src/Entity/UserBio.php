<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class UserBio
{

    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'bio')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'text')]
    private string $bio;

    #[ORM\Column]
    private \DateTime $updated_timestamp;

    #[ORM\PrePersist]
    public function onPrePersist()
    {
        $this->updated_timestamp = new \DateTime("now");
    }

    #[ORM\PreUpdate]
    public function onPreUpdate()
    {
        $this->updated_timestamp = new \DateTime("now");
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
     * Get the value of bio
     */
    public function getBio(): string
    {
        return $this->bio;
    }

    /**
     * Set the value of bio
     */
    public function setBio(string $bio): self
    {
        $this->bio = $bio;

        return $this;
    }

    /**
     * Get the value of updated_timestamp
     */
    public function getUpdatedTimestamp(): \DateTime
    {
        return $this->updated_timestamp;
    }
}
