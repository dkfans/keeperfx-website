<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class LauncherRelease
{

    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private int|null $id = null;

    #[ORM\Column]
    private string $tag;

    #[ORM\Column]
    private string $name;

    #[ORM\Column]
    private int $size_in_bytes;

    #[ORM\Column]
    private \DateTime $timestamp;

    #[ORM\Column]
    private bool $is_available = true;

    /**
     * Get the value of id
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the value of tag
     */
    public function getTag(): string
    {
        return $this->tag;
    }

    /**
     * Set the value of tag
     */
    public function setTag(string $tag): self
    {
        $this->tag = $tag;

        return $this;
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
     * Get the value of size_in_bytes
     */
    public function getSizeInBytes(): int
    {
        return $this->size_in_bytes;
    }

    /**
     * Set the value of size_in_bytes
     */
    public function setSizeInBytes(int $size_in_bytes): self
    {
        $this->size_in_bytes = $size_in_bytes;

        return $this;
    }

    /**
     * Get the value of timestamp
     */
    public function getTimestamp(): \DateTime
    {
        return $this->timestamp;
    }

    /**
     * Set the value of timestamp
     */
    public function setTimestamp(\DateTime $timestamp): self
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * Get the value of is_available
     */
    public function isAvailable(): bool
    {
        return $this->is_available;
    }

    /**
     * Set the value of is_available
     */
    public function setIsAvailable(bool $is_available): self
    {
        $this->is_available = $is_available;

        return $this;
    }
}
