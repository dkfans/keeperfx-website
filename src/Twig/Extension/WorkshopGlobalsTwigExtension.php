<?php

namespace App\Twig\Extension;

use App\Enum\WorkshopType;

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
        return [
            'workshop_globals' => [
                'types'                    => WorkshopType::cases(),
                'types_without_difficulty' => Config::get('app.workshop.item_types_without_difficulty'),
                'tags'                     => $this->em->getRepository(WorkshopTag::class)->findBy([], ['name' => 'ASC']),
                'stable_builds'            => $this->em->getRepository(GithubRelease::class)->findBy([], ['timestamp' => 'DESC']),
            ]
        ];
    }
}
