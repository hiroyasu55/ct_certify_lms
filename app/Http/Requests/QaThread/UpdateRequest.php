<?php

declare(strict_types=1);

namespace App\Http\Requests\QaThread;

use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * 質問スレッド更新リクエスト。タイトル・本文のみ更新可能(資格は変更不可)。
 */
class UpdateRequest extends FormRequest
{
    public function authorize(): Response
    {
        // Policyの規定するResponseを返す
        return Gate::inspect('update', $this->route('thread'));
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
