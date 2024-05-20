<?php

namespace App\Entity;

use App\Enum\BanType;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Ban {

    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'integer', enumType: BanType::class)]
    private BanType $type;

    #[ORM\Column]
    private string $pattern;

    #[ORM\Column(type: 'text', nullable:true)]
    private string|null $reason = null;

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
     * Get the value of type
     */
    public function getType(): BanType
    {
        return $this->type;
    }

    /**
     * Set the value of type
     */
    public function setType(BanType $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get the value of pattern
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * Set the value of pattern
     */
    public function setPattern(string $pattern): self
    {
        $this->pattern = $pattern;

        return $this;
    }

    /**
     * Get the value of reason
     */
    public function getReason(): ?string
    {
        return $this->reason;
    }

    /**
     * Set the value of reason
     */
    public function setReason(?string $reason): self
    {
        $this->reason = $reason;

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
