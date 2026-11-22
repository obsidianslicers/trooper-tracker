<?php

declare(strict_types=1);

namespace App\Messages\Awards\PageData;

use App\Enums\AwardFrequency;
use App\Messages\Awards\Resources\AwardResource;
use App\Messages\Organizations\Queries\GetModeratedOrganizationOptions;
use App\Models\Award;
use App\Models\Organization;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * The create award form: blank, a copy of an award the actor moderates, or set to one of
 * the actor's organizations.
 *
 * @method static array call(Actor $actor, int|null $copy_id = null, int|null $organization_id = null)
 */
final class CreateAwardPageData extends Message
{
    /** @param  Actor&Trooper  $actor */
    public function __construct(
        private readonly Actor $actor,
        private readonly ?int $copy_id = null,
        private readonly ?int $organization_id = null,
    ) {}

    public function handle(): array
    {
        $award = $this->newAward();

        if ($this->organization_id !== null)
        {
            $award->organization_id = $this->organization_id;
        }

        if ($award->organization_id !== null)
        {
            $organization = Organization::moderatedBy($this->actor)
                ->findOrFail($award->organization_id);

            $award->setRelation('organization', $organization);
        }

        return [
            'award' => new AwardResource($award),
            'frequency_options' => AwardFrequency::toValueLabels(),
            'organization_options' => GetModeratedOrganizationOptions::call(trooper: $this->actor),
        ];
    }

    private function newAward(): Award
    {
        if ($this->copy_id === null)
        {
            $award = new Award;
            $award->frequency = AwardFrequency::ONCE;

            return $award;
        }

        $copy = Award::moderatedBy($this->actor)->findOrFail($this->copy_id);

        $award = $copy->replicate();
        $award->name = __('admin.:title (Copy)', ['title' => $copy->name]);

        return $award;
    }
}
