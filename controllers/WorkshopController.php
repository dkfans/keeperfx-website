<?php

namespace App\Controller;


use App\Enum\UserRole;
use App\Enum\WorkshopType;

use App\Entity\GithubRelease;
use App\Entity\WorkshopItem;
use App\Entity\WorkshopRating;
use App\Entity\WorkshopTag;

use URLify;
use App\Account;
use App\FlashMessage;
use App\Config\Config;
use App\Twig\Extension\WorkshopRatingTwigExtension;

use Slim\Csrf\Guard as CsrfGuard;
use Doctrine\ORM\EntityManager;
use Twig\Environment as TwigEnvironment;

use Slim\Psr7\UploadedFile;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Xenokore\Utility\Helper\DirectoryHelper;

use Slim\Exception\HttpNotFoundException;

class WorkshopController {

    private EntityManager $em;

    public function __construct(EntityManager $em){
        $this->em = $em;
    }

    private function getWorkshopOptions(): array
    {
        // TODO: improve the name of this function
        return [
            'types'                    => WorkshopType::cases(),
            'types_without_difficulty' => Config::get('app.workshop.item_types_without_difficulty'),
            'tags'                     => $this->em->getRepository(WorkshopTag::class)->findBy([], ['name' => 'ASC']),
            'builds'                   => $this->em->getRepository(GithubRelease::class)->findBy([], ['timestamp' => 'DESC']),
        ];
    }

    public function itemIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        FlashMessage $flash,
        EntityManager $em,
        $id,
        $slug = null
    ){
        // Check if workshop item exists
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($id);
        if(!$workshop_item){
            $flash->warning('The requested workshop item could not be found.');
            $response->getBody()->write(
                $twig->render('workshop/alert.workshop.html.twig', $this->getWorkshopOptions())
            );
            return $response;
        }

        // Make sure title slug is in URL and matches
        if(URLify::slug($workshop_item->getName()) !== $slug){
            $response = $response->withHeader('Location',
                '/workshop/item/' . $workshop_item->getId() . '/' . URLify::slug($workshop_item->getName())
            )->withStatus(302);
            return $response;
        }

        // Get item filesize
        $filepath = $_ENV['APP_WORKSHOP_STORAGE'] . '/' . $workshop_item->getId() . '/' . $workshop_item->getFilename();
        if(!\file_exists($filepath)){
            $flash->warning('The requested file of this workshop item could not be found.');
            $response->getBody()->write(
                $twig->render('workshop/alert.workshop.html.twig', $this->getWorkshopOptions())
            );
            return $response;
        }
        $filesize = \filesize($filepath);

        // Get workshop item ratings
        $rating_score            = $workshop_item->getRatingScore();
        $rating_count            = \count($workshop_item->getRatings());
        $difficulty_rating_count = \count($workshop_item->getDifficultyRatings());

        // Get screenshots
        $screenshots = [];
        $screenshot_dir = $_ENV['APP_WORKSHOP_STORAGE'] . '/' . $workshop_item->getId() . '/screenshots';
        if(\is_dir($screenshot_dir)){
            foreach(\glob($screenshot_dir . '/*') as $screenshot_file){
                $size = \getimagesize($screenshot_file);
                $screenshots[] = [
                    'filename' => \basename($screenshot_file),
                    'width'    => $size[0],
                    'height'   => $size[1],
                ];
            }
        }

        // Render view
        $response->getBody()->write(
            $twig->render('workshop/item.workshop.html.twig', $this->getWorkshopOptions() + [
                'item'                     => $workshop_item,
                'screenshots'              => $screenshots,
                'rating_amount'            => $rating_count,
                'difficulty_rating_amount' => $difficulty_rating_count,
                'filesize'                 => $filesize,
            ])
        );

        return $response;
    }

    public function uploadIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig
    ){
        $response->getBody()->write(
            $twig->render('workshop/upload.workshop.html.twig', $this->getWorkshopOptions())
        );

        return $response;
    }

    public function upload(
        Request $request,
        Response $response,
        FlashMessage $flash,
        TwigEnvironment $twig,
        Account $account,
        EntityManager $em
    ){

        $success = true;

        $uploaded_files        = $request->getUploadedFiles();
        $post                  = $request->getParsedBody();

        $name                  = (string) ($post['name'] ?? null);
        $description           = (string) ($post['description'] ?? null);
        $install_instructions  = (string) ($post['install_instructions'] ?? null);

        $original_author        = $post['original_author'] ?? null;
        $original_creation_date = $post['original_creation_date'] ?? null;

        // Filter name (remove extra spaces)
        $name = \preg_replace('/\s+/', ' ', \trim($name));

        // Check if name is valid
        if(!$name){
            $success = false;
            $flash->warning('Please enter a name for this workshop item');
        }

        // Check if type is valid
        $type = WorkshopType::tryFrom((int) ($post['type'] ?? null));
        if($type === null){
            $flash->warning('Invalid workshop type');
            $success = false;
        }

        // Check if a file was uploaded
        if(empty($uploaded_files['file']) || !($uploaded_files['file'] instanceof UploadedFileInterface) || $uploaded_files['file']->getError() === UPLOAD_ERR_NO_FILE){
            $flash->warning('You did not submit a file');
            $success = false;
        }

        // Check valid screenshot files
        if(!empty($uploaded_files['screenshots'])){
            /** @var UploadedFile $screenshot_file */
            foreach($uploaded_files['screenshots'] as $screenshot_file){

                // NO screenshots were added
                if ($screenshot_file->getError() === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                $filename = $screenshot_file->getClientFilename();
                $ext = \strtolower(\pathinfo($filename, \PATHINFO_EXTENSION));
                if(!\in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])){
                    $success = false;
                    $flash->warning('One or more screenshots are invalid. Allowed file types: jpg, jpeg, png, gif');
                }
            }
        }

        // Check valid thumbnail file
        if(!empty($uploaded_files['thumbnail']) && $uploaded_files['thumbnail']->getError() !== UPLOAD_ERR_NO_FILE){
            /** @var UploadedFile $thumbnail_file */
            $thumbnail_file = $uploaded_files['thumbnail'];

            $filename = $thumbnail_file->getClientFilename();
            $ext = \strtolower(\pathinfo($filename, \PATHINFO_EXTENSION));
            if(!\in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])){
                $success = false;
                $flash->warning('Invalid thumbnail. Allowed file types: jpg, jpeg, png, gif');
            }
        }

        // Return the page if submission is invalid
        if(!$success){
            // TODO: remove post vars (request twig extension)
            $response->getBody()->write(
                $twig->render('workshop/upload.workshop.html.twig', $this->getWorkshopOptions() + [
                    'name'                 => $name,
                    'description'          => $description,
                    'install_instructions' => $install_instructions,
                ])
            );
            return $response;
        }

        // Create the item in DB
        $workshop_item = new WorkshopItem();
        $workshop_item->setName($name);
        $workshop_item->setSubmitter($account->getUser());
        $workshop_item->setType($type);

        if(!empty($description)){
            $workshop_item->setDescription($description);
        }

        if(!empty($install_instructions)){
            $workshop_item->setInstallInstructions($install_instructions);
        }

        if(\is_string($original_author) && !empty($original_author)){
            $workshop_item->setOriginalAuthor($original_author);
        }

        if(\is_string($original_creation_date) && !empty($original_creation_date)){
            try {
                $datetime = new \DateTime($original_creation_date);
                if($datetime){
                    $workshop_item->setOriginalCreationDate($datetime);
                }
            } catch (\Exception $ex){}
        }

        // Set optional minimum game build
        if(isset($post['min_game_build']) && !empty($post['min_game_build'])){
            $min_game_build = $em->getRepository(GithubRelease::class)->find((int) ($post['min_game_build'] ?? null));
            if($min_game_build){
                $workshop_item->setMinGameBuild($min_game_build);
            }
        }

        // Automatically accept item for accounts with a role higher than 'User'
        if($account->getUser()->getRole()->value >= UserRole::WorkshopModerator->value){
            $workshop_item->setIsAccepted(true);
        }

        $em->persist($workshop_item);
        $em->flush(); // flush because we need ID for creating storage directory

        // Create directories for files
        $workshop_item_dir = $_ENV['APP_WORKSHOP_STORAGE'] . '/' . $workshop_item->getId();
        $workshop_item_screenshots_dir = $workshop_item_dir . '/screenshots';
        if(!DirectoryHelper::create($workshop_item_dir)){
            throw new \Exception('Failed to create workshop item storage dir');
        }
        if(!DirectoryHelper::create($workshop_item_screenshots_dir)){
            throw new \Exception('Failed to create workshop item screenshots dir');
        }

        // Store the uploaded file
        // TODO: allow specific files only (archives .7z, .zip, .rar, etc)
        $file = $uploaded_files['file'];
        $filename = $file->getClientFilename();
        $path = $workshop_item_dir . '/' . $filename;
        $file->moveTo($path);
        if(!\file_exists($path)){
            throw new \Exception('Failed to move workshop item file');
        }

        $workshop_item->setFilename($filename);

        // Store any uploaded screenshots
        $screenshot_files = $uploaded_files['screenshots'] ?? [];
        if(!empty($screenshot_files)){
            foreach($screenshot_files as $screenshot_file){
                // NO screenshots were added
                if ($screenshot_file->getError() === \UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                // Generate screenshot output path
                $ext = \strtolower(\pathinfo($screenshot_file->getClientFilename(), \PATHINFO_EXTENSION));
                $str = \md5(\random_int(\PHP_INT_MIN, \PHP_INT_MAX) . \time());
                $screenshot_filename = $str . '.' . $ext;
                $path = $workshop_item_screenshots_dir . '/' . $screenshot_filename;

                // Move screenshot
                $screenshot_file->moveTo($path);
                if(!\file_exists($path)){
                    throw new \Exception('Failed to move workshop item screenshot');
                }
            }
        }

        // Store thumbnail
        $thumbnail_file = $uploaded_files['thumbnail'] ?? null;
        if($thumbnail_file && $thumbnail_file->getError() !== UPLOAD_ERR_NO_FILE){

            // Generate thumbnail output path
            $ext = \strtolower(\pathinfo($thumbnail_file->getClientFilename(), \PATHINFO_EXTENSION));
            $str = \md5(\random_int(\PHP_INT_MIN, \PHP_INT_MAX) . \time());
            $thumbnail_filename = 'thumbnail_' . $str . '.' . $ext;
            $path = $workshop_item_dir . '/' . $thumbnail_filename;

            // Move thumbnail
            $thumbnail_file->moveTo($path);
            if(!\file_exists($path)){
                throw new \Exception('Failed to move workshop item thumbnail');
            }

            $workshop_item->setThumbnail($thumbnail_filename);
        }

        // Flush again so filenames are added to DB entity
        $em->flush();

        // Redirect accounts with a role higher than 'User' because their item is automatically accepted
        if($account->getUser()->getRole()->value >= UserRole::WorkshopModerator->value){
            $flash->success('Workshop item successfully created!');
            $response = $response->withHeader('Location', '/workshop/item/' . $workshop_item->getId())->withStatus(302);
            return $response;
        }

        // Show notice to normal user accounts
        $flash->success(
            'Your workshop item has been submitted and will be reviewed by the KeeperFX team. ' .
            'After it has been accepted it will be added to the workshop for others to download.'
        );

        $response->getBody()->write(
            $twig->render('workshop/alert.workshop.html.twig', $this->getWorkshopOptions())
        );

        return $response;
    }

    public function editIndex(
        Request $request,
        Response $response,
        FlashMessage $flash,
        TwigEnvironment $twig,
        Account $account,
        EntityManager $em,
        $id
    ){
        // Check if workshop item exists
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($id);
        if(!$workshop_item){
            $flash->warning('The requested workshop item could not be found.');
            $response->getBody()->write(
                $twig->render('workshop/alert.workshop.html.twig', $this->getWorkshopOptions())
            );
            return $response;
        }

        // Check if user is workshop item submitter
        if($workshop_item->getSubmitter() !== $account->getUser()){
            $flash->warning('You can not edit this workshop item because you did not submit it.');
            $response->getBody()->write(
                $twig->render('workshop/alert.workshop.html.twig', $this->getWorkshopOptions())
            );
            return $response;
        }

        // Get screenshots
        $screenshots = [];
        $screenshot_dir = $_ENV['APP_WORKSHOP_STORAGE'] . '/' . $workshop_item->getId() . '/screenshots';
        if(\is_dir($screenshot_dir)){
            foreach(\glob($screenshot_dir . '/*') as $screenshot_file){
                $size = \getimagesize($screenshot_file);
                $screenshots[] = [
                    'filename' => \basename($screenshot_file),
                    'width'    => $size[0],
                    'height'   => $size[1],
                ];
            }
        }

        // Show edit page
        $response->getBody()->write(
            $twig->render('workshop/edit.workshop.html.twig', $this->getWorkshopOptions() + [
                'workshop_item' => $workshop_item,
                'screenshots'   => $screenshots,
            ])
        );
        return $response;
    }

    public function edit(
        Request $request,
        Response $response,
        FlashMessage $flash,
        TwigEnvironment $twig,
        Account $account,
        EntityManager $em,
        $id
    ){
        // Check if workshop item exists
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($id);
        if(!$workshop_item){
            $flash->warning('The requested workshop item could not be found.');
            $response->getBody()->write(
                $twig->render('workshop/alert.workshop.html.twig', $this->getWorkshopOptions())
            );
            return $response;
        }

        // Check if user is workshop item submitter
        if($workshop_item->getSubmitter() !== $account->getUser()){
            $flash->warning('You can not edit this workshop item because you did not submit it.');
            $response->getBody()->write(
                $twig->render('workshop/alert.workshop.html.twig', $this->getWorkshopOptions())
            );
            return $response;
        }

        $uploaded_files = $request->getUploadedFiles();
        $post           = $request->getParsedBody();

        $name                  = (string) ($post['name'] ?? null);
        $description           = (string) ($post['description'] ?? null);
        $install_instructions  = (string) ($post['install_instructions'] ?? null);

        $original_author        = $post['original_author'] ?? null;
        $original_creation_date = $post['original_creation_date'] ?? null;

        $workshop_item->setName($name);
        $workshop_item->setDescription($description);
        $workshop_item->setInstallInstructions($install_instructions);

        // Set workshop item type
        $type = WorkshopType::tryFrom((int) ($post['type'] ?? null));
        $workshop_item->setType($type);

        // Set minimum game build
        $min_game_build = $em->getRepository(GithubRelease::class)->find((int) ($post['min_game_build'] ?? null));
        $workshop_item->setMinGameBuild($min_game_build ?? null);

        // Set directories for files
        $workshop_item_dir = $_ENV['APP_WORKSHOP_STORAGE'] . '/' . $workshop_item->getId();
        $workshop_item_screenshots_dir = $workshop_item_dir . '/screenshots';

        // Update workshop file
        if(!empty($uploaded_files['file']) && $uploaded_files['file']->getError() !== UPLOAD_ERR_NO_FILE){

            $file     = $uploaded_files['file'];
            $filename = $file->getClientFilename();
            $path     = $workshop_item_dir . '/' . $filename;

            $workshop_item->setFilename($filename);

            $file->moveTo($path);
            if(!\file_exists($path)){
                throw new \Exception('Failed to move workshop item file');
            }
        }

        // Store any uploaded screenshots
        foreach($uploaded_files['screenshots'] as $screenshot_file){
            // NO screenshots were added
            if ($screenshot_file->getError() === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            // Generate screenshot output path
            $ext = \strtolower(\pathinfo($screenshot_file->getClientFilename(), \PATHINFO_EXTENSION));
            $str = \md5(\random_int(\PHP_INT_MIN, \PHP_INT_MAX) . \time());
            $screenshot_filename = $str . '.' . $ext;
            $path = $workshop_item_screenshots_dir . '/' . $screenshot_filename;

            // Move screenshot
            $screenshot_file->moveTo($path);
            if(!\file_exists($path)){
                throw new \Exception('Failed to move workshop item screenshot');
            }
        }

        // Update or set thumbnail
        if(!empty($uploaded_files['thumbnail']) && $uploaded_files['thumbnail']->getError() !== UPLOAD_ERR_NO_FILE){

            $thumbnail_file     = $uploaded_files['thumbnail'];
            $thumbnail_filename = $thumbnail_file->getClientFilename();
            $thumbnail_path     = $workshop_item_dir . '/' . $thumbnail_filename;

            // Remove already existing thumbnail
            if($workshop_item->getThumbnail() !== null){
                $current_thumbnail_path = $workshop_item_dir . '/' . $workshop_item->getThumbnail();
                if(\file_exists($current_thumbnail_path)){
                    \unlink($current_thumbnail_path);
                }
            }

            $thumbnail_file->moveTo($thumbnail_path);

            if(\file_exists($thumbnail_path)){
                $workshop_item->setThumbnail($thumbnail_filename);
            }
        }

        // Update original author
        if(\is_string($original_author) && !empty($original_author)){
            $workshop_item->setOriginalAuthor($original_author);
        } else {
            $workshop_item->setOriginalAuthor(null);
        }

        // Update original creation date
        if(\is_string($original_creation_date) && !empty($original_creation_date)){
            try {
                $datetime = new \DateTime($original_creation_date);
                if($datetime){
                    $workshop_item->setOriginalCreationDate($datetime);
                }
            } catch (\Exception $ex){}
        } else {
            $workshop_item->setOriginalCreationDate(null);
        }

        // Force the workshop item to be accepted again
        $workshop_item->setIsAccepted(false);

        // Write changes to DB
        $em->flush();

        $flash->success(
            'Your workshop item has been updated and has been temporary removed from the workshop. ' .
            'After it has been reviewed by the KeeperFX team it will be available in the workshop again.'
        );

        $response->getBody()->write(
            $twig->render('workshop/alert.workshop.html.twig', $this->getWorkshopOptions())
        );

        return $response;
    }

    public function download(
        Request $request,
        Response $response,
        FlashMessage $flash,
        Account $account,
        TwigEnvironment $twig,
        EntityManager $em,
        $id,
        $filename
    )
    {
        // Check if workshop item exists
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($id);
        if(!$workshop_item){
            $flash->warning('The requested workshop item could not be found.');
            $response->getBody()->write(
                $twig->render('workshop/alert.workshop.html.twig', $this->getWorkshopOptions())
            );
            return $response;
        }

        // Check if workshop item has been accepted
        // Admins can always download workshop items
        if(
            $workshop_item->getIsAccepted() !== true
            && $account->getUser()->getRole()->value < UserRole::Admin->value
        ){
            $flash->warning('The requested workshop item has not been accepted yet.');
            $response->getBody()->write(
                $twig->render('workshop/alert.workshop.html.twig', $this->getWorkshopOptions())
            );
            return $response;
        }

        // Check if filename matches the one in DB
        if($filename !== $workshop_item->getFilename()){
            $flash->warning('Invalid workshop download URL.');
            $response->getBody()->write(
                $twig->render('workshop/alert.workshop.html.twig', $this->getWorkshopOptions())
            );
            return $response;
        }

        $filepath = $_ENV['APP_WORKSHOP_STORAGE'] . '/' . $workshop_item->getId() . '/' . $workshop_item->getFilename();

        // Check if file exists
        if(!\file_exists($filepath)){
            $flash->warning('The requested workshop file could not be found.');
            $response->getBody()->write(
                $twig->render('workshop/alert.workshop.html.twig', $this->getWorkshopOptions())
            );
            return $response;
        }

        // Increase download count
        if(!isset($request->getQueryParams()['no_download_increment'])){
            $download_count = $workshop_item->getDownloadCount();
            $download_count++;
            $workshop_item->setDownloadCount($download_count);
            $em->flush();
        }

        // Return download
        $response = $response
            ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->withHeader('Pragma', 'no-cache')
            ->withHeader('Content-Type', 'application/octet-stream')
            ->withHeader('Content-Transfer-Encoding', 'Binary')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $workshop_item->getFilename() . '"');
        $response->getBody()->write(
            \file_get_contents($filepath)
        );
        return $response;
    }

    public function outputScreenshot(
        Request $request,
        Response $response,
        FlashMessage $flash,
        Account $account,
        TwigEnvironment $twig,
        EntityManager $em,
        $id,
        $filename)
    {
        // Check if workshop item exists
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($id);
        if(!$workshop_item){
            throw new HttpNotFoundException($request, 'workshop item not found');
        }

        // Get screenshot dir
        $screenshot_dir = $_ENV['APP_WORKSHOP_STORAGE'] . '/' . $workshop_item->getId() . '/screenshots';
        if(!\is_dir($screenshot_dir)){
            throw new HttpNotFoundException($request, 'screenshot dir does not exist');
        }

        // Loop trough screenshot to see if one matches
        $screenshot_filepath = null;
        foreach(\glob($screenshot_dir . '/*') as $path){
            if($filename === \basename($path)){
                $screenshot_filepath = $path;
                break;
            }
        }
        if($screenshot_filepath === null){
            throw new HttpNotFoundException($request, 'screenshot not found');
        }

        // Get mimetype of image
        $finfo        = \finfo_open(\FILEINFO_MIME_TYPE);
        $content_type = \finfo_file($finfo, $screenshot_filepath);
        \finfo_close($finfo);

        // Return screenshot
        $cache_time = (int)($_ENV['APP_IMAGE_OUTPUT_CACHE_TIME'] ?? 86400);
        $response = $response
            ->withHeader('Pragma', 'public')
            ->withHeader('Cache-Control', 'max-age=' . $cache_time)
            ->withHeader('Expires', \gmdate('D, d M Y H:i:s \G\M\T', time() + $cache_time))
            ->withHeader('Content-Type', $content_type);
        $response->getBody()->write(
            \file_get_contents($screenshot_filepath)
        );

        return $response;
    }

    public function outputThumbnail(
        Request $request,
        Response $response,
        FlashMessage $flash,
        Account $account,
        TwigEnvironment $twig,
        EntityManager $em,
        $id,
        $filename)
    {
        // Check if workshop item exists
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($id);
        if(!$workshop_item){
            throw new HttpNotFoundException($request, 'workshop item not found');
        }

        // Check if workshop item has thumbnail
        if(!$workshop_item->getThumbnail()){
            throw new HttpNotFoundException($request, 'workshop item does not have thumbnail');
        }

        // Check if thumbnail filename matches
        if($workshop_item->getThumbnail() !== $filename){
            throw new HttpNotFoundException($request, 'workshop item does not have thumbnail with that name');
        }

        // Get thumbnail filepath
        $thumbnail_filepath = $_ENV['APP_WORKSHOP_STORAGE'] . '/' . $workshop_item->getId() . '/' . $workshop_item->getThumbnail();

        // Check if file exists
        if(!\file_exists($thumbnail_filepath)){
            throw new HttpNotFoundException($request, 'workshop item thumbnail does not exist');
        }

        // Get mimetype of image
        $finfo        = \finfo_open(\FILEINFO_MIME_TYPE);
        $content_type = \finfo_file($finfo, $thumbnail_filepath);
        \finfo_close($finfo);

        // Return thumbnail
        $cache_time = (int)($_ENV['APP_IMAGE_OUTPUT_CACHE_TIME'] ?? 86400);
        $response = $response
            ->withHeader('Pragma', 'public')
            ->withHeader('Cache-Control', 'max-age=' . $cache_time)
            ->withHeader('Expires', \gmdate('D, d M Y H:i:s \G\M\T', time() + $cache_time))
            ->withHeader('Content-Type', $content_type);
        $response->getBody()->write(
            \file_get_contents($thumbnail_filepath)
        );

        return $response;
    }

    public function deleteThumbnail(
        Request $request,
        Response $response,
        FlashMessage $flash,
        Account $account,
        TwigEnvironment $twig,
        EntityManager $em,
        CsrfGuard $csrf_guard,
        $id,
        $token_name,
        $token_value
    ){
        // Validate against CSRF
        $valid = $csrf_guard->validateToken($token_name, $token_value);
        if(!$valid){
            return $response;
        }

        // Get workshop item
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($id);
        if(!$workshop_item){
            throw new HttpNotFoundException($request, 'workshop item not found');
        }

        // Make sure current user owns this workshop item
        if($account->getUser() !== $workshop_item->getSubmitter()){
            return $response;
        }

        // Get thumbnail filename
        $filename = $workshop_item->getThumbnail();
        if(!$filename){
            return $response;
        }

        // Get filepath
        $filepath = $_ENV['APP_WORKSHOP_STORAGE'] . '/' . $workshop_item->getId() . '/' . $filename;
        if(!\file_exists($filepath)){
            return $response;
        }

        // Delete file
        if(!@\unlink($filepath)){
            throw new \Exception("failed to remove thumbnail: {$filepath}");
        }

        // Update workshop item
        $workshop_item->setThumbnail(null);
        $em->flush();

        // Return view
        $flash->success('Thumbnail successfully removed');
        $response = $response->withHeader('Location', '/workshop/edit/' . $workshop_item->getId())->withStatus(302);
        return $response;
    }

    public function deleteScreenshot(
        Request $request,
        Response $response,
        FlashMessage $flash,
        Account $account,
        TwigEnvironment $twig,
        EntityManager $em,
        CsrfGuard $csrf_guard,
        $id,
        $filename,
        $token_name,
        $token_value
    ){
        // Validate CSRF token
        $valid = $csrf_guard->validateToken($token_name, $token_value);
        if(!$valid){
            return $response;
        }

        // Get workshop item
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($id);
        if(!$workshop_item){
            throw new HttpNotFoundException($request, 'workshop item not found');
        }

        // Make sure current user owns this workshop item
        if($account->getUser() !== $workshop_item->getSubmitter()){
            return $response;
        }

        // Get screenshots
        $screenshot_dir = $_ENV['APP_WORKSHOP_STORAGE'] . '/' . $workshop_item->getId() . '/screenshots';
        if(\is_dir($screenshot_dir)){
            foreach(\glob($screenshot_dir . '/*') as $screenshot_file){

                // Check if filename matches
                if(\basename($screenshot_file) === $filename){

                    // Delete screenshot
                    if(!@\unlink($screenshot_file)){
                        throw new \Exception("failed to remove screenshot: {$screenshot_file}");
                    }

                    $flash->success('Screenshot removed!');
                    $response = $response->withHeader('Location', '/workshop/edit/' . $workshop_item->getId())->withStatus(302);
                    return $response;
                }
            }
        }

        $flash->warning('Failed to remove screenshot.');
        $response = $response->withHeader('Location', '/workshop/edit/' . $workshop_item->getId())->withStatus(302);
        return $response;
    }

}
