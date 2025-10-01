<?php

namespace App\Controller\Api\v1\Workshop;

use URLify;

use App\Entity\WorkshopItem;

use Doctrine\ORM\EntityManager;
use Psr\SimpleCache\CacheInterface;
use App\Twig\Extension\Markdown\CustomMarkdownConverter;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class WorkshopBrowseApiController
{

    public function listLatest(
        Request $request,
        Response $response,
        EntityManager $em,
        CustomMarkdownConverter $md_converter,
        // TODO: CacheInterface $cache,
    ) {
        $workshop_items = [];
        $workshop_item_entities = $em->getRepository(WorkshopItem::class)->findBy(['is_last_file_broken' => false], ['creation_orderby_timestamp' => 'DESC'], 10);

        if ($workshop_item_entities) {
            /** @var WorkshopItem $entity */
            foreach ($workshop_item_entities as $entity) {

                // Get submitter username
                $submitter = $entity->getSubmitter();
                if (!$submitter) {
                    $username = 'KeeperFX Team';
                } else {
                    $username = $submitter->getUsername();
                }

                $description_with_markdown = $entity->getDescription();
                $description_with_html     = $md_converter->convert($description_with_markdown);
                $description_text_only     = \trim(\strip_tags($description_with_html));

                $workshop_items[] = [
                    'name'                 => $entity->getName(),
                    'category'             => $entity->getCategory()->name,
                    'created_timestamp'    => $entity->getCreatedTimestamp()->format('Y-m-d'),
                    'install_instructions' => $entity->getInstallInstructions(),

                    'description_markdown' => $description_with_markdown,
                    'description_html'     => $description_with_html,
                    'description'          => $description_text_only,

                    'url'                  => $_ENV['APP_ROOT_URL'] . '/workshop/item/' . $entity->getId() . '/' . URLify::slug($entity->getName()),

                    'image'                => \count($entity->getImages()) > 0 ?
                        $_ENV['APP_ROOT_URL'] . '/workshop/image/' . $entity->getId() . '/' . $entity->getImages()[0]->getFilename() :
                        $_ENV['APP_ROOT_URL'] . '/img/no-image-256.png',

                    'thumbnail'             => $entity->getThumbnail() ?
                        $_ENV['APP_ROOT_URL'] . '/workshop/image/' . $entity->getId() . '/' . $entity->getThumbnail() :
                        null,

                    'submitter' => [
                        'username' => $username,
                    ],
                ];
            }
        }

        $response->getBody()->write(
            \json_encode(['workshop_items' => $workshop_items])
        );

        // Output JSON
        $response = $response->withHeader('Content-Type', 'application/json');

        return $response;
    }
}
