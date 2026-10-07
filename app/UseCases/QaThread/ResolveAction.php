<?php

declare(strict_types=1);

namespace App\UseCases\QaThread;

use App\Enums\QaThreadStatus;
use App\Models\QaThread;
use Illuminate\Support\Facades\DB;

/**
 * 質問スレッドを解決済にする
 */
final class ResolveAction
{
    /**
     * @param QaThread $thread
     */
    public function __invoke(QaThread $thread): QaThread
    {
        // 既に解決済の場合は解決日時を上書きしない
        if ($thread->status !== QaThreadStatus::Resolved) {
            DB::transaction(fn () => $thread->update([
                'status' => QaThreadStatus::Resolved,
                'resolved_at' => now(),
            ]));
        }

        return $thread;
    }
}
