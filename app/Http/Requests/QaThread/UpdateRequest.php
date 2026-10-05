<?php

declare(strict_types=1);

namespace App\Http\Requests\QaThread;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 質問スレッド更新リクエスト。タイトル・本文のみ更新可能(資格は変更不可)。
 */
class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('thread')) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'タイトル',
            'body' => '本文',
        ];
    }
}
