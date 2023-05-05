<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class WorkshopImage {

    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\ManyToOne(targetEntity: WorkshopItem::class, inversedBy: 'files')]
    private WorkshopItem $item;

    #[ORM\Column]
    private string $filename;

    #[ORM\Column(type: 'integer')]
    private int $order = 0;

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
     * Set the value of id
     */
    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of item
     */
    public function getItem(): WorkshopItem
    {
        return $this->item;
    }

    /**
     * Set the value of item
     */
    public function setItem(WorkshopItem $item): self
    {
        $this->item = $item;

        return $this;
    }

    /**
     * Get the value of filename
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * Set the value of filename
     */
    public function setFilename(string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }


    /**
     * Get the value of order
     */
    public function getOrder(): int
    {
        return $this->order;
    }

    /**
     * Set the value of order
     */
    public function setOrder(int $order): self
    {
        $this->order = $order;

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
