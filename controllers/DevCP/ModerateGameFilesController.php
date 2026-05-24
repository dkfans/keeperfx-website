<?php

namespace App\Controller\DevCP;

use App\Entity\CrashReport;

use App\FlashMessage;
use App\Config\Config;
use App\Entity\GameFileIndex;
use Doctrine\ORM\EntityManager;
use Slim\Csrf\Guard as CsrfGuard;
use Twig\Environment as TwigEnvironment;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;
use App\Enum\ReleaseType;

class ModerateGameFilesController
{
    // index

    public function listIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em
    ) {
        $response->getBody()->write(
            $twig->render('devcp/game-files/game-files.list.devcp.html.twig', [
                'stable_game_file_indexes' => $em->getRepository(GameFileIndex::class)->findBy(['release_type' => 'STABLE'], ['id' => 'DESC']),
                'alpha_game_file_indexes'  => $em->getRepository(GameFileIndex::class)->findBy(['release_type' => 'ALPHA'], ['id' => 'DESC']),
            ])
        );

        return $response;
    }

    public function view(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        $type,
        $version
    ) {

        // Try and get the release type
        try {
            $release_type = ReleaseType::from($type);
        } catch (\ValueError $ex) {
            throw new HttpNotFoundException($request);
        }

        // Find game file index
        $game_file_index = $em->getRepository(GameFileIndex::class)->findOneBy(['release_type' => $release_type, 'version' => $version]);
        if (!$game_file_index) {
            throw new HttpNotFoundException($request);
        }
        // Show output
        $response->getBody()->write(
            $twig->render('devcp/game-files/game-files.devcp.html.twig', [
                'game_version'        => $game_file_index->getVersion(),
                'filemap_widget_data' => $this->buildWidgetFileTreeFromFilemap($game_file_index->getData()),
            ])
        );
        return $response;
    }

    /**
     * Converts filemap (path => checksum) to widget tree format
     */
    private function buildWidgetFileTreeFromFilemap(array $filemap): array
    {
        $root = [];

        foreach ($filemap as $path => $checksum) {
            // Normalize path and split into parts
            $normalized = ltrim($path, '/');
            $parts = $normalized === '' ? [] : explode('/', $normalized);
            $filename = array_pop($parts);
            $current = &$root;

            // Traverse/create directory structure
            foreach ($parts as $dir) {
                $found = false;
                foreach ($current as &$node) {
                    if ($node['text'] === $dir) {
                        $current = &$node['nodes'];
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $newNode = ['text' => $dir, 'nodes' => []];
                    $current[] = $newNode;
                    $current = &$current[count($current) - 1]['nodes'];
                }
            }

            // Add the file with checksum tag
            $current[] = [
                'text' => $filename,
                'tags' => [$checksum]
            ];
        }

        // Sort: directories first, then files, case-insensitive
        $this->sortWidgetTree($root);
        return $root;
    }

    /**
     * Recursively sorts tree: dirs before files, case-insensitive
     */
    private function sortWidgetTree(array &$nodes): void
    {
        usort($nodes, function ($a, $b) {
            $aIsDir = isset($a['nodes']);
            $bIsDir = isset($b['nodes']);

            // Directories come before files
            if ($aIsDir !== $bIsDir) {
                return $aIsDir ? -1 : 1;
            }

            // Same type: sort by text case-insensitively
            return strcasecmp($a['text'], $b['text']);
        });

        foreach ($nodes as &$node) {
            if (isset($node['nodes'])) {
                $this->sortWidgetTree($node['nodes']);
            }
        }
    }
}
