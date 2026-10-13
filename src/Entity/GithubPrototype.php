<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class GithubPrototype
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(type: 'bigint', nullable: true)]
    private int $artifact_id;

    #[ORM\Column]
    private string $name;

    #[ORM\Column]
    private string $workflow_title;

    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?string $workflow_run_id;

    #[ORM\Column]
    private string $filename;

    #[ORM\Column]
    private \DateTime $timestamp;

    #[ORM\Column]
    private int $size_in_bytes;

    #[ORM\Column]
    private bool $is_available = true;

    /**
     * Get the value of id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the value of artifact_id.
     */
    public function getArtifactId(): int
    {
        return $this->artifact_id;
    }

    /**
     * Set the value of artifact_id.
     *
     * @return self
     */
    public function setArtifactId(int $artifact_id): static
    {
        $this->artifact_id = $artifact_id;

        return $this;
    }

    /**
     * Get the value of name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the value of name.
     *
     * @return self
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the value of filename.
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * Set the value of filename.
     *
     * @return self
     */
    public function setFilename(string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get the value of timestamp.
     */
    public function getTimestamp(): \DateTime
    {
        return $this->timestamp;
    }

    /**
     * Set the value of timestamp.
     *
     * @return self
     */
    public function setTimestamp(\DateTime $timestamp): static
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * Get the value of size_in_bytes.
     */
    public function getSizeInBytes(): int
    {
        return $this->size_in_bytes;
    }

    /**
     * Set the value of size_in_bytes.
     *
     * @return self
     */
    public function setSizeInBytes(int $size_in_bytes): static
    {
        $this->size_in_bytes = $size_in_bytes;

        return $this;
    }

    /**
     * Get the value of workflow_title.
     */
    public function getWorkflowTitle(): string
    {
        return $this->workflow_title;
    }

    /**
     * Set the value of workflow_title.
     *
     * @return self
     */
    public function setWorkflowTitle(string $workflow_title): static
    {
        $this->workflow_title = $workflow_title;

        return $this;
    }

    /**
     * Get the value of is_available.
     */
    public function getIsAvailable(): bool
    {
        return $this->is_available;
    }

    /**
     * Set the value of is_available.
     *
     * @return self
     */
    public function setIsAvailable(bool $is_available): static
    {
        $this->is_available = $is_available;

        return $this;
    }

    /**
     * Get the value of workflow_run_id.
     */
    public function getWorkflowRunId(): ?string
    {
        return $this->workflow_run_id;
    }

    /**
     * Set the value of workflow_run_id.
     */
    public function setWorkflowRunId(?string $workflow_run_id): self
    {
        $this->workflow_run_id = $workflow_run_id;

        return $this;
    }
}
