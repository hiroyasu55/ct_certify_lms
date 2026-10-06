<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\CertificationStatus;
use App\Enums\UserRole;
use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;

/**
 * 質問スレッドへの回答の認可ルール。
 *
 * 回答投稿は受講生 / コーチ(管理者は不可)、編集・削除は回答者本人のみ許可する。
 */
class QaReplyPolicy
{
    public function create(User $auth, QaThread $thread): bool
    {
        return match ($auth->role) {
            UserRole::Student => $thread->certification->status === CertificationStatus::Published,
            UserRole::Coach => $thread->certification->status === CertificationStatus::Published
                && $thread->certification->coaches->contains('id', $auth->id),
            default => false,
        };
    }

    public function update(User $auth, QaReply $reply): bool
    {
        return $reply->user_id === $auth->id;
    }

    public function delete(User $auth, QaReply $reply): bool
    {
        return match ($auth->role) {
            UserRole::Admin => true,
            default => $reply->user_id === $auth->id,
        };
    }
}
