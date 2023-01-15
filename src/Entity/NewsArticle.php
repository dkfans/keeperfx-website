<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;
use URLify;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class NewsArticle {

    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private int|null $id = null;

    #[ORM\Column]
    private string $title;

    #[ORM\ManyToOne(targetEntity: 'User')]
    private User $author;

    #[ORM\Column]
    private \DateTime $created_timestamp;

    #[ORM\Column(type: 'text')]
    private string $short_text;

    #[ORM\Column(type: 'text')]
    private string $text;

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

    /**
     * Get the value of short_text
     */
    public function getShortText()
    {
        return $this->short_text;
    }

    /**
     * Set the value of short_text
     *
     * @return  self
     */
    public function setShortText($short_text): self
    {
        $this->short_text = $short_text;

        return $this;
    }

    /**
     * Get the value of text
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * Set the value of text
     *
     * @return  self
     */
    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getTitleSlug(): string {
        return URLify::slug($this->title);
    }
}
