<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;

/**
 * 質問スレッドへの回答の認可ルール。
 *
 * 回答投稿は受講生 / コーチ(admin は不可)、編集・削除は回答者本人のみ許可する。
 */
class QaReplyPolicy
{
    public function create(User $auth, QaThread $thread): bool
    {
        return in_array($auth->role, [UserRole::Student, UserRole::Coach], true);
    }

    public function update(User $auth, QaReply $reply): bool
    {
        return $reply->user_id === $auth->id;
    }

    public function delete(User $auth, QaReply $reply): bool
    {
        return $reply->user_id === $auth->id;
    }
}
