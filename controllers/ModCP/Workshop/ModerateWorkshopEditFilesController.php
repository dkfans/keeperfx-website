<?php

namespace App\Controller\ModCP\Workshop;

use App\Entity\WorkshopItem;
use App\Entity\WorkshopFile;

use App\Account;
use App\FlashMessage;
use App\UploadSizeHelper;
use App\Workshop\WorkshopBrokenFileHandler;

use Doctrine\ORM\EntityManager;
use Slim\Csrf\Guard as CsrfGuard;
use Twig\Environment as TwigEnvironment;

use Slim\Exception\HttpNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ModerateWorkshopEditFilesController {

    public function index(
        Request $request,
        Response $response,
        FlashMessage $flash,
        TwigEnvironment $twig,
        Account $account,
        EntityManager $em,
        $item_id
    ){
        // Check if workshop item exists
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($item_id);
        if(!$workshop_item){
            $flash->warning('The requested workshop item could not be found.');
            $response->getBody()->write(
                $twig->render('workshop/alert.workshop.html.twig')
            );
            return $response;
        }

        // Show edit page
        $response->getBody()->write(
            $twig->render('modcp/workshop/edit.files.workshop.modcp.html.twig', [
                'workshop_item' => $workshop_item
            ])
        );
        return $response;
    }

    public function upload(
        Request $request,
        Response $response,
        FlashMessage $flash,
        TwigEnvironment $twig,
        Account $account,
        EntityManager $em,
        UploadSizeHelper $upload_size_helper,
        WorkshopBrokenFileHandler $broken_file_handler,
        $item_id
    )
    {
        // Check if workshop item exists
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($item_id);
        if(!$workshop_item){
            throw new HttpNotFoundException($request);
        }

        // Get file uploads
        $uploaded_files = $request->getUploadedFiles();

        // Make sure uploaded file is set
        if(empty($uploaded_files['file']) || $uploaded_files['file']->getError() === UPLOAD_ERR_NO_FILE){
            $flash->warning('No file was uploaded...');
            $response = $response->withHeader('Location', '/moderate/workshop/' . $workshop_item->getId() . '/files')->withStatus(302);
            return $response;
        }

        // Get variables
        $file     = $uploaded_files['file'];
        $filename = $file->getClientFilename();

        // Make sure upload file does not exceed file size
        if($file->getSize() > $upload_size_helper->getFinalWorkshopItemUploadSize()){
            $flash->warning('File upload size is too big.');
            $response = $response->withHeader('Location', '/moderate/workshop/' . $workshop_item->getId() . '/files')->withStatus(302);
            return $response;
        }

        // Set directories for files
        $workshop_item_dir       = $_ENV['APP_WORKSHOP_STORAGE'] . '/' . $workshop_item->getId();
        $workshop_item_files_dir = $workshop_item_dir . '/files';

        // Make sure output directory exists
        if(!\is_dir($workshop_item_files_dir)){
            if(!@mkdir($workshop_item_files_dir, 0777, true)){
                throw new \Exception("Failed to create 'files' dir for workshop item with id {$workshop_item->getId()}.");
            }
        }

        // Generate storage filename
        $ext              = \strtolower(\pathinfo($filename, \PATHINFO_EXTENSION));
        $str              = \md5(\random_int(\PHP_INT_MIN, \PHP_INT_MAX) . \time());
        $storage_filename = $str . '__' . $ext;
        $storage_path     = $workshop_item_files_dir . '/' . $storage_filename;

        // Move uploaded file to storage
        $file->moveTo($storage_path);
        if(!\file_exists($storage_path)){
            throw new \Exception('Failed to move workshop item file');
        }

        // Increase weight for existing files
        $query_builder = $em->getConnection()->createQueryBuilder();
        $query_builder
            ->update('workshop_file')
            ->where('item_id = ' . $workshop_item->getId())
            ->set('weight', 'weight + 1');
        $query_builder->executeQuery();

        // Create new DB entity
        $workshop_file = new WorkshopFile();
        $workshop_file->setItem($workshop_item);
        $workshop_file->setFilename($filename);
        $workshop_file->setStorageFilename($storage_filename);
        $workshop_file->setSize(\filesize($storage_path));
        $workshop_file->setWeight(0);
        $em->persist($workshop_file);

        // Update last updated timestamp on workshop item
        $workshop_item->setUpdatedTimestamp(new \DateTime('now'));

        // Save to DB
        $em->flush();

        // Handle broken file checker
        $broken_file_handler->handleItem($workshop_item, true);

        // Show notice and navigate back to edit page
        $flash->success('File uploaded!');
        $response = $response->withHeader('Location', '/moderate/workshop/' . $workshop_item->getId() . '/files')->withStatus(302);
        return $response;
    }

    public function delete(
        Request $request,
        Response $response,
        FlashMessage $flash,
        TwigEnvironment $twig,
        Account $account,
        EntityManager $em,
        CsrfGuard $csrf_guard,
        $item_id,
        $file_id,
        $token_name,
        $token_value,
    )
    {
        // Check for valid CSRF check
        $valid = $csrf_guard->validateToken($token_name, $token_value);
        if(!$valid){
            throw new HttpNotFoundException($request);
        }

        // Check if workshop item exists
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($item_id);
        if(!$workshop_item){
            throw new HttpNotFoundException($request);
        }

        // Check if workshop file exists
        $workshop_file = $em->getRepository(WorkshopFile::class)->find($file_id);
        if(!$workshop_file){
            throw new HttpNotFoundException($request);
        }

        // Get storage variables
        $original_filename       = $workshop_file->getFilename();
        $workshop_item_dir       = $_ENV['APP_WORKSHOP_STORAGE'] . '/' . $workshop_item->getId();
        $workshop_item_files_dir = $workshop_item_dir . '/files';
        $workshop_file_path      = $workshop_item_files_dir . '/' . $workshop_file->getStorageFilename();

        // Make sure file exists
        if(!\file_exists($workshop_file_path)){
            throw new \Exception("Workshop file does not exist: '{$workshop_file_path}'");
        }

        // Remove file and double check
        @\unlink($workshop_file_path);
        if(\file_exists($workshop_file_path)){
            throw new \Exception("Workshop file still exists after removal...: '{$workshop_file_path}'");
        }

        // Remember weight so we can keep the numbering without jumps
        $deleted_weight = $workshop_file->getWeight();

        // Remove from DB
        $em->remove($workshop_file);
        $em->flush();

        // Fix weights for existing files
        $query_builder = $em->getConnection()->createQueryBuilder();
        $query_builder
            ->update('workshop_file')
            ->where(
                $query_builder->expr()->and(
                    $query_builder->expr()->eq('item_id', $workshop_item->getId()),
                    $query_builder->expr()->gt('weight', $deleted_weight)
                ))
            ->set('weight', 'weight - 1');
        $query_builder->executeQuery();

        // Show success notice to user
        $flash->success(\sprintf(
            "The file '%s' has been successfully removed!",
            $original_filename
        ));

        // Redirect back to file list
        $response = $response->withHeader('Location', '/moderate/workshop/' . $workshop_item->getId() . '/files')->withStatus(302);
        return $response;
    }

    public function move(
        Request $request,
        Response $response,
        FlashMessage $flash,
        TwigEnvironment $twig,
        Account $account,
        EntityManager $em,
        UploadSizeHelper $upload_size_helper,
        WorkshopBrokenFileHandler $broken_file_handler,
        CsrfGuard $csrf_guard,
        $item_id,
        $file_id,
        $direction,
        $token_name,
        $token_value,
    )
    {
        // Check for valid direction
        if(!\in_array($direction, ['up', 'down'])){
            throw new HttpNotFoundException($request);
        }

        // Check for valid CSRF token
        $valid = $csrf_guard->validateToken($token_name, $token_value);
        if(!$valid){
            throw new HttpNotFoundException($request);
        }

        // Check if workshop item exists
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($item_id);
        if(!$workshop_item){
            throw new HttpNotFoundException($request);
        }

        // Check if workshop file exists
        $workshop_file = $em->getRepository(WorkshopFile::class)->find($file_id);
        if(!$workshop_file){
            throw new HttpNotFoundException($request);
        }

        // Check if file is attached to item
        if($workshop_file->getItem() !== $workshop_item){
            throw new HttpNotFoundException($request);
        }

        // Make sure we can move up
        if($direction === 'up' && $workshop_file->getWeight() <= 0){
            $flash->error('Failed to move workshop file.');
            $response = $response->withHeader('Location', '/moderate/workshop/' . $workshop_item->getId() . '/files')->withStatus(302);
            return $response;
        }

        // Make sure we can move down
        if($direction === 'down' && $workshop_file->getWeight() >= (\count($workshop_item->getFiles()) - 1)){
            $flash->error('Failed to move workshop file.');
            $response = $response->withHeader('Location', '/moderate/workshop/' . $workshop_item->getId() . '/files')->withStatus(302);
            return $response;
        }

        // Determine wanted new weight
        $wanted_weight = $direction === 'up' ? $workshop_file->getWeight() - 1 : $workshop_file->getWeight() + 1;

        // Check if we can change with the file with the wanted weight
        $workshop_file_at_wanted_weight = $em->getRepository(WorkshopFile::class)->findOneBy(['item' => $workshop_item, 'weight' => $wanted_weight]);
        if(!$workshop_file_at_wanted_weight || $workshop_file_at_wanted_weight == $workshop_file){
            throw new \Exception('something went wrong..');
        }

        // Change weights between files
        $workshop_file_at_wanted_weight->setWeight($workshop_file->getWeight());
        $workshop_file->setWeight($wanted_weight);

        // Save changes to DB
        $em->flush();

        // Handle broken file checker
        $broken_file_handler->handleItem($workshop_item, true);

        // Redirect back to file list
        $flash->success('The file has been successfully moved.');
        $response = $response->withHeader('Location', '/moderate/workshop/' . $workshop_item->getId() . '/files')->withStatus(302);
        return $response;

    }

    public function rename(
        Request $request,
        Response $response,
        FlashMessage $flash,
        TwigEnvironment $twig,
        Account $account,
        EntityManager $em,
        CsrfGuard $csrf_guard,
        $item_id,
        $file_id
    )
    {

        $post     = $request->getParsedBody();
        $new_name = \trim((string) ($post['name'] ?? null));

        // Make sure new name is valid
        if(!$new_name || \strlen($new_name) > 64 || \strlen($new_name) < 1){
            return $request;
        }

        // Check if workshop item exists
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($item_id);
        if(!$workshop_item){
            throw new HttpNotFoundException($request);
        }

        // Check if workshop file exists
        $workshop_file = $em->getRepository(WorkshopFile::class)->find($file_id);
        if(!$workshop_file){
            throw new HttpNotFoundException($request);
        }

        // Check if file is attached to item
        if($workshop_file->getItem() !== $workshop_item){
            throw new HttpNotFoundException($request);
        }

        // Get current filename name and extension
        $filename = $workshop_file->getFilename();
        $ext      = \strtolower(\pathinfo($filename, \PATHINFO_EXTENSION));

        // Define new filename
        $new_filename = $new_name . '.' . $ext;

        // TODO: check valid filename

        // Save to DB
        $workshop_file->setFilename($new_filename);
        $em->flush();

        // Return
        $response->getBody()->write(
            \json_encode([
                'success'  => true,
                'filename' => $workshop_file->getFilename(),
                'csrf'     => [
                    'keys' => [
                        'name'  => $csrf_guard->getTokenNameKey(),
                        'value' => $csrf_guard->getTokenValueKey(),
                    ],
                    'name'  => $csrf_guard->getTokenName(),
                    'value' => $csrf_guard->getTokenValue()
                ],
            ])
        );

        // Output JSON
        $response = $response->withHeader('Content-Type', 'application/json');

        return $response;
    }

    public function mark_as_broken(
        Request $request,
        Response $response,
        FlashMessage $flash,
        EntityManager $em,
        CsrfGuard $csrf_guard,
        WorkshopBrokenFileHandler $broken_file_handler,
        $item_id,
        $file_id,
        $token_name,
        $token_value,
    ) {

        // Check for valid CSRF token
        $valid = $csrf_guard->validateToken($token_name, $token_value);
        if(!$valid){
            throw new HttpNotFoundException($request);
        }

        // Check if workshop item exists
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($item_id);
        if(!$workshop_item){
            throw new HttpNotFoundException($request);
        }

        // Check if workshop file exists
        $workshop_file = $em->getRepository(WorkshopFile::class)->find($file_id);
        if(!$workshop_file){
            throw new HttpNotFoundException($request);
        }

        // Check if file is attached to item
        if($workshop_file->getItem() !== $workshop_item){
            throw new HttpNotFoundException($request);
        }

        // Mark file as broken
        $broken_file_handler->markFileAsBroken($workshop_file, true);

        // Redirect back to file list
        $flash->success('The file has been successfully marked as broken.');
        $response = $response->withHeader('Location', '/moderate/workshop/' . $workshop_item->getId() . '/files')->withStatus(302);
        return $response;
    }

    public function unmark_as_broken(
        Request $request,
        Response $response,
        FlashMessage $flash,
        EntityManager $em,
        CsrfGuard $csrf_guard,
        WorkshopBrokenFileHandler $broken_file_handler,
        $item_id,
        $file_id,
        $token_name,
        $token_value,
    ) {

        // Check for valid CSRF token
        $valid = $csrf_guard->validateToken($token_name, $token_value);
        if(!$valid){
            throw new HttpNotFoundException($request);
        }

        // Check if workshop item exists
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($item_id);
        if(!$workshop_item){
            throw new HttpNotFoundException($request);
        }

        // Check if workshop file exists
        $workshop_file = $em->getRepository(WorkshopFile::class)->find($file_id);
        if(!$workshop_file){
            throw new HttpNotFoundException($request);
        }

        // Check if file is attached to item
        if($workshop_file->getItem() !== $workshop_item){
            throw new HttpNotFoundException($request);
        }

        // Mark file as broken
        $broken_file_handler->unmarkFileAsBroken($workshop_file, true);

        // Redirect back to file list
        $flash->success('The file has been successfully unmarked as broken.');
        $response = $response->withHeader('Location', '/moderate/workshop/' . $workshop_item->getId() . '/files')->withStatus(302);
        return $response;
    }
}
