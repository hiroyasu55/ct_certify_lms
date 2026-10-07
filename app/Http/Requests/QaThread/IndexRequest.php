<?php

declare(strict_types=1);

namespace App\Http\Requests\QaThread;

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
     * コーチが担当中の資格を certification_id に指定しているか
     *
     * 一覧クエリでの資格 ID 列挙を防ぐため、Controller はこれが true なら 403 にする。
     */
    public function isUnassignedCertificationForCoach(): bool
    {
        $user = $this->user();
        $certificationId = $this->input('certification_id');

        if ($user?->role !== UserRole::Coach || blank($certificationId)) {
            return false;
        }

        return ! in_array($certificationId, $user->coachingCertificationIds(), true);
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
