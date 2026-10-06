<?php

declare(strict_types=1);

namespace App\UseCases\QaThread;

use App\Enums\UserRole;
use App\Exceptions\QaThread\QaThreadHasRepliesException;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * 質問スレッドを削除するユースケース。紐付く回答は FK の cascade で一緒に削除される。
 */
final class DestroyAction
{
    public function __invoke(QaThread $thread, ?User $user = null): void
    {
        // 管理者以外で回答の付いている質問を削除しようとする場合例外発出
        if ($user?->role !== UserRole::Admin && $thread->replies()->exists()) {
            throw new QaThreadHasRepliesException;
        }

        DB::transaction(fn () => $thread->delete());
    }
}
