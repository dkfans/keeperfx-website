<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class GithubRelease
{

    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private int|null $id = null;

    #[ORM\Column]
    private string $tag;

    #[ORM\Column]
    private string $name;

    #[ORM\Column(nullable: true)]
    private ?string $version = null;

    #[ORM\Column]
    private int $size_in_bytes;

    #[ORM\Column]
    private \DateTime $timestamp;

    #[ORM\Column]
    private string $download_url;

    #[ORM\Column]
    private bool $commits_handled = false;

    #[ORM\ManyToOne(targetEntity: NewsArticle::class)]
    private NewsArticle|null $linked_news_post = null;

    #[ORM\OneToMany(targetEntity: GitCommit::class, mappedBy: "release")]
    private Collection $commits;

    #[ORM\OneToMany(targetEntity: ReleaseMirror::class, mappedBy: "release")]
    private Collection $mirrors;

    /**
     * Get the value of id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the value of name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the value of name
     *
     * @return  self
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the value of tag
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * Set the value of tag
     *
     * @return  self
     */
    public function setTag($tag)
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * Get the value of timestamp
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Set the value of timestamp
     *
     * @return  self
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * Get the value of download_url
     */
    public function getDownloadUrl()
    {
        return $this->download_url;
    }

    /**
     * Set the value of download_url
     *
     * @return  self
     */
    public function setDownloadUrl($download_url)
    {
        $this->download_url = $download_url;

        return $this;
    }

    /**
     * Get the value of size_in_bytes
     */
    public function getSizeInBytes()
    {
        return $this->size_in_bytes;
    }

    /**
     * Set the value of size_in_bytes
     *
     * @return  self
     */
    public function setSizeInBytes($size_in_bytes)
    {
        $this->size_in_bytes = $size_in_bytes;

        return $this;
    }

    /**
     * Get the value of commits_handled
     */
    public function getCommitsHandled(): bool
    {
        return $this->commits_handled;
    }

    /**
     * Set the value of commits_handled
     *
     * @return  self
     */
    public function setCommitsHandled(bool $commits_handled)
    {
        $this->commits_handled = $commits_handled;

        return $this;
    }

    /**
     * Get the value of commits
     */
    public function getCommits()
    {
        return $this->commits;
    }

    /**
     * Get the value of linked_news_post
     */
    public function getLinkedNewsPost(): ?NewsArticle
    {
        return $this->linked_news_post;
    }

    /**
     * Set the value of linked_news_post
     */
    public function setLinkedNewsPost(?NewsArticle $linked_news_post): self
    {
        $this->linked_news_post = $linked_news_post;

        return $this;
    }

    /**
     * Get the value of version
     */
    public function getVersion(): ?string
    {
        return $this->version;
    }

    /**
     * Set the value of version
     */
    public function setVersion(?string $version): self
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get the value of mirrors
     */
    public function getMirrors()
    {
        return $this->mirrors;
    }

    public function getVersionParts(): ?array
    {
        $version = $this->getVersion();

        if (\is_string($version) && \preg_match('/([0-9]+)\.([0-9]+)\.([0-9]+)/', $version, $matches)) {
            return [
                'major' => (int)$matches[1],
                'minor' => (int)$matches[2],
                'patch' => (int)$matches[3],
            ];
        }

        return null;
    }

    public function getVersionMajorMinor(): ?string
    {
        $version_parts = $this->getVersionParts();

        if ($version_parts) {
            return "{$version_parts['major']}.{$version_parts['minor']}";
        }

        return null;
    }
}
