<?php

namespace App\Entity;

use App\Enum\ReleaseType;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class GameFileIndex {

    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private int|null $id = null;

    #[ORM\Column(type: 'string', enumType: ReleaseType::class)]
    private ReleaseType $release_type;

    #[ORM\Column]
    private string $version;

    #[ORM\Column(type: 'text')]
    private string $data;

    /**
     * Get the value of id
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set the value of id
     */
    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of release_type
     */
    public function getReleaseType(): ReleaseType
    {
        return $this->release_type;
    }

    /**
     * Set the value of release_type
     */
    public function setReleaseType(ReleaseType $release_type): self
    {
        $this->release_type = $release_type;

        return $this;
    }

    /**
     * Get the value of version
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Set the value of version
     */
    public function setVersion(string $version): self
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get the value of data
     */
    public function getData(): array|string|null
    {
        return \json_decode($this->data, true);
    }

    /**
     * Set the value of data
     */
    public function setData(array|null $data): self
    {
        $this->data = \json_encode($data);

        return $this;
    }
}
