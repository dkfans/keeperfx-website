<?php

namespace App\Entity;

use App\Enum\WorkshopScanStatus;

use App\Entity\User;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class WorkshopFile {

    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\ManyToOne(targetEntity: WorkshopItem::class, inversedBy: 'files')]
    private WorkshopItem $item;

    #[ORM\Column]
    private string $filename;

    #[ORM\Column]
    private string $storage_filename;

    #[ORM\Column(type: 'integer')]
    private int $download_count = 0;

    #[ORM\Column(nullable: true)]
    private string|null $version = null;

    #[ORM\Column(type: 'integer', enumType: WorkshopScanStatus::class)]
    private WorkshopScanStatus $scan_status = WorkshopScanStatus::NOT_SCANNED_YET;

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
     * Get the value of storage_filename
     */
    public function getStorageFilename(): string
    {
        return $this->storage_filename;
    }

    /**
     * Set the value of original_filename
     */
    public function setStorageFilename(string $storage_filename): self
    {
        $this->storage_filename = $storage_filename;

        return $this;
    }

    /**
     * Get the value of download_count
     */
    public function getDownloadCount(): int
    {
        return $this->download_count;
    }

    /**
     * Set the value of download_count
     */
    public function setDownloadCount(int $download_count): self
    {
        $this->download_count = $download_count;

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
     * Get the value of scan_status
     */
    public function getScanStatus(): WorkshopScanStatus
    {
        return $this->scan_status;
    }

    /**
     * Set the value of scan_status
     */
    public function setScanStatus(WorkshopScanStatus $scan_status): self
    {
        $this->scan_status = $scan_status;

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
