<?php

declare(strict_types=1);

namespace App\Http\Requests\QaThread;

use App\Enums\CertificationStatus;
use App\Models\QaThread;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 質問スレッド新規投稿リクエスト。公開中の資格・タイトル・本文を受け取る。
 */
class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', QaThread::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'certification_id' => [
                'required',
                'string',
                Rule::exists('certifications', 'id')->where('status', CertificationStatus::Published->value),
            ],
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'certification_id.required' => '公開中の資格を選んでください。',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'certification_id' => '資格',
            'title' => 'タイトル',
            'body' => '本文',
        ];
    }
}
