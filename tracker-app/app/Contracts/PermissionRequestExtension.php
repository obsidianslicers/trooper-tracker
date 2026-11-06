<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Exceptions\PermissionApprovalException;
use App\Models\PermissionRequest;
use App\Models\Trooper;
use Illuminate\Validation\Validator;

/**
 * Something else a permission request can ask for, such as forum user groups.
 * A plugin registers one with addPermissionRequestExtension().
 *
 * Its inputs travel as "extensions[<key>][...]": the form section names them so,
 * rules() and validate() check them, and store() and approve() act on them.
 */
interface PermissionRequestExtension
{
    /**
     * Lowercase letters, digits, dashes and underscores.
     */
    public function key(): string;

    /**
     * HTML added to the Request Permissions form once a trooper is picked, or ''
     * for none. Name inputs "extensions[<key>][field]" or "extensions[<key>][field][]".
     */
    public function formSection(Trooper $trooper): string;

    /**
     * Rules for the inputs, keyed by field name without the "extensions.<key>." prefix.
     *
     * @return array<string, mixed>
     */
    public function rules(): array;

    /**
     * Checks across fields, run after every rule passes.
     *
     * @param  array<string, mixed>  $input  This extension's validated inputs.
     */
    public function validate(Validator $validator, Trooper $trooper, array $input): void;

    /**
     * Whether the inputs ask for any change, so a request with nothing else is allowed.
     *
     * @param  array<string, mixed>  $input
     */
    public function requestsChange(array $input): bool;

    /**
     * Save the inputs for this request.
     *
     * @param  array<string, mixed>  $input
     */
    public function store(PermissionRequest $permission_request, array $input): void;

    /**
     * What this request asks for, shown to reviewers and in notifications.
     *
     * @return array<string, string> Label => value; leave out empty lines.
     */
    public function summary(PermissionRequest $permission_request): array;

    /**
     * Apply the request. Runs inside the approval's transaction, after core's changes.
     *
     * @throws PermissionApprovalException to stop the approval and roll it back.
     */
    public function approve(PermissionRequest $permission_request): void;
}
