<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\QaReply;

use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use App\UseCases\QaReply\UpdateAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_本文を更新する(): void
    {
        $reply = QaReply::factory()->create();

        $result = app(UpdateAction::class)($reply, ['body' => '更新後本文']);

        $this->assertDatabaseHas('qa_replies', [
            'id' => $reply->id,
            'body' => '更新後本文',
        ]);

        // 更新後の回答を返す
        $this->assertTrue($result->is($reply));
        $this->assertSame('更新後本文', $result->body);
    }

    public function test_スレッドと投稿者は変更しない(): void
    {
        $reply = QaReply::factory()->create();
        $originalThreadId = $reply->qa_thread_id;
        $originalUserId = $reply->user_id;

        app(UpdateAction::class)($reply, [
            'body' => '更新後本文',
            'qa_thread_id' => QaThread::factory()->create()->id,
            'user_id' => User::factory()->coach()->create()->id,
        ]);

        $fresh = $reply->fresh();
        $this->assertSame($originalThreadId, $fresh->qa_thread_id);
        $this->assertSame($originalUserId, $fresh->user_id);
    }
}
