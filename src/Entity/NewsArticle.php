<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;
use URLify;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class NewsArticle
{

    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private int|null $id = null;

    #[ORM\Column]
    private string $title;

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $author;

    #[ORM\Column(nullable: true)]
    private string|null $image = null;

    #[ORM\Column]
    private \DateTime $created_timestamp;

    #[ORM\Column(type: 'text')]
    private string $contents;

    #[ORM\Column(type: 'text')]
    private string $excerpt;

    #[ORM\PrePersist]
    public function onPrePersist()
    {
        $this->created_timestamp = new \DateTime("now");
    }

    /**
     * Get the value of id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the value of title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set the value of title
     *
     * @return  self
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the value of author
     */
    public function getAuthor(): User
    {
        return $this->author;
    }

    /**
     * Set the value of author
     *
     * @return  self
     */
    public function setAuthor(User $author)
    {
        $this->author = $author;

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
     *
     * @return  self
     */
    public function setCreatedTimestamp($created_timestamp)
    {
        $this->created_timestamp = $created_timestamp;

        return $this;
    }

    public function getTitleSlug(): string
    {
        return URLify::slug($this->title);
    }

    /**
     * Get the value of contents
     */
    public function getContents(): string
    {
        return $this->contents;
    }

    /**
     * Set the value of contents
     */
    public function setContents(string $contents): self
    {
        $this->contents = $contents;

        return $this;
    }

    /**
     * Get the value of excerpt
     */
    public function getExcerpt(): string
    {
        return $this->excerpt;
    }

    /**
     * Set the value of excerpt
     */
    public function setExcerpt(string $excerpt): self
    {
        $this->excerpt = $excerpt;

        return $this;
    }

    /**
     * Get the value of image
     */
    public function getImage(): ?string
    {
        return $this->image;
    }

    /**
     * Set the value of image
     */
    public function setImage(?string $image): self
    {
        $this->image = $image;

        return $this;
    }
}
