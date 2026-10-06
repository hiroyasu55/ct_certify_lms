<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\CertificationStatus;
use App\Enums\UserRole;
use App\Models\QaThread;
use App\Models\User;

/**
 * 質問スレッドの認可ルール。
 *
 * 閲覧は受講生 / コーチ、投稿は受講生のみ、編集・削除は投稿者本人のみ許可する。
 */
class QaThreadPolicy
{
    public function viewAny(User $auth): bool
    {
        return in_array($auth->role, [UserRole::Student, UserRole::Coach, UserRole::Admin], true);
    }

    public function view(User $auth, QaThread $thread): bool
    {
        return match($auth->role) {
            UserRole::Student => $thread->certification->status === CertificationStatus::Published,
            UserRole::Coach => $thread->certification->status === CertificationStatus::Published
                && $thread->certification->coaches->contains('id', $auth->id),
            UserRole::Admin => true,
            default => false,
        };
    }

    public function create(User $auth): bool
    {
        return $auth->role == UserRole::Student;
    }

    public function update(User $auth, QaThread $thread): bool
    {
        return $thread->user_id === $auth->id;
    }

    public function delete(User $auth, QaThread $thread): bool
    {
        return match($auth->role) {
            UserRole::Admin => true,
            default => $thread->user_id === $auth->id && $thread->replies->count() == 0,
        };
    }

    public function resolve(User $auth, QaThread $thread): bool
    {
        return $thread->user_id === $auth->id;
    }

    public function unresolve(User $auth, QaThread $thread): bool
    {
        return $thread->user_id === $auth->id;
    }
}
