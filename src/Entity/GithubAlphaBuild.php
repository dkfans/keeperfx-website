<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class GithubAlphaBuild {

    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private int|null $id = null;

    #[ORM\Column]
    private int $artifact_id;

    #[ORM\Column]
    private string $name;

    #[ORM\Column]
    private string $workflow_title;

    #[ORM\Column]
    private string $filename;

    #[ORM\Column]
    private \DateTime $timestamp;

    #[ORM\Column]
    private int $size_in_bytes;

    #[ORM\Column]
    private bool $is_available = true;

    /**
     * Get the value of id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the value of artifact_id
     */
    public function getArtifactId()
    {
        return $this->artifact_id;
    }

    /**
     * Set the value of artifact_id
     *
     * @return  self
     */
    public function setArtifactId($artifact_id)
    {
        $this->artifact_id = $artifact_id;

        return $this;
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
     * Get the value of filename
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Set the value of filename
     *
     * @return  self
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;

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
     * Get the value of workflow_title
     */
    public function getWorkflowTitle()
    {
        return $this->workflow_title;
    }

    /**
     * Set the value of workflow_title
     *
     * @return  self
     */
    public function setWorkflowTitle($workflow_title)
    {
        $this->workflow_title = $workflow_title;

        return $this;
    }

    /**
     * Get the value of is_available
     */
    public function getIsAvailable()
    {
        return $this->is_available;
    }

    /**
     * Set the value of is_available
     *
     * @return  self
     */
    public function setIsAvailable($is_available)
    {
        $this->is_available = $is_available;

        return $this;
    }
}
