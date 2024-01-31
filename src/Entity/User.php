<?php

namespace App\Entity;

use App\Enum\UserRole;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class User {

    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(options:['charset'=>'utf8mb4', 'collation'=>'utf8mb4_unicode_ci'])]
    private string $username;

    #[ORM\Column(nullable: true)]
    private string|null $password;

    #[ORM\Column(nullable: true)]
    private string|null $email = null;

    #[ORM\Column(nullable: true)]
    private string|null $avatar = null;

    #[ORM\Column(nullable: true)]
    private string|null $avatar_small = null;

    #[ORM\Column(type: 'integer', enumType: UserRole::class)]
    private UserRole $role = UserRole::User;

    #[ORM\Column(nullable: true)]
    private string|null $country = null;

    #[ORM\Column]
    private \DateTime $created_timestamp;

    #[ORM\OneToMany(targetEntity: NewsArticle::class, mappedBy: 'author', cascade: ["remove"])]
    private Collection $news_articles;

    #[ORM\OneToMany(targetEntity: UserOAuthToken::class, mappedBy: 'user', cascade: ["remove"])]
    private Collection $connection_tokens;

    #[ORM\OneToMany(targetEntity: UserCookieToken::class, mappedBy: 'user', cascade: ["remove"])]
    private Collection $cookie_tokens;

    #[ORM\OneToMany(targetEntity: UserNotification::class, mappedBy: 'user', cascade: ["remove"])]
    private Collection $notifications;

    #[ORM\OneToMany(targetEntity: UserNotificationSetting::class, mappedBy: 'user', cascade: ["remove"])]
    private Collection $notification_settings;

    #[ORM\OneToMany(targetEntity: WorkshopItem::class, mappedBy: 'submitter', cascade: ["remove"])]
    private Collection $workshop_items;

    #[ORM\OneToMany(targetEntity: WorkshopComment::class, mappedBy: 'user', cascade: ["remove"])]
    private Collection $workshop_comments;

    #[ORM\OneToMany(targetEntity: WorkshopRating::class, mappedBy: 'user', cascade: ["remove"])]
    private Collection $workshop_ratings;

    #[ORM\OneToMany(targetEntity: WorkshopDifficultyRating::class, mappedBy: 'user', cascade: ["remove"])]
    private Collection $workshop_difficulty_ratings;

    public function __construct() {
        $this->news_articles               = new ArrayCollection();
        $this->connection_tokens           = new ArrayCollection();
        $this->cookie_tokens               = new ArrayCollection();
        $this->notifications               = new ArrayCollection();
        $this->notification_settings       = new ArrayCollection();
        $this->workshop_items              = new ArrayCollection();
        $this->workshop_comments           = new ArrayCollection();
        $this->workshop_ratings            = new ArrayCollection();
        $this->workshop_difficulty_ratings = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function onPrePersist()
    {
        $this->created_timestamp = new \DateTime("now");
    }

    /**
     * Get the value of id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the value of username
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set the value of username
     *
     * @return  self
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get the value of password
     */
    public function getPassword(): string|null
    {
        return $this->password;
    }

    /**
     * Set the value of password
     *
     * @return  self
     */
    public function setPassword(string|null $password)
    {
        if(\is_string($password)){
            $password = \password_hash($password, PASSWORD_DEFAULT);
        }

        $this->password = $password;
        return $this;
    }

    /**
     * Get the value of email
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set the value of email
     *
     * @return  self
     */
    public function setEmail(string|null $email): self
    {
        // Empty email addresses should become NULL
        if(empty($email)){
            $email = null;
        }

        $this->email = $email;

        return $this;
    }

    /**
     * Get the value of avatar
     */
    public function getAvatar():  string|null
    {
        return $this->avatar;
    }

    /**
     * Set the value of avatar
     *
     * @return  self
     */
    public function setAvatar(string|null $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }

    /**
     * Get the value of role
     */
    public function getRole(): UserRole
    {
        return $this->role;
    }

    /**
     * Set the value of role
     *
     * @return  self
     */
    public function setRole(UserRole $role): self
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Get the value of created_timestamp
     */
    public function getCreatedTimestamp()
    {
        return $this->created_timestamp;
    }

    /**
     * Set the value of created_timestamp
     *
     * @return  self
     */
    public function setCreatedTimestamp($created_timestamp)
    {
        $this->created_timestamp = $created_timestamp;

        return $this;
    }

    /**
     * Get the value of news_articles
     */
    public function getNewsArticles(): Collection
    {
        return $this->news_articles;
    }

    /**
     * Get the value of connection_tokens
     */
    public function getConnectionTokens(): Collection
    {
        return $this->connection_tokens;
    }

    /**
     * Get the value of cookie_tokens
     */
    public function getCookieTokens(): Collection
    {
        return $this->cookie_tokens;
    }

    /**
     * Get the value of notifications
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    /**
     * Get the value of notification_settings
     */
    public function getNotificationSettings(): Collection
    {
        return $this->notification_settings;
    }

    /**
     * Get the value of workshop_items
     */
    public function getWorkshopItems(): Collection
    {
        return $this->workshop_items;
    }

    /**
     * Get the value of workshop_comments
     */
    public function getWorkshopComments(): Collection
    {
        return $this->workshop_comments;
    }

    /**
     * Get the value of workshop_ratings
     */
    public function getWorkshopRatings(): Collection
    {
        return $this->workshop_ratings;
    }

    /**
     * Get the value of workshop_difficulty_ratings
     */
    public function getWorkshopDifficultyRatings(): Collection
    {
        return $this->workshop_difficulty_ratings;
    }

    /**
     * Get the value of avatar_small
     */
    public function getAvatarSmall(): ?string
    {
        return $this->avatar_small;
    }

    /**
     * Set the value of avatar_small
     */
    public function setAvatarSmall(?string $avatar_small): self
    {
        $this->avatar_small = $avatar_small;

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
}
