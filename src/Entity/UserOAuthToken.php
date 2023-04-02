<?php

namespace App\Entity;

use App\Enum\OAuthProviderType;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class UserOAuthToken {

    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\ManyToOne(targetEntity: 'User')]
    private User $user;

    #[ORM\Column(type: 'string', enumType: OAuthProviderType::class)]
    private OAuthProviderType $provider_type;

    #[ORM\Column]
    private string $token;

    #[ORM\Column]
    private string $uid;

    #[ORM\Column]
    private string $refresh_token;

    #[ORM\Column]
    private \DateTime $created_timestamp;

    #[ORM\Column]
    private \DateTime $expires_timestamp;

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
     * Get the value of provider
     */
    public function getProviderType(): OAuthProviderType
    {
        return $this->provider_type;
    }

    /**
     * Set the value of provider
     */
    public function setProviderType(OAuthProviderType $provider): self
    {
        $this->provider_type = $provider;

        return $this;
    }

    /**
     * Get the value of token
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * Set the value of token
     */
    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get the value of uid
     */
    public function getUid(): string
    {
        return $this->uid;
    }

    /**
     * Set the value of uid
     */
    public function setUid(string $uid): self
    {
        $this->uid = $uid;

        return $this;
    }

    /**
     * Get the value of refresh_token
     */
    public function getRefreshToken(): string
    {
        return $this->refresh_token;
    }

    /**
     * Set the value of refresh_token
     */
    public function setRefreshToken(string $refresh_token): self
    {
        $this->refresh_token = $refresh_token;

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

    /**
     * Get the value of expires_timestamp
     */
    public function getExpiresTimestamp(): \DateTime
    {
        return $this->expires_timestamp;
    }

    /**
     * Set the value of expires_timestamp
     */
    public function setExpiresTimestamp(\DateTime $expires_timestamp): self
    {
        $this->expires_timestamp = $expires_timestamp;

        return $this;
    }
}
