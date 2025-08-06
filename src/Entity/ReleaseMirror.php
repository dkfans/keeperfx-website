<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\GithubRelease;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class ReleaseMirror
{

    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private int|null $id = null;

    #[ORM\Column]
    private string $url;

    #[ORM\ManyToOne(targetEntity: 'GithubRelease')]
    private GithubRelease $release;

    /**
     * Get the value of id
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set the value of id
     */
    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of url
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Set the value of url
     */
    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get the value of release
     */
    public function getRelease(): GithubRelease
    {
        return $this->release;
    }

    /**
     * Set the value of release
     */
    public function setRelease(GithubRelease $release): self
    {
        $this->release = $release;

        return $this;
    }
}
