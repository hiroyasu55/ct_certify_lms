<?php

declare(strict_types=1);

namespace App\UseCases\QaThread;

use App\Models\QaThread;
use Illuminate\Support\Facades\DB;

/**
 * 質問スレッドを削除するユースケース。紐付く回答は FK の cascade で一緒に削除される。
 */
final class DestroyAction
{
    public function __invoke(QaThread $thread): void
    {
        DB::transaction(fn () => $thread->delete());
    }
}
