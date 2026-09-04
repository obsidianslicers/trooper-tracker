<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Faq;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $item = $this->route('item');

        if ($item === null)
        {
            throw new AuthorizationException('FAQ not found or unauthorized.');
        }

        return $this->user()->can('delete', $item);
    }

    public function rules(): array
    {
        return [];
    }
}
