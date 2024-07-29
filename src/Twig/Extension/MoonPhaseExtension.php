<?php

namespace App\Twig\Extension;

/**
 * Moon Phase Twig Extension.
 */
class MoonPhaseExtension extends \Twig\Extension\AbstractExtension implements \Twig\Extension\GlobalsInterface
{
    private \Solaris\MoonPhase $moon_phase;

    public function __construct() {
        $this->moon_phase = new \Solaris\MoonPhase();
    }

    public function getName(): string
    {
        return 'moon_phase_extension';
    }

    public function getGlobals(): array
    {
        // Variables
        $is_full_moon = false;
        $is_near_full_moon = false;
        $is_new_moon = false;
        $is_near_new_moon = false;

        // Get phase
        $phase = $this->moon_phase->getPhase();
        $moon_phase_data['phase'] = $phase;

        // Check KFX type of moon
        if ($phase > 0.475 && $phase < 0.525){
            $is_full_moon = true;
        } elseif ($phase > 0.45 && $phase < 0.55) {
            $is_near_full_moon = true;
        } elseif ($phase < 0.025 || $phase > 0.975) {
            $is_new_moon = true;
        } elseif ($phase < 0.05 || $phase > 0.95) {
            $is_near_new_moon = true;
        }

        // Get phase image
        $phase_img_filename = $this->moon_phase->getPhaseName();
        $phase_img_filename = \strtolower($phase_img_filename);
        $phase_img_filename = \str_replace(' ', '-', $phase_img_filename);
        $phase_img_filename .= '.png';

        // Get phase image URL
        $phase_img_url = '/img/moon/' . $phase_img_filename;

        // Return
        return [
            'moon_phase' => [
                'phase'             => $phase,
                'name'              => $this->moon_phase->getPhaseName(),
                'img'               => $phase_img_url,
                'next_full_moon'    => (new \DateTime())->setTimestamp((int) $this->moon_phase->getPhaseNextFullMoon()),
                'next_new_moon'     => (new \DateTime())->setTimestamp((int) $this->moon_phase->getPhaseNextNewMoon()),
                'is_full_moon'      => $is_full_moon,
                'is_near_full_moon' => $is_near_full_moon,
                'is_new_moon'       => $is_new_moon,
                'is_near_new_moon'  => $is_near_new_moon,
            ],
        ];
    }
}
