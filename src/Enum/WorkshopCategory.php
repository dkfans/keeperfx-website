<?php

namespace App\Enum;

/**
 * Workshop item categories.
 *
 * If a new category is added and it should not have a difficulty rating:
 *      Update the config value: `app.workshop.item_categories_without_difficulty`
 */
enum WorkshopCategory: int {

    // With difficulty rating
    case Map                = 10;
    case MapPack            = 15;
    case Campaign           = 20;
    case MultiplayerMap     = 30;
    case MultiplayerMapPack = 35;
    case Mod                = 40;

    // Without difficulty rating
    case Creature    = 45;
    case Application = 50;
    case Other       = 100;
}
