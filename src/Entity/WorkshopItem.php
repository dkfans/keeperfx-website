<?php

namespace App\Entity;

use App\Enum\WorkshopType;
use Doctrine\ORM\Mapping as ORM;

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
    private User $submitter;

    #[ORM\Column(nullable: true)]
    private int|null $map_number = null;

    #[ORM\Column(type: 'integer', enumType: WorkshopType::class)]
    private WorkshopType $type;

    #[ORM\ManyToOne(targetEntity: GithubRelease::class)]
    private GithubRelease|null $min_game_build = null;

    #[ORM\Column(nullable: true)]
    private \DateTime|null $original_creation_date = null;

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
    public function setOriginalCreationDate(\DateTime $original_creation_date): self
    {
        $this->original_creation_date = $original_creation_date;

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

        return $this;
    }

    /**
     * Get the value of submitter
     */
    public function getSubmitter(): User
    {
        return $this->submitter;
    }

    /**
     * Set the value of submitter
     */
    public function setSubmitter(User $submitter): self
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
}
