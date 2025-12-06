<?php

declare(strict_types=1);

namespace App\Enums;

enum EventType: string
{
    use HasEnumHelpers;

    case REGULAR = 'regular';
    case ARMOR_PARTY = 'armorparty';
    case CHARITY = 'charity';
    case PUBLIC_RELATIONS = 'publicrelations';
    case DISNEY = 'disney';
    case LUCAS_FILM_LIMITED = 'lucasfilm';
    case CONVENTION = 'convention';
    case HOSPITAL = 'hospital';
    case WEDDING = 'wedding';
    case BIRTHDAY_PARTY = 'birthdayparty';
    case VIRTUAL_TROOP = 'virtualtroop';
    case OTHER = 'other';
}