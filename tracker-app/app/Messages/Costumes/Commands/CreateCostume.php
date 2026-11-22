<?php

declare(strict_types=1);

namespace App\Messages\Costumes\Commands;

use App\Models\Costume;
use Hyperdrive\Message;

/**
 * @method static Costume call(string $name)
 */
final class CreateCostume extends Message
{
    public function __construct(private readonly string $name) {}

    public function handle(): Costume
    {
        $costume = new Costume;
        $costume->name = $this->name;
        $costume->save();

        return $costume;
    }
}
