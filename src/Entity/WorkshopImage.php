<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class WorkshopImage
{

    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\ManyToOne(targetEntity: WorkshopItem::class, inversedBy: 'images')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private WorkshopItem $item;

    #[ORM\Column]
    private string $filename;

    #[ORM\Column(type: 'integer')]
    private int $weight = 0;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $width = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $height = null;

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
     * Get the value of x
     */
    public function getWidth(): ?int
    {
        return $this->width;
    }

    /**
     * Set the value of x
     */
    public function setWidth(?int $width): self
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Get the value of y
     */
    public function getHeight(): ?int
    {
        return $this->height;
    }

    /**
     * Set the value of y
     */
    public function setHeight(?int $height): self
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Get the value of weight
     */
    public function getWeight(): int
    {
        return $this->weight;
    }

    /**
     * Set the value of order
     */
    public function setWeight(int $weight): self
    {
        $this->weight = $weight;

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
