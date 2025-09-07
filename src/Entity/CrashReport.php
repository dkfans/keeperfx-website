<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class CrashReport
{

    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private int|null $id = null;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(type: 'text')]
    private string $game_version;

    #[ORM\Column(type: 'text')]
    private string $game_config;

    #[ORM\Column(type: 'text')]
    private string $game_log;

    #[ORM\Column(type: 'text')]
    private string $game_output;

    #[ORM\Column(nullable: true)]
    private string|null $contact_details = null;

    #[ORM\Column(nullable: true)]
    private string|null $save_filename = null;

    #[ORM\Column(nullable: true)]
    private string|null $source = null; // The source of the crash report

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
    public function getId(): ?int
    {
        return $this->id;
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
     * Get the value of game_version
     */
    public function getGameVersion(): string
    {
        return $this->game_version;
    }

    /**
     * Set the value of game_version
     */
    public function setGameVersion(string $game_version): self
    {
        $this->game_version = $game_version;

        return $this;
    }

    /**
     * Get the value of game_config
     */
    public function getGameConfig(): string
    {
        return $this->game_config;
    }

    /**
     * Set the value of game_config
     */
    public function setGameConfig(string $game_config): self
    {
        $this->game_config = $game_config;

        return $this;
    }

    /**
     * Get the value of game_log
     */
    public function getGameLog(): string
    {
        return $this->game_log;
    }

    /**
     * Set the value of game_log
     */
    public function setGameLog(string $game_log): self
    {
        $this->game_log = $game_log;

        return $this;
    }

    /**
     * Get the value of game_output
     */
    public function getGameOutput(): string
    {
        return $this->game_output;
    }

    /**
     * Set the value of game_output
     */
    public function setGameOutput(string $game_output): self
    {
        $this->game_output = $game_output;

        return $this;
    }

    /**
     * Get the value of contact_details
     */
    public function getContactDetails(): ?string
    {
        return $this->contact_details;
    }

    /**
     * Set the value of contact_details
     */
    public function setContactDetails(?string $contact_details): self
    {
        $this->contact_details = $contact_details;

        return $this;
    }

    /**
     * Get the value of save_filename
     */
    public function getSaveFilename(): ?string
    {
        return $this->save_filename;
    }

    /**
     * Set the value of save_filename
     */
    public function setSaveFilename(?string $save_filename): self
    {
        $this->save_filename = $save_filename;

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
     * Get the value of source
     */
    public function getSource(): ?string
    {
        return $this->source;
    }

    /**
     * Set the value of source
     */
    public function setSource(?string $source): self
    {
        $this->source = $source;

        return $this;
    }
}
