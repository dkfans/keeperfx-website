<?php

namespace App\Entity;

use App\Enum\WorkshopCategory;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class WorkshopBrokenFile
{

    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\ManyToOne(targetEntity: WorkshopItem::class, inversedBy: 'broken_file')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private WorkshopItem|null $original_item = null;

    #[ORM\Column]
    private string $original_filename;

    #[ORM\Column()]
    private string $hash;

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
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the value of item
     */
    public function getOriginalItem(): WorkshopItem|null
    {
        return $this->original_item;
    }

    /**
     * Set the value of item
     */
    public function setOriginalItem(WorkshopItem|null $item): self
    {
        $this->original_item = $item;

        return $this;
    }

    /**
     * Get the value of original_filename
     */
    public function getOriginalFilename(): string
    {
        return $this->original_filename;
    }

    /**
     * Set the value of original_filename
     */
    public function setOriginalFilename(string $original_filename): self
    {
        $this->original_filename = $original_filename;

        return $this;
    }

    /**
     * Get the value of hash
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * Set the value of hash
     */
    public function setHash(string $hash): self
    {
        $this->hash = $hash;

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
}
