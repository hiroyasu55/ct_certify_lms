<?php

declare(strict_types=1);

namespace App\Http\Requests\QaReply;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 質問スレッドへの回答更新リクエスト。本文のみ更新可能。
 */
class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('reply')) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'body' => '回答本文',
        ];
    }
}
