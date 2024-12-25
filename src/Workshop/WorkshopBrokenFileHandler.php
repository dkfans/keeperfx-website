<?php

namespace App\Workshop;

use App\Entity\WorkshopBrokenFile;
use App\Entity\WorkshopFile;
use App\Entity\WorkshopItem;

use App\Config\Config;
use Doctrine\ORM\EntityManager;

use Psr\SimpleCache\CacheInterface;

class WorkshopBrokenFileHandler {

    public function __construct(
        private EntityManager $em,
        private WorkshopCache $workshop_cache,
    ){}

    public function handleItem(WorkshopItem $item, bool $flush_after = true): void
    {
        // Get hashes for all broken files
        $hashes = $this->getBrokenFileHashes();
        if(\count($hashes) === 0)
        {
            return;
        }

        // Get the last file for this item
        $last_file = $this->em->getRepository(WorkshopFile::class)->findOneBy(['item' => $item], ['weight' => 'ASC'], 1);
        if($last_file === null){
            return;
        }

        // Check if last file is a broken file
        if(\in_array($this->getWorkshopFileHash($last_file), $hashes)){

            // Set file (and item) as broken
            $this->setFileBroken($last_file, true, false);
            $this->setItemLastFileIsBroken($item, true, $flush_after);

        } else {

            // Item is not considered broken
            $this->setItemLastFileIsBroken($item, false, $flush_after);
        }
    }

    private function setFileBroken(WorkshopFile $file, bool $is_broken, bool $flush_after)
    {
        $file->setIsBroken($is_broken);

        if($flush_after){
            $this->em->flush();
        }
    }

    private function setItemLastFileIsBroken(WorkshopItem $item, bool $is_broken, bool $flush_after)
    {
        // Only handle this if the item broken status is changed
        if($is_broken !== $item->isLastFileBroken())
        {
            // Set the 'is last file broken' on the item
            $item->setIsLastFileBroken($is_broken);

            // Save changes to the DB
            if($flush_after){
                $this->em->flush();
            }

            // Clear the workshop browse page cache so it reflects the new data
            $this->workshop_cache->clearAllCachedBrowsePageData();
        }

    }

    public function markFileAsBroken(WorkshopFile $workshop_file, bool $flush_after = true)
    {
        $workshop_item     = $workshop_file->getItem();
        $original_filename = $workshop_file->getFilename();
        $hash              = $this->getWorkshopFileHash($workshop_file);

        // Make sure we have a hash for the file
        if($hash === null){
            return;
        }

        // Check if hash is already stored
        $already_marked = $this->em->getRepository(WorkshopBrokenFile::class)->findOneBy(['hash' => $hash]);
        if($already_marked === null){

            // Create a broken file entity if item hash combo is not stored yet
            $broken_file = new WorkshopBrokenFile();
            $broken_file->setOriginalItem($workshop_item);
            $broken_file->setHash($hash);
            $broken_file->setOriginalFilename($original_filename);
            $this->em->persist($broken_file);
        }

        // Loop trough all files of the workshop item
        $last_file = null;
        $files = $this->em->getRepository(WorkshopFile::class)->findBy(['item' => $workshop_item], ['weight' => 'DESC']);
        foreach($files as $file)
        {
            // If the hash matches the one we're marking we'll mark this file as broken
            if($this->getWorkshopFileHash($file) === $hash){
                $file->setIsBroken(true);
            }

            $last_file = $file;
        }

        // Mark workshop item's last file as broken if it is
        $workshop_item->setIsLastFileBroken(
            $last_file->isBroken()
        );

        // Flush changes to DB
        if($flush_after) {
            $this->em->flush();
        }
    }

    public function unmarkFileAsBroken(WorkshopFile $workshop_file, bool $flush_after = true)
    {
        $workshop_item = $workshop_file->getItem();
        $hash          = $this->getWorkshopFileHash($workshop_file);

        // Make sure we have a hash for the file
        if($hash === null){
            return;
        }

        // Check if hash in the DB and remove its definition
        $broken_file = $this->em->getRepository(WorkshopBrokenFile::class)->findOneBy(['hash' => $hash]);
        if($broken_file !== null){
            $this->em->remove($broken_file);
        }

        // Loop trough all files of the workshop item
        $last_file = null;
        $files = $this->em->getRepository(WorkshopFile::class)->findBy(['item' => $workshop_item], ['weight' => 'DESC']);
        foreach($files as $file)
        {
            // If the hash matches the one we're marking we'll mark this file as broken
            if($this->getWorkshopFileHash($file) === $hash){
                $file->setIsBroken(false);
            }

            $last_file = $file;
        }

        // Mark workshop item's last file as broken if it is
        $workshop_item->setIsLastFileBroken(
            $last_file->isBroken()
        );

        // Flush changes to DB
        if($flush_after){
            $this->em->flush();
        }
    }

    private function getBrokenFileHashes(): array
    {
        $hashes = [];

        $broken_files = $this->em->getRepository(WorkshopBrokenFile::class)->findAll();

        foreach($broken_files as $broken_file) {
            $hashes[] = $broken_file->getHash();
        }

        return $hashes;
    }

    private function getWorkshopFileHash(WorkshopFile $file): string|null
    {
        // Get file path
        $workshop_item_dir       = Config::get('storage.path.workshop') . '/' . $file->getItem()->getId();
        $workshop_item_files_dir = $workshop_item_dir . '/files';
        $workshop_file_path      = $workshop_item_files_dir . '/' . $file->getStorageFilename();

        // Make sure the file exists
        if(!\file_exists($workshop_file_path)) {
            return null;
        }

        // Get the hash of the file
        $hash = \sha1_file($workshop_file_path);

        // Convert false to null and return
        return $hash === false ? null : $hash;
    }
}
