<?php

declare(strict_types=1);

namespace App\Http\Requests\QaThread;

use App\Enums\QaThreadStatus;
use App\Enums\UserRole;
use App\Models\QaThread;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 質問一覧へのアクセス検証。受講生 / コーチ / 管理者共通
 */
class IndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', QaThread::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'certification_id' => ['nullable', 'ulid', Rule::exists('certifications', 'id')],
            'status' => ['nullable', 'string', Rule::in(['unresolved', 'resolved'])],
            'keyword' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array{certification_id: ?string, keyword: ?string}
     */
    public function filters(): array
    {
        return [
            'certification_id' => $this->input('certification_id'),
            'status' => $this->input('status'),
            'keyword' => $this->input('keyword'),
        ];
    }
}
