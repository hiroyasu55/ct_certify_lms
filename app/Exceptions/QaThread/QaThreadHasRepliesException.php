<?php

declare(strict_types=1);

namespace App\Exceptions\QaThread;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * 投稿者削除時の「回答あり」状態ガード
 *
 * 409 を返し、フラッシュメッセージ「回答が付いているスレッドは削除できません」を表示する。
 */
final class QaThreadHasRepliesException extends HttpException
{
    public function __construct(?string $message = null, ?\Throwable $previous = null)
    {
        parent::__construct(
            statusCode: 409,
            message: $message ?? '回答が付いているスレッドは削除できません。',
            previous: $previous,
        );
    }
}
