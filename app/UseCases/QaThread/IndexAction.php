<?php

declare(strict_types=1);

namespace App\UseCases\QaThread;

use App\Enums\CertificationStatus;
use App\Enums\UserRole;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 資格ごとの QaThreadサマリを取得する Action。
 */
final class IndexAction
{
    /**
     * @param array{certification_id?: ?string, status?: ?string, keyword?: ?string} $filters
     *
     * @return LengthAwarePaginator<QaThread>
     */
    public function __invoke(User $user, ?array $filters = []): LengthAwarePaginator
    {
        $query = QaThread::query()
            ->with(['certification', 'user'])
            ->withCount('replies')
            ->when(
                $filters['certification_id'] ?? null,
                fn ($q) => $q->where('certification_id', $filters['certification_id'])
            )
            ->when(
                $filters['status'] ?? null,
                fn ($q) => $q->where('status', $filters['status'])
            )
            // 管理者以外は公開中資格の質問のみ閲覧可
            ->when(
                $user->role != UserRole::Admin,
                fn ($q) => $q->whereHas(
                    'certification',
                    fn ($q) => $q->where('status', CertificationStatus::Published),
                )
            )
            // コーチは担当する資格の質問のみ閲覧可
            ->when(
                $user->role == UserRole::Coach,
                fn ($q) => $q->whereHas(
                    'certification',
                    fn ($q) => $q->assignedTo($user),
                )
            )
            // キーワード検索
            ->when(
                $filters['keyword'] ?? null,
                fn ($q) => $q->where(fn ($q) => $q
                    ->where('title', 'like', "%{$filters['keyword']}%")
                    ->orWhere('body', 'like', "%{$filters['keyword']}%")
                    ->orWhereHas(
                        'replies',
                        fn ($q) => $q->where('body', 'like', "%{$filters['keyword']}%")
                    )
                )
            )
            ->orderByDesc('created_at');

        return $query->paginate(20)->withQueryString();
    }
}
