<?php

declare(strict_types=1);

namespace App\UseCases\QaThread;

use App\Enums\QaThreadStatus;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * 受講生が質問スレッドを新規投稿するユースケース。投稿直後は未解決(open)状態。
 */
final class StoreAction
{
    /**
     * @param array{certification_id: string, title: string, body: string} $validated
     */
    public function __invoke(User $user, array $validated): QaThread
    {
        return DB::transaction(fn () => QaThread::create([
            'certification_id' => $validated['certification_id'],
            'user_id' => $user->id,
            'title' => $validated['title'],
            'body' => $validated['body'],
            'status' => QaThreadStatus::Unresolved,
        ]));
    }
}
