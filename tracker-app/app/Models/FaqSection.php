<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Base\FaqSection as BaseFaqSection;
use App\Models\Concerns\HasTrooperStamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FaqSection extends BaseFaqSection
{
    use HasFactory;
    use HasTrooperStamps;
}
