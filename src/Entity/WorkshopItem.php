<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class WorkshopItem {

    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column]
    private string $name;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private User $author;

    #[ORM\Column]
    private int|null $map_number = null;

    #[ORM\ManyToOne(targetEntity: WorkshopType::class)]
    private WorkshopType $type;

    #[ORM\ManyToOne(targetEntity: GithubRelease::class)]
    private GithubRelease|null $min_game_build;

    #[ORM\Column]
    private \DateTime $creation_date;

    #[ORM\Column]
    private \DateTime $created_timestamp;

    #[ORM\Column]
    private \DateTime $updated_timestamp;

    #[ORM\Column]
    private bool $is_accepted = false;

    #[ORM\PrePersist]
    public function onPrePersist()
    {
        $this->created_timestamp = new \DateTime("now");
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
     * Get the value of name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the value of name
     */
    public function setName(string $name): self
    {
        $this->name = $name;

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
     * Get the value of updated_timestamp
     */
    public function getUpdatedTimestamp(): \DateTime
    {
        return $this->updated_timestamp;
    }

    /**
     * Set the value of updated_timestamp
     */
    public function setUpdatedTimestamp(\DateTime $updated_timestamp): self
    {
        $this->updated_timestamp = $updated_timestamp;

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
     */
    public function setAuthor(User $author): self
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Get the value of is_accepted
     */
    public function isIsAccepted(): bool
    {
        return $this->is_accepted;
    }

    /**
     * Set the value of is_accepted
     */
    public function setIsAccepted(bool $is_accepted): self
    {
        $this->is_accepted = $is_accepted;

        return $this;
    }

    /**
     * Get the value of creation_date
     */
    public function getCreationDate(): \DateTime
    {
        return $this->creation_date;
    }

    /**
     * Set the value of creation_date
     */
    public function setCreationDate(\DateTime $creation_date): self
    {
        $this->creation_date = $creation_date;

        return $this;
    }

    /**
     * Get the value of type
     */
    public function getType(): WorkshopType
    {
        return $this->type;
    }

    /**
     * Set the value of type
     */
    public function setType(WorkshopType $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get the value of map_number
     */
    public function getMapNumber(): ?int
    {
        return $this->map_number;
    }

    /**
     * Set the value of map_number
     */
    public function setMapNumber(?int $map_number): self
    {
        $this->map_number = $map_number;

        return $this;
    }

    /**
     * Get the value of min_game_build
     */
    public function getMinGameBuild(): GithubRelease
    {
        return $this->min_game_build;
    }

    /**
     * Set the value of min_game_build
     */
    public function setMinGameBuild(GithubRelease $min_game_build): self
    {
        $this->min_game_build = $min_game_build;

        return $this;
    }
}
