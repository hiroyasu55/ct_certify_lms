<?php

declare(strict_types=1);

namespace App\UseCases\QaReply;

use App\Models\QaReply;
use Illuminate\Support\Facades\DB;

/**
 * 質問スレッドへの回答本文を更新するユースケース。
 */
final class UpdateAction
{
    /**
     * @param array{body: string} $validated
     */
    public function __invoke(QaReply $reply, array $validated): QaReply
    {
        DB::transaction(fn () => $reply->update([
            'body' => $validated['body'],
        ]));

        return $reply;
    }
}
