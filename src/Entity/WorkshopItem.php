<?php

namespace App\Entity;

use App\Enum\WorkshopCategory;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class WorkshopItem
{

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

    #[ORM\Column(type: 'integer', enumType: WorkshopCategory::class)]
    private WorkshopCategory $category;

    #[ORM\Column(nullable: true)]
    private int|null $min_game_build = null;

    #[ORM\Column]
    private \DateTime $created_timestamp;

    #[ORM\Column(nullable: true)]
    private \DateTime|null $updated_timestamp = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private string|null $description = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private string|null $install_instructions = null;

    #[ORM\Column]
    private bool $is_published = false;

    #[ORM\Column]
    private bool $is_bundled_with_game = false;

    #[ORM\Column]
    private bool $is_last_file_broken = false;

    #[ORM\Column]
    private bool $difficulty_rating_enabled = true;

    #[ORM\Column(type: 'integer')]
    private int $download_count = 0;

    #[ORM\Column(nullable: true)]
    private string|null $original_author = null;

    #[ORM\Column(nullable: true)]
    private \DateTime|null $original_creation_date = null;

    #[ORM\Column(nullable: true)]
    private string|null $thumbnail = null;

    #[ORM\OneToMany(targetEntity: WorkshopFile::class, mappedBy: 'item', cascade: ["remove"])]
    #[ORM\OrderBy(["weight" => "ASC"])]
    private Collection $files;

    #[ORM\OneToMany(targetEntity: WorkshopImage::class, mappedBy: 'item', cascade: ["remove"])]
    #[ORM\OrderBy(["weight" => "ASC"])]
    private Collection $images;

    #[ORM\OneToMany(targetEntity: WorkshopRating::class, mappedBy: 'item', cascade: ["remove"])]
    private Collection $ratings;

    #[ORM\Column(type: "decimal", precision: 3, scale: 2, nullable: true)]
    private float|null $rating_score = null;

    #[ORM\OneToMany(targetEntity: WorkshopDifficultyRating::class, mappedBy: 'item', cascade: ["remove"])]
    private Collection $difficulty_ratings;

    #[ORM\Column(type: "decimal", precision: 3, scale: 2, nullable: true)]
    private float|null $difficulty_rating_score = null;

    #[ORM\OneToMany(targetEntity: WorkshopComment::class, mappedBy: 'item', cascade: ["remove"])]
    #[ORM\OrderBy(["created_timestamp" => "DESC"])]
    private Collection $comments;

    #[ORM\OneToMany(targetEntity: WorkshopBrokenFile::class, mappedBy: 'original_item')]
    private Collection $broken_files;

    #[ORM\Column]
    private ?\DateTime $creation_orderby_timestamp = null;

    public function __construct()
    {
        $this->files              = new ArrayCollection();
        $this->images             = new ArrayCollection();
        $this->ratings            = new ArrayCollection();
        $this->difficulty_ratings = new ArrayCollection();
        $this->comments           = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function onPrePersist()
    {
        $this->created_timestamp = new \DateTime("now");
        if ($this->creation_orderby_timestamp === null) {
            $this->creation_orderby_timestamp = new \DateTime("now");
        }
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
    public function getUpdatedTimestamp(): ?\DateTime
    {
        return $this->updated_timestamp;
    }

    /**
     * Set the value of updated_timestamp
     */
    public function setUpdatedTimestamp(?\DateTime $updated_timestamp): self
    {
        $this->updated_timestamp = $updated_timestamp;

        return $this;
    }

    /**
     * Get the value of is_published
     */
    public function isPublished(): bool
    {
        return $this->is_published;
    }

    /**
     * Fallback for Twig
     */
    public function is_published(): bool
    {
        return $this->isPublished();
    }

    /**
     * Set the value of is_published
     */
    public function setIsPublished(bool $is_published): self
    {
        $this->is_published = $is_published;

        return $this;
    }

    /**
     * Get the value of category
     */
    public function getCategory(): WorkshopCategory
    {
        return $this->category;
    }

    /**
     * Set the value of category
     */
    public function setCategory(WorkshopCategory $category): self
    {
        $this->category = $category;
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
    public function getMinGameBuild(): int|null
    {
        return $this->min_game_build;
    }

    /**
     * Set the value of min_game_build
     */
    public function setMinGameBuild(int|null $min_game_build): self
    {
        $this->min_game_build = $min_game_build;
        return $this;
    }

    /**
     * Get the value of install_instructions
     */
    public function getInstallInstructions(): string|null
    {
        return $this->install_instructions;
    }

    /**
     * Set the value of install_instructions
     */
    public function setInstallInstructions(string|null $install_instructions): self
    {
        $this->install_instructions = $install_instructions;
        return $this;
    }

    /**
     * Get the value of description
     */
    public function getDescription(): string|null
    {
        return $this->description;
    }

    /**
     * Set the value of description
     */
    public function setDescription(string|null $description): self
    {
        $this->description = $description;
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

        if ($original_creation_date === null) {
            $this->creation_orderby_timestamp = $this->created_timestamp;
        } else {
            $this->creation_orderby_timestamp = $original_creation_date;
        }

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

    /**
     * Get the value of files
     */
    public function getFiles(): Collection
    {
        return $this->files;
    }

    /**
     * Get the value of images
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    /**
     * Get the value of difficulty_rating_enabled
     */
    public function isDifficultyRatingEnabled(): bool
    {
        return $this->difficulty_rating_enabled;
    }

    /**
     * Set the value of difficulty_rating_enabled
     */
    public function setDifficultyRatingEnabled(bool $difficulty_rating_enabled): self
    {
        $this->difficulty_rating_enabled = $difficulty_rating_enabled;

        return $this;
    }

    /**
     * Get the value of creation_orderby_timestamp
     */
    public function getCreationOrderbyTimestamp(): \DateTime
    {
        return $this->creation_orderby_timestamp;
    }

    /**
     * Set the value of creation_orderby_timestamp
     */
    public function setCreationOrderbyTimestamp(\DateTime $creation_orderby_timestamp): self
    {
        $this->creation_orderby_timestamp = $creation_orderby_timestamp;

        return $this;
    }

    /**
     * Get the value of is_bundled_with_game
     */
    public function isIsBundledWithGame(): bool
    {
        return $this->is_bundled_with_game;
    }

    /**
     * Set the value of is_bundled_with_game
     */
    public function setIsBundledWithGame(bool $is_bundled_with_game): self
    {
        $this->is_bundled_with_game = $is_bundled_with_game;

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

        return $this;
    }

    /**
     * Get the value of broken_files
     */
    public function getBrokenFiles(): Collection
    {
        return $this->broken_files;
    }

    /**
     * Get the value of is_last_file_broken
     */
    public function isLastFileBroken(): bool
    {
        return $this->is_last_file_broken;
    }

    /**
     * Set the value of is_last_file_broken
     */
    public function setIsLastFileBroken(bool $is_last_file_broken): self
    {
        $this->is_last_file_broken = $is_last_file_broken;

        return $this;
    }
}
