<?php

declare(strict_types=1);

namespace App\Http\Requests\QaReply;

use App\Models\QaReply;
use Illuminate\Foundation\Http\FormRequest;

/**
 * 質問スレッドへの回答投稿リクエスト。本文のみ受け取る。
 */
class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', [QaReply::class, $this->route('thread')]) ?? false;
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
