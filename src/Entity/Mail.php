<?php

namespace App\Entity;

use App\Enum\MailStatus;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Mail {

    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column]
    private string $receiver;

    #[ORM\Column]
    private string $subject;

    #[ORM\Column(type: 'text', nullable:true, options:['charset'=>'utf8mb4', 'collation'=>'utf8mb4_unicode_ci'])]
    private string|null $body = null;

    #[ORM\Column(type: 'text', nullable:true, options:['charset'=>'utf8mb4', 'collation'=>'utf8mb4_unicode_ci'])]
    private string|null $html_body = null;

    #[ORM\Column(type: 'integer', enumType: MailStatus::class)]
    private MailStatus $status = MailStatus::NOT_SENT_YET;

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
     * Get the value of receiver
     */
    public function getReceiver(): string
    {
        return $this->receiver;
    }

    /**
     * Set the value of receiver
     */
    public function setReceiver(string $receiver): self
    {
        $this->receiver = $receiver;

        return $this;
    }

    /**
     * Get the value of status
     */
    public function getStatus(): MailStatus
    {
        return $this->status;
    }

    /**
     * Set the value of status
     */
    public function setStatus(MailStatus $status): self
    {
        $this->status = $status;

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
     * Get the value of subject
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * Set the value of subject
     */
    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get the value of body
     */
    public function getBody(): ?string
    {
        return $this->body;
    }

    /**
     * Set the value of body
     */
    public function setBody(?string $body): self
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Get the value of html_body
     */
    public function getHtmlBody(): ?string
    {
        return $this->html_body;
    }

    /**
     * Set the value of html_body
     */
    public function setHtmlBody(?string $html_body): self
    {
        $this->html_body = $html_body;

        return $this;
    }
}
