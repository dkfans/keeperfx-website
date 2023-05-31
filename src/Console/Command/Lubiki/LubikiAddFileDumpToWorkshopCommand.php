<?php

namespace App\Console\Command\Lubiki;

use App\Entity\GithubRelease;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;
use App\Entity\WorkshopItem;
use App\Enum\WorkshopCategory;
use Xenokore\Utility\Helper\DirectoryHelper;
use App\Entity\WorkshopFile;
use App\Entity\WorkshopImage;
use App\Enum\WorkshopScanStatus;
use URLify;

class LubikiAddFileDumpToWorkshopCommand extends Command
{
    private EntityManager $em;

    private array $kfx_releases = [];

    public function __construct(EntityManager $em) {
        $this->em = $em;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("lubiki:add-file-dump-to-workshop")
            ->setDescription("Add Lubiki file dump to Workshop")
            ->addArgument('path', InputArgument::REQUIRED, 'Directory containing Lubiki stuff');
    }

    private function getKfxReleaseByString(string $string): GithubRelease|null
    {
        foreach($this->kfx_releases as $name => $gh_release){
            if(\strpos(\strtolower($name), \strtolower($string)) !== false){
                return $gh_release;
            }
        }

        return $this->kfx_releases['KeeperFX 0.4.8 Build 2154'];
    }

    private function normalizeBigStringNFO(string $string): string
    {
        $string = \str_replace("\r", '', $string);
        $string = \str_replace("\n", ', ', $string);
        $string = \preg_replace('/\h{2,}/', ' ', $string);
        $string = \preg_replace('/,{2,}/', ',', $string);
        $string = \trim($string, ", \t\n\r\0\x0B");
        return $string;
    }

    private function getInfoFromNFO(string $contents): array
    {
        $data = [
            'name'           => null,
            'version'        => null,
            'author'         => null,
            'creation_date'  => null,
            'pool'           => null,
            'objects'        => null,
            'description'    => null,
            'min_game_build' => null,
        ];

        if(\preg_match('/Name\: (.+) \d+\./', $contents, $matches)){
            $data['name'] = $matches[1];
        }

        if(\preg_match('/Name\: .+ (\d+\.\d+\.\d+)/', $contents, $matches)){
            $data['version'] = $matches[1];
        }

        if(\preg_match('/Author\: (.+?)(,|$)/', $contents, $matches)){
            $data['author'] = $matches[1];
        }

        if(\preg_match('/Author\: .+?\, Created on ([\w\ ]+)/', $contents, $matches)){
            $date = \DateTime::createFromFormat("d M Y", $matches[1]);
            if($date){
                $data['creation_date'] = $date;
            }
        }

        if(\preg_match('/Pool\: (.+?)Objects/s', $contents, $matches)){
            $pool = $this->normalizeBigStringNFO($matches[1]);
            if($pool){
                $data['pool'] = $pool;
            }
        }

        if(\preg_match('/Objects on map\:(.+?)Description/s', $contents, $matches)){
            $objects = $this->normalizeBigStringNFO($matches[1]);
            if($objects){
                $data['objects'] = $objects;
            }
        }

        if(\preg_match('/Description\:(.+)/s', $contents, $matches)){
            $description = $matches[1];

            if(\preg_match('/Required version\: (.+)/s', $description, $matches)){
                $gh_release = $this->getKfxReleaseByString(trim($matches[1]));
                if($gh_release){
                    $data['min_game_build'] = $gh_release->getId();
                }

                $description = str_replace($matches[0], '', $description);
            }

            if(strpos($description, 'No description.') === false){
                $description = \preg_replace('/\h{2,}/', ' ', $description);
                $description = \preg_replace('/,{2,}/', ',', $description);
                $description = \trim($description);

                $data['description'] = $description;
            }
        }

        return $data;
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln("[>] Getting stable releases from DB");

        $gh_releases = $this->em->getRepository(GithubRelease::class)->findAll();
        if(!$gh_releases){
            $output->writeln("[-] <error>No KFX stable releases in DB...</error>");
            return Command::FAILURE;
        }
        foreach($gh_releases as $gh_release){

            // Ignore 0.5.0 as 0.5.0b should be suggested
            if($gh_release->getName() === 'KeeperFX 0.5.0 Build 3080'){
                continue;
            }

            $this->kfx_releases[$gh_release->getName()] = $gh_release;
        }

        $output->writeln("[>] Handling Lubiki stuff");

        // Get path
        $path = \rtrim((string) $input->getArgument('path'), ' \\/');
        if(!$path){
            $output->writeln("[-] <error>No path set</error>");
            return Command::FAILURE;
        }
        $output->writeln("[+] Path: {$path}");

        // Check path
        if(!is_dir($path) || !\is_readable($path)){
            $output->writeln("[-] <error>Not found or accessible!</error>");
            return Command::FAILURE;
        }

        // Make sure DK1 maps are present
        $map_dir = $path . '/dk1_maps';
        if(!is_dir($map_dir) || !\is_readable($map_dir)){
            $output->writeln("[-] <error>'dk1_maps' directory not found or accessible!</error>");
            return Command::FAILURE;
        }
        $output->writeln("[+] 'dk1_maps' directory found");

        // Get map info files
        $nfo_file_list       = \glob($map_dir . '/*.nfo');
        $nfo_file_list_count = \count($nfo_file_list);
        if($nfo_file_list_count < 1){
            $output->writeln("[-] No maps found...");
            return Command::FAILURE;
        }
        $output->writeln("[+] Maps: {$nfo_file_list_count}");

        foreach($nfo_file_list as $nfo_file)
        {
            $filename_nfo = \basename($nfo_file);
            $filename_name = \pathinfo($filename_nfo, \PATHINFO_FILENAME);
            $output->writeln("[>] Handling: '<info>{$filename_name}</info>'");

            // Get map number
            $filename_exp = \explode('_', $filename_name);
            $map_number = (int) $filename_exp[0];
            if($map_number < 100 || $map_number > 32000){
                $output->writeln("[-] Unable to grab normal map number");
                continue;
            }
            $output->writeln("[+] Map number: <info>{$map_number}</info>");

            // Get file
            $file = $map_dir . '/' . $filename_name . '.zip';
            if(!\file_exists($file)){
                $output->writeln("[-] File does not exist: <info>{$file}</info>");
                continue;
            }

            // Load nfo file
            $nfo_contents = @\file_get_contents($nfo_file);
            if(!$nfo_contents){
                $output->writeln("[-] Unable to load file: <info>{$nfo_file}</info>");
                continue;
            }

            $nfo = $this->getInfoFromNFO($nfo_contents);

            $output->writeln("[>] Map name: '<info>{$nfo['name']}</info>'");

            $workshop_item = new WorkshopItem();
            $workshop_item->setName($nfo['name']);
            $workshop_item->setSubmitter(null); // null = KeeperFX Team
            $workshop_item->setCategory(WorkshopCategory::Map);
            $workshop_item->setMinGameBuild($nfo['min_game_build']);
            $workshop_item->setOriginalAuthor($nfo['author']);
            $workshop_item->setOriginalCreationDate($nfo['creation_date']);
            $workshop_item->setIsPublished(true);
            $workshop_item->setMapNumber($map_number);

            $description = '';
            if($nfo['description']){
                $description = $nfo['description'] . PHP_EOL . PHP_EOL;
            }
            if($nfo['pool'] || $nfo['objects']){
                if($nfo['pool']){
                    $description .= '- **Creature Pool**: ' . $nfo['pool'];
                    if($nfo['objects']){
                        $description .= PHP_EOL;
                    }
                }
                if($nfo['objects']){
                    $description .= '- **Objects**: ' . $nfo['objects'];
                }
            }

            $workshop_item->setDescription(trim($description));
            $workshop_item->setInstallInstructions('Read the [Playing Custom Maps](/wiki/Playing-Custom-Maps) documentation page for information on how to play this map.');

            // Add to DB
            $this->em->persist($workshop_item);
            $this->em->flush();
            $output->writeln("[+] Added to database!");

            // Define directories for files
            $workshop_item_dir        = $_ENV['APP_WORKSHOP_STORAGE'] . '/' . $workshop_item->getId();
            $workshop_item_files_dir  = $workshop_item_dir . '/files';
            $workshop_item_images_dir = $workshop_item_dir . '/images';

            // Create directories for files
            if(!DirectoryHelper::create($workshop_item_dir)){
                throw new \Exception('Failed to create workshop item storage dir');
            }
            if(!DirectoryHelper::create($workshop_item_files_dir)){
                throw new \Exception('Failed to create workshop item files dir'); // TODO: move during migration
            }
            if(!DirectoryHelper::create($workshop_item_images_dir)){
                throw new \Exception('Failed to create workshop item images dir');
            }


            // Generate storage filename and path
            $storage_filename = md5(time()) . '__zip';
            $storage_path = $workshop_item_files_dir . '/' . $storage_filename;

            // Copy file
            if(!\copy($file, $storage_path)){
                $output->writeln("[-] <error>Failed to copy file</error>");
                continue;
            }

            // Generate new fancy download name
            $download_filename = URLify::slug($nfo['name']);
            if($nfo['version']){
                $download_filename .= '_v' . $nfo['version'];
            }
            $download_filename .= '.zip';

            // Add file to DB
            $workshop_file = new WorkshopFile();
            $workshop_file->setFilename($download_filename);
            $workshop_file->setStorageFilename($storage_filename);
            $workshop_file->setItem($workshop_item);
            $workshop_file->setSize(\filesize($storage_path));
            $workshop_file->setWeight(0);
            $workshop_file->setScanStatus(WorkshopScanStatus::SCANNED);
            $this->em->persist($workshop_file);

            // Get image filename and path
            $image_filename = $filename_name . '.png';
            $image_path     = $map_dir . '/' . $image_filename;
            $image_new_path = $workshop_item_images_dir . '/' . $image_filename;

            // Check if image exists
            if(file_exists($image_path)){

                // Copy image
                if(!\copy($image_path, $image_new_path)){
                    $output->writeln("[-] <error>Failed to copy image</error>");
                    continue;
                }

                // Get image width and height
                $width  = null;
                $height = null;
                $size   = @\getimagesize($image_new_path);
                if($size && \is_array($size)){
                    $width  = $size[0];
                    $height = $size[1];
                }

                // Add image to DB
                $workshop_image = new WorkshopImage();
                $workshop_image->setFilename($image_filename);
                $workshop_image->setItem($workshop_item);
                $workshop_image->setWidth($width);
                $workshop_image->setHeight($height);
                $workshop_image->setWeight(0);
                $this->em->persist($workshop_image);
            }
        }

        $this->em->flush();

        return Command::SUCCESS;
    }
}
