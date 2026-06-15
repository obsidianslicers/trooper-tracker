<?php

declare(strict_types=1);

namespace App\Features\Troopers\Exceptions;

use App\Models\Organization;
use RuntimeException;

class DuplicateOrganizationIdentifierException extends RuntimeException
{
    public function __construct(
        public readonly Organization $organization,
        public readonly string $identifier,
    ) {
        parent::__construct($this->flashMessage());
    }

    public function flashMessage(): string
    {
        $label = $this->organization->identifier_display ?: 'identifier';

        return "{$this->organization->name} {$label} {$this->identifier} is already assigned to another trooper.";
    }
}
