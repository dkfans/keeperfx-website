<?php

namespace App\Entity;

use App\Entity\User;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class WorkshopCommentReport {

    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\ManyToOne(targetEntity: 'User')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: WorkshopComment::class)]
    private WorkshopComment $comment;

    #[ORM\Column(type: 'text')]
    private string $reason;

    #[ORM\Column]
    private \DateTime $created_timestamp;

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
     * Get the value of reason
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * Set the value of reason
     */
    public function setReason(string $reason): self
    {
        $this->reason = $reason;

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
     * Get the value of comment
     */
    public function getComment(): WorkshopComment
    {
        return $this->comment;
    }

    /**
     * Set the value of comment
     */
    public function setComment(WorkshopComment $comment): self
    {
        $this->comment = $comment;

        return $this;
    }
}
