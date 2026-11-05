<?php

declare(strict_types=1);

namespace Tests\Fixtures\PermissionRequests;

use App\Contracts\PermissionRequestExtension;
use App\Exceptions\PermissionApprovalException;
use App\Models\PermissionRequest;
use App\Models\Trooper;
use Illuminate\Validation\Validator;

/**
 * A permission request extension for tests: asks for badges, kept in memory.
 */
final class BadgeRequests implements PermissionRequestExtension
{
    /**
     * @var array<int, array<int, string>> Badges requested, by permission request ID.
     */
    public static array $stored = [];

    /**
     * @var array<int, int> Approved permission request IDs.
     */
    public static array $approved = [];

    public static ?string $approval_failure = null;

    public static function reset(): void
    {
        self::$stored = [];
        self::$approved = [];
        self::$approval_failure = null;
    }

    public function key(): string
    {
        return 'badges';
    }

    public function formSection(Trooper $trooper): string
    {
        return '<input type="checkbox" name="extensions[badges][names][]" value="gold">';
    }

    public function rules(): array
    {
        return ['names' => ['array'], 'names.*' => ['string', 'in:gold,silver']];
    }

    public function validate(Validator $validator, Trooper $trooper, array $input): void
    {
        if (in_array('silver', $input['names'] ?? [], true) && $trooper->is_moderator)
        {
            $validator->errors()->add('extensions.badges.names', 'Moderators already have silver.');
        }
    }

    public function requestsChange(array $input): bool
    {
        return !empty($input['names']);
    }

    public function store(PermissionRequest $permission_request, array $input): void
    {
        if ($this->requestsChange($input))
        {
            self::$stored[$permission_request->id] = $input['names'];
        }
    }

    public function summary(PermissionRequest $permission_request): array
    {
        return ['Badges' => implode(', ', self::$stored[$permission_request->id] ?? [])];
    }

    public function approve(PermissionRequest $permission_request): void
    {
        if (self::$approval_failure !== null)
        {
            throw new PermissionApprovalException(self::$approval_failure);
        }

        self::$approved[] = $permission_request->id;
    }
}
