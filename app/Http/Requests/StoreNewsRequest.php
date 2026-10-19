<?php

namespace App\Http\Requests;

use App\Enums\PermissionName;
use App\Models\News;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo(PermissionName::ManageNews->value);
    }

    public function rules(): array
    {
        // The news type is fixed at creation: switching an item that already
        // has confirmations back to read-only would erase that record.
        $existing = $this->route('news');
        $needsAck = $existing ? $existing->requires_acknowledgement : $this->boolean('requires_acknowledgement');

        return [
            'requires_acknowledgement' => ['nullable', 'boolean'],
            'recipient_ids' => [Rule::requiredIf($needsAck), 'nullable', 'array', $needsAck ? 'min:1' : 'max:0'],
            'recipient_ids.*' => ['integer', Rule::exists('users', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
            'template' => ['required', Rule::in(News::TEMPLATES)],
            'image' => ['nullable', 'image', 'max:10240'],
            'is_published' => ['nullable', 'boolean'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }
}
