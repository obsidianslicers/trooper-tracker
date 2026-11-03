<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Plugins;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates an uploaded plugin zip. Refused outright while uploads are turned off.
 */
class InstallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) config('plugins.uploads.enabled', false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $max_kilobytes = (int) config('plugins.uploads.max_kilobytes', 10240);

        return [
            'archive' => ['required', 'file', 'mimes:zip', "max:{$max_kilobytes}"],
        ];
    }
}
