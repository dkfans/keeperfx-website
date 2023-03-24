<?php

namespace App\Workshop;

use App\Entity\User;
use App\Entity\WorkshopItem;
use App\Entity\GithubRelease;

use App\Enum\WorkshopType;

use Doctrine\ORM\EntityManager;

use App\Workshop\Exception\WorkshopException;

class WorkshopItemInstance {

    public const DEFAULT_TYPE = WorkshopType::Other;

    public function __construct(
        private WorkshopItem $item,
        private EntityManager $em,
    ){}

    public function setName(string $name): self
    {
        if(!$this->item){
            throw new WorkshopException('Workshop item not set');
        }

        $this->item->setName(\preg_replace('/\s+/', ' ', \trim($name)));
        return $this;
    }

    public function setType(WorkshopType|int $type): self
    {
        if(!$this->item){
            throw new WorkshopException('Workshop item not set');
        }

        if($type instanceof WorkshopType){
            $this->item->setType($type);
        } else {
            $try_type = WorkshopType::tryFrom($type);
            if($try_type === null){
                $this->item->setType(self::DEFAULT_TYPE);
            } else {
                $this->item->setType($try_type);
            }
        }

        return $this;
    }

    public function setSubmitter(User $user): self
    {
        if(!$this->item){
            throw new WorkshopException('Workshop item not set');
        }

        $this->item->setSubmitter($user);
        return $this;
    }

    public function setDescription(string|null $description): self
    {
        if(!$this->item){
            throw new WorkshopException('Workshop item not set');
        }

        if($description === null || empty($description)){
            $this->item->setDescription(null);
        } else {
            $this->item->setDescription($description);
        }

        return $this;
    }

    public function setInstallInstructions(string|null $install_instructions): self
    {
        if(!$this->item){
            throw new WorkshopException('Workshop item not set');
        }

        if($install_instructions === null || empty($install_instructions)){
            $this->item->setInstallInstructions(null);
        } else {
            $this->item->setInstallInstructions($install_instructions);
        }

        return $this;
    }

    public function setOriginalAuthor(string|null $original_author): self
    {
        if(!$this->item){
            throw new WorkshopException('Workshop item not set');
        }

        if($original_author === null || empty($original_author)){
            $this->item->setOriginalAuthor(null);
        } else {
            $this->item->setOriginalAuthor($original_author);
        }

        return $this;
    }

    public function setOriginalCreationDate(\DateTime|string|null $original_creation_date): self
    {
        if(!$this->item){
            throw new WorkshopException('Workshop item not set');
        }

        if($original_creation_date instanceof \DateTime){
            $this->item->setOriginalCreationDate($original_creation_date);
        } elseif($original_creation_date === null || empty($original_creation_date)){
            $this->item->setOriginalCreationDate(null);
        } else {
            try {
                $datetime = new \DateTime($original_creation_date);
                if($datetime){
                    $this->item->setOriginalCreationDate($datetime);
                }
            } catch (WorkshopException $ex){
                $this->item->setOriginalCreationDate(null);
            }
        }

        return $this;
    }

    public function setMinGameBuild(GithubRelease|int|null $build): self
    {
        if(!$this->item){
            throw new WorkshopException('Workshop item not set');
        }

        if($build === null || $build instanceof GithubRelease){
            $this->item->setMinGameBuild($build);
        } else {
            $min_game_build = $this->em->getRepository(GithubRelease::class)->find($build);
            if($min_game_build){
                $this->item->setMinGameBuild($min_game_build);
            } else {
                $this->item->setMinGameBuild(null);
            }
        }

        return $this;
    }

    public function getStorageDir(): string
    {
        if(!$this->item){
            throw new WorkshopException('Workshop item not set.');
        }

        if($this->item->getId() === null){
            throw new WorkshopException('Workshop item does not have an ID yet. Save it first.');
        }

        $dir = Workshop::getStorageDir() . '/' . $this->item->getId();

        if(!\is_dir($dir)){
            if(!@mkdir($dir)){
                throw new WorkshopException("Failed to create storage dir for workshop item with id {$this->item->getId()}.");
            }
        }

        return $dir;
    }

    public function getScreenshotsDir(): string
    {
        if(!$this->item){
            throw new WorkshopException('Workshop item not set.');
        }

        if($this->item->getId() === null){
            throw new WorkshopException('Workshop item does not have an ID yet. Save it first.');
        }

        $dir = Workshop::getStorageDir() . '/' . $this->item->getId() . '/screenshots';

        if(!\is_dir($dir)){
            if(!@mkdir($dir)){
                throw new WorkshopException("Failed to create screenshots dir for workshop item with id {$this->item->getId()}.");
            }
        }

        return $dir;
    }

    public function __call($method, $args) {
        if(!\method_exists($this->item, $method)){
            throw new \Exception("Method does not exist: {$method}");
        }

        return $this->item->$method(...$args);
    }

    public function save(): void
    {
        if(!$this->item){
            throw new WorkshopException('Workshop item not set');
        }

        $this->em->persist($this->item);
        $this->em->flush();
    }

    public function delete(): void
    {
        $this->em->remove($this->item);
        $this->em->flush();
        $this->item = null;
    }

}
