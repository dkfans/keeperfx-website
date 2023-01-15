<?php

namespace App\Entity;

use App\Enum\UserRole;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class User {

    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column]
    private string $username;

    #[ORM\Column]
    private string $password;

    #[ORM\Column(nullable: true)]
    private string|null $email = null;

    #[ORM\Column(nullable: true)]
    private string|null $avatar = null;

    #[ORM\Column(type: 'integer', enumType: UserRole::class)]
    private UserRole $role = UserRole::User;

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
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set the value of password
     *
     * @return  self
     */
    public function setPassword(string $password)
    {
        $this->password = \password_hash($password, PASSWORD_DEFAULT);

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
    public function setRole(UserRole|int $role): self
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
}
