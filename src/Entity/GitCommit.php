<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\GithubRelease;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class GitCommit
{

    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private int|null $id = null;

    #[ORM\Column]
    private string $hash;

    #[ORM\ManyToOne(targetEntity: 'GithubRelease')]
    private GithubRelease $release;

    #[ORM\Column]
    private \DateTime $timestamp;

    #[ORM\Column]
    private string $message;

    /**
     * Get the value of id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the value of hash
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * Set the value of hash
     *
     * @return  self
     */
    public function setHash($hash)
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * Get the value of release
     */
    public function getRelease()
    {
        return $this->release;
    }

    /**
     * Set the value of release
     *
     * @return  self
     */
    public function setRelease(GithubRelease $release)
    {
        $this->release = $release;

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
     *
     * @return  self
     */
    public function setTimestamp(\DateTime $timestamp)
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * Get the value of message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set the value of message
     *
     * @return  self
     */
    public function setMessage(string $message)
    {
        $this->message = $message;

        return $this;
    }
}
