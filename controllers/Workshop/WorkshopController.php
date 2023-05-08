<?php

namespace App\Controller\Workshop;


use App\Enum\UserRole;
use App\Enum\WorkshopCategory;

use App\Entity\WorkshopTag;
use App\Entity\WorkshopItem;
use App\Entity\GithubRelease;
use App\Entity\WorkshopRating;
use App\Entity\WorkshopComment;
use App\Entity\WorkshopDifficultyRating;

use App\Account;
use App\FlashMessage;
use App\Config\Config;
use App\Entity\WorkshopFile;
use App\UploadSizeHelper;

use URLify;
use Slim\Psr7\UploadedFile;
use Doctrine\ORM\EntityManager;
use Slim\Csrf\Guard as CsrfGuard;
use GuzzleHttp\Psr7\LazyOpenStream;
use geertw\IpAnonymizer\IpAnonymizer;
use Twig\Environment as TwigEnvironment;
use ByteUnits\Binary as BinaryFormatter;

use Psr\SimpleCache\CacheInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Xenokore\Utility\Helper\DirectoryHelper;

use Slim\Exception\HttpNotFoundException;

class WorkshopController {

    public function itemIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        FlashMessage $flash,
        EntityManager $em,
        Account $account,
        $id,
        $slug = null
    ){
        // Check if workshop item exists
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($id);
        if(!$workshop_item){
            $flash->warning('The requested workshop item could not be found.');
            $response->getBody()->write(
                $twig->render('workshop/alert.workshop.html.twig')
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
                $twig->render('workshop/alert.workshop.html.twig')
            );
            return $response;
        }
        $filesize = \filesize($filepath);

        // Get workshop item rating counts
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

        // Get user rating
        $user_rating = $em->getRepository(WorkshopRating::class)->findOneBy([
            'item' => $workshop_item,
            'user' => $account?->getUser()
        ])?->getScore();

        // Get user difficulty rating
        $user_difficulty_rating = $em->getRepository(WorkshopDifficultyRating::class)->findOneBy([
            'item' => $workshop_item,
            'user' => $account?->getUser()
        ])?->getScore();

        // Render view
        $response->getBody()->write(
            $twig->render('workshop/item.workshop.html.twig', [
                'item'                     => $workshop_item,
                'screenshots'              => $screenshots,
                'rating_amount'            => $rating_count,
                'user_rating'              => $user_rating,
                'difficulty_rating_amount' => $difficulty_rating_count,
                'user_difficulty_rating'   => $user_difficulty_rating,
                'filesize'                 => $filesize,
            ])
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
                $twig->render('workshop/alert.workshop.html.twig')
            );
            return $response;
        }

        // Check if user is workshop item submitter
        if($workshop_item->getSubmitter() !== $account->getUser()){
            $flash->warning('You can not edit this workshop item because you did not submit it.');
            $response->getBody()->write(
                $twig->render('workshop/alert.workshop.html.twig')
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
            $twig->render('workshop/edit.workshop.html.twig', [
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
                $twig->render('workshop/alert.workshop.html.twig')
            );
            return $response;
        }

        // Check if user is workshop item submitter
        if($workshop_item->getSubmitter() !== $account->getUser()){
            $flash->warning('You can not edit this workshop item because you did not submit it.');
            $response->getBody()->write(
                $twig->render('workshop/alert.workshop.html.twig')
            );
            return $response;
        }

        $uploaded_files = $request->getUploadedFiles();
        $post           = $request->getParsedBody();

        $name                  = \trim((string) ($post['name'] ?? null));
        $description           = \trim((string) ($post['description'] ?? null));
        $install_instructions  = \trim((string) ($post['install_instructions'] ?? null));

        $original_author        = $post['original_author'] ?? null;
        $original_creation_date = $post['original_creation_date'] ?? null;

        $workshop_item->setName($name);
        $workshop_item->setDescription($description);
        $workshop_item->setInstallInstructions($install_instructions);

        // Set workshop item category
        $category = WorkshopCategory::tryFrom((int) ($post['category'] ?? null));
        $workshop_item->setCategory($category);

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
        $workshop_item->setIsPublished(false);

        // Write changes to DB
        $em->flush();

        $flash->success(
            'Your workshop item has been updated and has been temporary removed from the workshop. ' .
            'After it has been reviewed by the KeeperFX team it will be available in the workshop again.'
        );

        $response->getBody()->write(
            $twig->render('workshop/alert.workshop.html.twig')
        );

        return $response;
    }

    public function comment(
        Request $request,
        Response $response,
        FlashMessage $flash,
        Account $account,
        TwigEnvironment $twig,
        EntityManager $em,
        $id
    ){
        // Check if workshop item exists
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($id);
        if(!$workshop_item){
            throw new HttpNotFoundException($request);
        }

        $post    = $request->getParsedBody();
        $content = (string) ($post['content'] ?? null);

        if(empty($content)){
            $flash->warning('You tried to submit an empty comment.');
            $response = $response->withHeader('Location', '/workshop/item/' . $workshop_item->getId())->withStatus(302);
            return $response;
        }

        // TODO: filter bad words

        $comment = new WorkshopComment();
        $comment->setItem($workshop_item);
        $comment->setUser($account->getUser());
        $comment->setContent($content);

        $em->persist($comment);
        $em->flush();

        $flash->success('Your comment has been added!');
        $response = $response->withHeader('Location', '/workshop/item/' . $workshop_item->getId())->withStatus(302);
        return $response;
    }

}
