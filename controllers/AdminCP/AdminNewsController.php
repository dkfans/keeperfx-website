<?php

namespace App\Controller\AdminCP;

use App\Entity\NewsArticle;

use App\Account;
use App\FlashMessage;
use App\DiscordNotifier;
use App\UploadSizeHelper;
use App\Helper\ThumbnailHelper;
use App\Notifications\NotificationCenter;
use App\Notifications\Notification\NewsPostNotification;

use Slim\Csrf\Guard;
use Doctrine\ORM\EntityManager;
use ByteUnits\Binary as BinaryFormatter;
use Twig\Environment as TwigEnvironment;

use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpBadRequestException;

class AdminNewsController {

    public function newsIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em
    ){
        $articles = $em->getRepository(NewsArticle::class)->findBy([], ['created_timestamp' => 'DESC']);

        $response->getBody()->write(
            $twig->render('admincp/news/news.admincp.html.twig', [
                'articles' => $articles
            ])
        );

        return $response;
    }

    public function newsAddIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig
    ){
        $response->getBody()->write(
            $twig->render('admincp/news/news.add.admincp.html.twig')
        );

        return $response;
    }

    public function newsAdd(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        Account $account,
        EntityManager $em,
        FlashMessage $flash,
        DiscordNotifier $discord_notifier,
        UploadSizeHelper $upload_size_helper,
        NotificationCenter $nc,
    ){

        // Get POST data
        $post     = $request->getParsedBody();
        $title    = (string) ($post['title'] ?? null);
        $contents = (string) ($post['contents'] ?? null);
        $excerpt  = (string) ($post['excerpt'] ?? null);

        // Make sure title and contents are set
        if(!$contents || !$title){
            $flash->warning('Missing title or contents.');
            $response->getBody()->write(
                $twig->render('admincp/news/news.add.admincp.html.twig')
            );
            return $response;
        }

        // Create article
        $article = new NewsArticle();
        $article->setTitle($title);
        $article->setContents($contents);
        $article->setAuthor($account->getUser());

        // Add an excerpt
        if($excerpt){
            $article->setExcerpt($excerpt);
        } else {
            $article->setExcerpt($contents);
        }

        // Check if we need to upload an image for this news article
        if(\array_key_exists('APP_NEWS_IMAGE_STORAGE', $_ENV) && !empty($_ENV['APP_NEWS_IMAGE_STORAGE'])){

            // Get image file
            $files = $request->getUploadedFiles();
            $file  = $files['image'] ?? null;
            if($file !== null && ($file instanceof UploadedFileInterface) && $file->getError() !== \UPLOAD_ERR_NO_FILE){

                // Check image file extension
                $filename = $file->getClientFilename();
                $file_extension = \strtolower(\pathinfo($filename, \PATHINFO_EXTENSION));
                if(!\in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])){
                    $flash->warning('Invalid image file. Allowed file types: jpg, jpeg, png, gif, webp');
                    $response->getBody()->write(
                        $twig->render('admincp/news/news.add.admincp.html.twig')
                    );
                    return $response;
                }

                // Check filesize
                if($file->getSize() > $upload_size_helper->getFinalNewsImageUploadSize()){
                    $flash->warning(
                        'Maximum upload filesize for news image exceeded. (' .
                        BinaryFormatter::bytes($upload_size_helper->getFinalNewsImageUploadSize())->format() .
                        ')'
                    );
                    $response->getBody()->write(
                        $twig->render('admincp/news/news.add.admincp.html.twig')
                    );
                    return $response;
                }

                // Check if news image upload directory exists
                // Create it if it doesn't
                $news_image_dir = $_ENV['APP_NEWS_IMAGE_STORAGE'] ?? null;
                if(empty($news_image_dir)){
                    throw new \Exception('Image news storage directory not set: \'APP_NEWS_IMAGE_STORAGE\'');
                }
                if(!is_dir($news_image_dir)){
                    if(!\mkdir($news_image_dir)){
                        throw new \Exception('Failed to create news image storage directory: \'' . $news_image_dir . '\'');
                    }
                }

                // Create variables for this image
                $image_filename  = md5($title . time()) . '.' . $file_extension;
                $news_image_path = $news_image_dir . '/' . $image_filename;

                // Make sure image does not exist yet
                if(\file_exists($news_image_path)){
                    throw new \Exception("news image filename already exists: {$image_filename}");
                }

                // Move uploaded image
                $file->moveTo($news_image_path);
                if(!\file_exists($news_image_path)){
                    throw new \Exception('Failed to move uploaded news image');
                }

                // Create a thumbnail
                $thumbnail_filename = ThumbnailHelper::createThumbnail($news_image_path, 256, 256);
                if($thumbnail_filename){
                    $image_filename = $thumbnail_filename;

                    // Remove original image
                    @\unlink($news_image_path);
                }

                // Add image to news item
                $article->setImage($image_filename);

            }

        }

        // Save to DB
        $em->persist($article);
        $em->flush();

        // Notify the users that are subscribed to news articles
        $nc->sendNotificationToAll(NewsPostNotification::class, [
            'id'          => $article->getId(),
            'title'       => $article->getTitle(),
            'title_slug'  => $article->getTitleSlug(),
            'date_string' => $article->getCreatedTimestamp()->format("Y-m-d"),
        ]);

        // Send a notification on Discord
        $discord_notifier->notifyNewNewsItem($article);

        // Success
        $flash->success('News article posted!');
        $response = $response->withHeader('Location', '/admin/news/list')->withStatus(302);
        return $response;
    }

    public function newsEditIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        FlashMessage $flash,
        $id
    ){

        // Get article
        $article = $em->getRepository(NewsArticle::class)->find($id);
        if(!$article){
            $flash->warning('News article not found.');
            $response = $response->withHeader('Location', '/admin/news/list')->withStatus(302);
            return $response;
        }

        // Output
        $response->getBody()->write(
            $twig->render('admincp/news/news.edit.admincp.html.twig', [
                'article' => $article,
            ])
        );
        return $response;
    }

    public function newsEdit(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        FlashMessage $flash,
        UploadSizeHelper $upload_size_helper,
        $id
    ){

        // Get POST vars
        $post     = $request->getParsedBody();
        $title    = (string) ($post['title'] ?? null);
        $contents = (string) ($post['contents'] ?? null);
        $excerpt  = (string) ($post['excerpt'] ?? null);

        // Get the article
        $article = $em->getRepository(NewsArticle::class)->find($id);
        if(!$article){
            $flash->warning('News article not found.');
            $response = $response->withHeader('Location', '/admin/news/list')->withStatus(302);
            return $response;
        }

        // Update article in DB
        $article->setTitle($title);
        $article->setContents($contents);

        // Add excerpt
        if($excerpt){
            $article->setExcerpt($excerpt);
        } else {
            $article->setExcerpt($contents);
        }

        // Check if we need to upload an image for this news article
        if(\array_key_exists('APP_NEWS_IMAGE_STORAGE', $_ENV) && !empty($_ENV['APP_NEWS_IMAGE_STORAGE'])){

            // Get image file
            $files = $request->getUploadedFiles();
            $file  = $files['image'] ?? null;
            if($file !== null && ($file instanceof UploadedFileInterface) && $file->getError() !== \UPLOAD_ERR_NO_FILE){

                // Check image file extension
                $filename = $file->getClientFilename();
                $file_extension = \strtolower(\pathinfo($filename, \PATHINFO_EXTENSION));
                if(!\in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])){
                    $flash->warning('Invalid image file. Allowed file types: jpg, jpeg, png, gif, webp');
                    $response = $response->withHeader('Location', '/admin/news/' . $article->getId())->withStatus(302);
                    return $response;
                }

                // Check filesize
                if($file->getSize() > $upload_size_helper->getFinalNewsImageUploadSize()){
                    $flash->warning(
                        'Maximum upload filesize for news image exceeded. (' .
                        BinaryFormatter::bytes($upload_size_helper->getFinalNewsImageUploadSize())->format() .
                        ')'
                    );
                    $response = $response->withHeader('Location', '/admin/news/' . $article->getId())->withStatus(302);
                    return $response;
                }

                // Check if news image upload directory exists
                // Create it if it doesn't
                $news_image_dir = $_ENV['APP_NEWS_IMAGE_STORAGE'] ?? null;
                if(empty($news_image_dir)){
                    throw new \Exception('Image news storage directory not set: \'APP_NEWS_IMAGE_STORAGE\'');
                }
                if(!is_dir($news_image_dir)){
                    if(!\mkdir($news_image_dir)){
                        throw new \Exception('Failed to create news image storage directory: \'' . $news_image_dir . '\'');
                    }
                }

                // Check if there is already an image added to this news article
                $existing_image = $article->getImage();
                if($existing_image){

                    // Check if image exists and remove it if it does
                    $existing_image_path = $_ENV['APP_NEWS_IMAGE_STORAGE'] . '/' . $existing_image;
                    if(\file_exists($existing_image_path)){
                        @\unlink($existing_image_path);
                    }
                }

                // Create variables for this image
                $image_filename  = md5($title . time()) . '.' . $file_extension;
                $news_image_path = $news_image_dir . '/' . $image_filename;

                // Make sure image does not exist yet
                if(\file_exists($news_image_path)){
                    throw new \Exception("news image filename already exists: {$image_filename}");
                }

                // Move uploaded image
                $file->moveTo($news_image_path);
                if(!\file_exists($news_image_path)){
                    throw new \Exception('Failed to move uploaded news image');
                }

                // Create a thumbnail
                $thumbnail_filename = ThumbnailHelper::createThumbnail($news_image_path, 256, 256);
                if($thumbnail_filename){
                    $image_filename = $thumbnail_filename;

                    // Remove original image
                    @\unlink($news_image_path);
                }

                // Add image to news item
                $article->setImage($image_filename);

            }
        }

        // Save changes to DB
        $em->flush();

        // Success
        $flash->success('News article updated!');
        $response = $response->withHeader('Location', '/admin/news/' . $article->getId())->withStatus(302);
        return $response;
    }

    public function newsDelete(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        FlashMessage $flash,
        Guard $csrf_guard,
        $id,
        $token_name,
        $token_value,
    ){

        // Check for valid CSRF token
        $valid = $csrf_guard->validateToken($token_name, $token_value);
        if($valid){

            $article = $em->getRepository(NewsArticle::class)->find($id);
            if($article){

                $em->remove($article);
                $em->flush();
                $flash->success('News article successfully removed!');
            }
        }

        $response = $response->withHeader('Location', '/admin/news/list')->withStatus(302);
        return $response;
    }

    public function imageDelete(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        FlashMessage $flash,
        Guard $csrf_guard,
        $id,
        $token_name,
        $token_value,
    ){

        // Check for valid CSRF token
        $valid = $csrf_guard->validateToken($token_name, $token_value);
        if(!$valid){
            throw new HttpBadRequestException($request);
        }

        // Get the article
        $article = $em->getRepository(NewsArticle::class)->find($id);
        if(!$article){
            throw new HttpNotFoundException($request);
        }

        // Get the image
        $image = $article->getImage();
        if($image === null){
            $flash->error('Article does not have an image to delete');
            $response = $response->withHeader('Location', '/admin/news/' . $article->getId())->withStatus(302);
            return $response;
        }

        // Get the path
        $image_path = $_ENV['APP_NEWS_IMAGE_STORAGE'] . '/' . $image;

        // Remove the image file if it exists
        if(\file_exists($image_path)){
            @\unlink($image_path);
        }

        // Remove image from entity and save to DB
        $article->setImage(null);
        $em->flush();

        // Success
        $flash->success('News image has been removed!');
        $response = $response->withHeader('Location', '/admin/news/' . $article->getId())->withStatus(302);
        return $response;
    }

}
