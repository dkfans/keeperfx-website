<?php

namespace App\Console\Command\Website;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;


class FetchGeoIpDatabaseCommand extends Command
{
    public const GEO_IP_URL = 'https://cdn.jsdelivr.net/npm/@ip-location-db/geo-whois-asn-country-mmdb/geo-whois-asn-country.mmdb';

    protected function configure()
    {
        $this->setName("website:fetch-geoip-db")
            ->setDescription("Download the GeoIP database (mmdb)");
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln("[>] Fetching latest GeoIP database...");

        // Make sure a Github token is set
        if (
            !isset($_ENV['APP_GEOIP_DATABASE'])
            || empty($_ENV['APP_GEOIP_DATABASE'])
        ) {
            $output->writeln("[-] GeoIP storage location not set");
            $output->writeln("[>] ENV VAR: 'APP_GEOIP_DATABASE'");
            return Command::FAILURE;
        }

        $client = new \GuzzleHttp\Client([
            'verify' => false, // Don't verify SSL connection
        ]);

        $temp_geo_db_filepath = $_ENV['APP_GEOIP_DATABASE'] . '.new';

        if (\file_exists($temp_geo_db_filepath)) {
            unlink($temp_geo_db_filepath);
        }

        $client->request('GET', self::GEO_IP_URL, ['sink' => $temp_geo_db_filepath]);
        if (!\file_exists($temp_geo_db_filepath)) {
            $output->writeln("[-] Failed to download GeoIP DB");
            return Command::FAILURE;
        }

        if (\file_exists($_ENV['APP_GEOIP_DATABASE'])) {
            unlink($_ENV['APP_GEOIP_DATABASE']);
        }

        if (rename($temp_geo_db_filepath, $_ENV['APP_GEOIP_DATABASE']) == false) {
            $output->writeln("[-] Failed to move downloaded GeoIP DB");
            return Command::FAILURE;
        }

        $output->writeln("[>] Done!");

        return Command::SUCCESS;
    }
}
