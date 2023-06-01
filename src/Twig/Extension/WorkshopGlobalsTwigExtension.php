<?php

namespace App\Twig\Extension;

use App\Enum\WorkshopCategory;

use App\Config\Config;
use Doctrine\ORM\EntityManager;
use App\Entity\WorkshopTag;
use App\Entity\GithubRelease;

class WorkshopGlobalsTwigExtension extends \Twig\Extension\AbstractExtension implements \Twig\Extension\GlobalsInterface
{

    public function __construct(
        private EntityManager $em,
    ){}

    public function getName(): string
    {
        return 'workshop_globals_extension';
    }

    public function getGlobals(): array
    {
        $categories_map = [];
        foreach(WorkshopCategory::cases() as $enum) {
            $categories_map[$enum->value] = $enum->name;
        }

        $stable_builds_map = [];
        $stable_builds = $this->em->getRepository(GithubRelease::class)->findBy([], ['timestamp' => 'DESC']);
        foreach($stable_builds as $stable_build){
            $stable_builds_map[$stable_build->getId()] = $stable_build;
        }

        return [
            'workshop_globals' => [
                'categories'                    => WorkshopCategory::cases(),
                'categories_without_difficulty' => Config::get('app.workshop.item_categories_without_difficulty'),
                'categories_map'                => $categories_map,
                'tags'                          => $this->em->getRepository(WorkshopTag::class)->findBy([], ['name' => 'ASC']),
                'stable_builds'                 => $stable_builds_map,
            ]
        ];
    }
}
