<?php

namespace App\Entity;

use App\Enum\WorkshopType;
use App\Entity\WorkshopRating;
use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class WorkshopItem {

    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column]
    private string $name;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private User|null $submitter = null;

    #[ORM\Column(nullable: true)]
    private int|null $map_number = null;

    #[ORM\Column(type: 'integer', enumType: WorkshopType::class)]
    private WorkshopType $type;

    #[ORM\ManyToOne(targetEntity: GithubRelease::class)]
    private GithubRelease|null $min_game_build = null;

    #[ORM\Column]
    private \DateTime $created_timestamp;

    #[ORM\Column]
    private \DateTime $updated_timestamp;

    #[ORM\Column(type: 'text')]
    private string $description = '';

    #[ORM\Column(type: 'text')]
    private string $install_instructions = '';

    #[ORM\Column(nullable: true)]
    private string|null $filename = null;

    #[ORM\Column(nullable: true)]
    private string|null $thumbnail = null;

    #[ORM\Column]
    private bool $is_accepted = false;

    #[ORM\Column(type: 'integer')]
    private int $download_count = 0;

    #[ORM\Column(nullable: true)]
    private string|null $original_author = null;

    #[ORM\Column(nullable: true)]
    private \DateTime|null $original_creation_date = null;

    #[ORM\OneToMany(targetEntity: WorkshopRating::class, mappedBy: 'item')]
    private Collection $ratings;

    #[ORM\Column(type: "decimal", precision: 3, scale: 2, nullable: true)]
    private float|null $rating_score = null;

    #[ORM\OneToMany(targetEntity: WorkshopDifficultyRating::class, mappedBy: 'item')]
    private Collection $difficulty_ratings;

    #[ORM\Column(type: "decimal", precision: 3, scale: 2, nullable: true)]
    private float|null $difficulty_rating_score = null;

    #[ORM\OneToMany(targetEntity: WorkshopComment::class, mappedBy: 'item')]
    private Collection $comments;

    public function __construct() {
        $this->ratings            = new ArrayCollection();
        $this->difficulty_ratings = new ArrayCollection();
        $this->comments           = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function onPrePersist()
    {
        $this->created_timestamp = new \DateTime("now");
        $this->updated_timestamp = new \DateTime("now");
    }

    private function updateLastUpdatedTimestamp()
    {
        // Even though using `PreUpdate` would be nice, some columns should not update the last-updated timestamp.
        // For example, download counts and the calculated ratings are stored in this Entity for better performance.
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
        $this->updateLastUpdatedTimestamp();

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
     * Get the value of is_accepted
     */
    public function getIsAccepted(): bool
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
        $this->updateLastUpdatedTimestamp();

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
        $this->updateLastUpdatedTimestamp();

        return $this;
    }

    /**
     * Get the value of min_game_build
     */
    public function getMinGameBuild(): GithubRelease|null
    {
        return $this->min_game_build;
    }

    /**
     * Set the value of min_game_build
     */
    public function setMinGameBuild(GithubRelease|null $min_game_build): self
    {
        $this->min_game_build = $min_game_build;
        $this->updateLastUpdatedTimestamp();

        return $this;
    }

    /**
     * Get the value of install_instructions
     */
    public function getInstallInstructions(): string
    {
        return $this->install_instructions;
    }

    /**
     * Set the value of install_instructions
     */
    public function setInstallInstructions(string $install_instructions): self
    {
        $this->install_instructions = $install_instructions;
        $this->updateLastUpdatedTimestamp();

        return $this;
    }

    /**
     * Get the value of description
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Set the value of description
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;
        $this->updateLastUpdatedTimestamp();

        return $this;
    }

    /**
     * Get the value of filename
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * Set the value of filename
     */
    public function setFilename(string $filename): self
    {
        $this->filename = $filename;
        $this->updateLastUpdatedTimestamp();

        return $this;
    }

    /**
     * Get the value of submitter
     */
    public function getSubmitter(): User|null
    {
        return $this->submitter;
    }

    /**
     * Set the value of submitter
     */
    public function setSubmitter(User|null $submitter): self
    {
        $this->submitter = $submitter;
        $this->updateLastUpdatedTimestamp();

        return $this;
    }

    /**
     * Get the value of download_count
     */
    public function getDownloadCount(): int
    {
        return $this->download_count;
    }

    /**
     * Set the value of download_count
     */
    public function setDownloadCount(int $download_count): self
    {
        $this->download_count = $download_count;

        return $this;
    }

    /**
     * Get the value of thumbnail
     */
    public function getThumbnail(): ?string
    {
        return $this->thumbnail;
    }

    /**
     * Set the value of thumbnail
     */
    public function setThumbnail(?string $thumbnail): self
    {
        $this->thumbnail = $thumbnail;
        $this->updateLastUpdatedTimestamp();

        return $this;
    }

    /**
     * Get the value of ratings
     */
    public function getRatings(): Collection
    {
        return $this->ratings;
    }

    /**
     * Get the value of original_author
     */
    public function getOriginalAuthor(): ?string
    {
        return $this->original_author;
    }

    /**
     * Set the value of original_author
     */
    public function setOriginalAuthor(?string $original_author): self
    {
        $this->original_author = $original_author;
        $this->updateLastUpdatedTimestamp();

        return $this;
    }

    /**
     * Get the value of original_creation_date
     */
    public function getOriginalCreationDate(): \DateTime|null
    {
        return $this->original_creation_date;
    }

    /**
     * Set the value of original_creation_date
     */
    public function setOriginalCreationDate(?\DateTime $original_creation_date): self
    {
        $this->original_creation_date = $original_creation_date;
        $this->updateLastUpdatedTimestamp();

        return $this;
    }

    /**
     * Get the value of rating_score
     */
    public function getRatingScore(): ?float
    {
        return $this->rating_score;
    }

    /**
     * Set the value of rating_score
     */
    public function setRatingScore(?float $rating_score): self
    {
        $this->rating_score = $rating_score;

        return $this;
    }

    /**
     * Get the value of difficulty_rating_score
     */
    public function getDifficultyRatingScore(): ?float
    {
        return $this->difficulty_rating_score;
    }

    /**
     * Set the value of difficulty_rating_score
     */
    public function setDifficultyRatingScore(?float $difficulty_rating_score): self
    {
        $this->difficulty_rating_score = $difficulty_rating_score;

        return $this;
    }

    /**
     * Get the value of difficulty_ratings
     */
    public function getDifficultyRatings(): Collection
    {
        return $this->difficulty_ratings;
    }

    /**
     * Get the value of difficulty_ratings
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }
}