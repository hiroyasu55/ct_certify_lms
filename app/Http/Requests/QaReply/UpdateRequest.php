<?php

declare(strict_types=1);

namespace App\Http\Requests\QaReply;

use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * 質問スレッドへの回答更新リクエスト。本文のみ更新可能。
 */
class UpdateRequest extends FormRequest
{
    public function authorize(): Response
    {
        // Policyの規定するResponseを返す
        return Gate::inspect('update', $this->route('reply'));
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
