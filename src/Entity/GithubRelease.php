<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class GithubRelease {

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
    private string $download_url;

    #[ORM\Column]
    private bool $commits_handled = false;

    #[ORM\OneToMany(targetEntity: GitCommit::class, mappedBy: "release")]
    private $commits;

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
}
