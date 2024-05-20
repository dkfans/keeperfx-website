<?php

namespace App;

use App\Entity\Ban;
use App\Enum\BanType;

use Doctrine\ORM\EntityManager;
use Psr\SimpleCache\CacheInterface;
use Xenokore\Utility\Helper\StringHelper;

class BanChecker {

    public const BAN_CACHE_KEY = 'bans';

    private $loaded = false;

    private $bans = [
        BanType::IP->value       => [],
        BanType::Hostname->value => [],
        BanType::ISP->value      => [],
    ];

    public function __construct(
        private EntityManager $em,
        private CacheInterface $cache,
    ){}

    public function check(BanType $type, string $string): bool
    {
        // Load bans if they are not loaded yet
        if($this->loaded === false){
            $this->loadBans();
        }

        // Check if the clients data matches a ban
        foreach($this->bans[$type->value] as $pattern)
        {
            if(StringHelper::match($string, $pattern))
            {
                return true;
            }
        }

        // Not banned
        return false;
    }

    public function checkAll(?string $ip = null, ?string $hostname = null, ?string $isp = null)
    {
        if($ip){
            if($this->check(BanType::IP, $ip)){
                return true;
            }
        }
        if($hostname){
            if($this->check(BanType::Hostname, $hostname)){
                return true;
            }
        }
        if($isp){
            if($this->check(BanType::ISP, $isp)){
                return true;
            }
        }

        // Not banned
        return false;
    }

    private function loadBans()
    {
        // Load bans from cache
        $bans = $this->cache->get(self::BAN_CACHE_KEY, null);
        if($bans != null){
            $this->bans = $bans;
            $this->loaded = true;
            return;
        }

        // Load database bans
        $db_bans = $this->em->getRepository(Ban::class)->findAll();
        foreach($db_bans as $ban)
        {
            $this->bans[$ban->getType()->value][] = $ban->getPattern();
        }

        // Store database bans into cache
        $this->cache->set(self::BAN_CACHE_KEY, $this->bans);

        // Bans are loaded
        $this->loaded = true;
    }

}
