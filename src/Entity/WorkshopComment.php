<?php

namespace App\Entity;

use App\Entity\User;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class WorkshopComment {

    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\ManyToOne(targetEntity: WorkshopItem::class, inversedBy: 'comments')]
    private WorkshopItem $item;

    #[ORM\ManyToOne(targetEntity: 'User')]
    private User $user;

    #[ORM\Column(type: 'text', options:['charset'=>'utf8mb4', 'collation'=>'utf8mb4_unicode_ci'])]
    private string $content;

    #[ORM\Column]
    private \DateTime $created_timestamp;

    #[ORM\Column]
    private \DateTime $updated_timestamp;

    #[ORM\ManyToOne(targetEntity: WorkshopComment::class)]
    private WorkshopComment|null $parent = null;

    #[ORM\OneToMany(targetEntity: WorkshopComment::class, mappedBy: 'parent', cascade: ["remove"])]
    #[ORM\OrderBy(["created_timestamp" => "DESC"])]
    private Collection $replies;

    #[ORM\OneToMany(targetEntity: WorkshopCommentReport::class, mappedBy: 'comment', cascade: ["remove"])]
    #[ORM\OrderBy(["created_timestamp" => "DESC"])]
    private Collection $reports;

    public function __construct() {
        $this->replies = new ArrayCollection();
        $this->reports = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function onPrePersist()
    {
        $this->created_timestamp = new \DateTime("now");
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
     * Get the value of item
     */
    public function getItem(): WorkshopItem
    {
        return $this->item;
    }

    /**
     * Set the value of item
     */
    public function setItem(WorkshopItem $item): self
    {
        $this->item = $item;

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
     * Get the value of content
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Set the value of content
     */
    public function setContent(string $content): self
    {
        $this->content = $content;

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
     * Get the value of updated_timestamp
     */
    public function getUpdatedTimestamp(): \DateTime
    {
        return $this->updated_timestamp;
    }

    /**
     * Get the value of replies
     */
    public function getReplies(): Collection
    {
        return $this->replies;
    }

    /**
     * Get the value of parent
     */
    public function getParent(): ?WorkshopComment
    {
        return $this->parent;
    }

    /**
     * Set the value of parent
     */
    public function setParent(?WorkshopComment $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get the value of reports
     */
    public function getReports(): Collection
    {
        return $this->reports;
    }
}
