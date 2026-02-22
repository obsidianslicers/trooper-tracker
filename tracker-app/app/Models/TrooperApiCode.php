<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Base\TrooperApiCode as BaseTrooperApiCode;

/**
 * Represents an API access code issued to a trooper.
 *
 * These codes are used to authenticate or authorize API usage for a trooper.
 */
class TrooperApiCode extends BaseTrooperApiCode
{
    use HasFactory;


}
