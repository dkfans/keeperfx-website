<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class GitCommit
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column]
    private string $hash;

    #[ORM\ManyToOne(targetEntity: GithubRelease::class, inversedBy: 'commits')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private GithubRelease $release;

    #[ORM\Column]
    private \DateTime $timestamp;

    #[ORM\Column]
    private string $message;

    /**
     * Get the value of id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the value of hash.
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * Set the value of hash.
     */
    public function setHash(string $hash): self
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * Get the value of release.
     */
    public function getRelease(): GithubRelease
    {
        return $this->release;
    }

    /**
     * Set the value of release.
     */
    public function setRelease(GithubRelease $release): self
    {
        $this->release = $release;

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
     */
    public function setTimestamp(\DateTime $timestamp): self
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * Get the value of message.
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Set the value of message.
     */
    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }
}
