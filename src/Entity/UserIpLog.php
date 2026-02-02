<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class UserIpLog
{

    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'ip_logs')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column]
    private string $ip;

    #[ORM\Column]
    private \DateTime $first_seen_timestamp;

    #[ORM\Column]
    private \DateTime $last_seen_timestamp;

    #[ORM\Column(nullable: true)]
    private string|null $country = null;

    #[ORM\Column(nullable: true)]
    private bool|null $is_proxy = null;

    #[ORM\Column(nullable: true)]
    private bool|null $is_hosting = null;

    #[ORM\Column(nullable: true)]
    private string|null $host_name = null;

    #[ORM\Column(nullable: true)]
    private string|null $isp = null;

    #[ORM\PrePersist]
    public function onPrePersist()
    {
        $this->first_seen_timestamp = new \DateTime("now");
        $this->last_seen_timestamp  = new \DateTime("now");
    }

    /**
     * Get the value of id
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the value of user
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Set the value of user
     */
    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get the value of ip
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * Set the value of ip
     */
    public function setIp(string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get the value of host_name
     */
    public function getHostName(): ?string
    {
        return $this->host_name;
    }

    /**
     * Set the value of host_name
     */
    public function setHostName(?string $host_name): self
    {
        $this->host_name = $host_name;

        return $this;
    }

    /**
     * Get the value of first_seen_timestamp
     */
    public function getFirstSeenTimestamp(): \DateTime
    {
        return $this->first_seen_timestamp;
    }

    /**
     * Set the value of first_seen_timestamp
     */
    public function setFirstSeenTimestamp(\DateTime $first_seen_timestamp): self
    {
        $this->first_seen_timestamp = $first_seen_timestamp;

        return $this;
    }

    /**
     * Get the value of last_seen_timestamp
     */
    public function getLastSeenTimestamp(): \DateTime
    {
        return $this->last_seen_timestamp;
    }

    /**
     * Set the value of last_seen_timestamp
     */
    public function setLastSeenTimestamp(\DateTime $last_seen_timestamp): self
    {
        $this->last_seen_timestamp = $last_seen_timestamp;

        return $this;
    }

    /**
     * Get the value of country
     */
    public function getCountry(): ?string
    {
        return $this->country;
    }

    /**
     * Set the value of country
     */
    public function setCountry(?string $country): self
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get the value of is_proxy
     */
    public function isProxy(): ?bool
    {
        return $this->is_proxy;
    }

    /**
     * Set the value of is_proxy
     */
    public function setIsProxy(?bool $is_proxy): self
    {
        $this->is_proxy = $is_proxy;

        return $this;
    }

    /**
     * Get the value of is_hosting
     */
    public function isHosting(): ?bool
    {
        return $this->is_hosting;
    }

    /**
     * Set the value of is_hosting
     */
    public function setIsHosting(?bool $is_hosting): self
    {
        $this->is_hosting = $is_hosting;

        return $this;
    }

    /**
     * Get the value of isp
     */
    public function getIsp(): ?string
    {
        return $this->isp;
    }

    /**
     * Set the value of isp
     */
    public function setIsp(?string $isp): self
    {
        $this->isp = $isp;

        return $this;
    }
}
