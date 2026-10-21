<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class UserAward
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'awards')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column]
    private string $award_id;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $info = null;

    #[ORM\Column(type: 'integer')]
    private int $weight = 1000;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?User $giver = null;

    #[ORM\Column]
    private \DateTime $created_timestamp;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->created_timestamp = new \DateTime('now');
    }

    /**
     * Get the value of id.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the value of user.
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Set the value of user.
     */
    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get the value of award_id.
     */
    public function getAwardId(): string
    {
        return $this->award_id;
    }

    /**
     * Set the value of award_id.
     */
    public function setAwardId(string $award_id): self
    {
        $this->award_id = $award_id;

        return $this;
    }

    /**
     * Get the value of info.
     */
    public function getInfo(): ?string
    {
        return $this->info;
    }

    /**
     * Set the value of info.
     */
    public function setInfo(?string $info): self
    {
        $this->info = $info;

        return $this;
    }

    /**
     * Get the value of weight.
     */
    public function getWeight(): int
    {
        return $this->weight;
    }

    /**
     * Set the value of weight.
     */
    public function setWeight(int $weight): self
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * Get the value of giver.
     */
    public function getGiver(): ?User
    {
        return $this->giver;
    }

    /**
     * Set the value of giver.
     */
    public function setGiver(?User $giver): self
    {
        $this->giver = $giver;

        return $this;
    }

    /**
     * Get the value of created_timestamp.
     */
    public function getCreatedTimestamp(): \DateTime
    {
        return $this->created_timestamp;
    }
}
