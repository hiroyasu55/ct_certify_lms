<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\QaReply;

use App\Models\QaReply;
use App\Models\QaThread;
use App\UseCases\QaReply\DestroyAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestroyActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_回答を削除する(): void
    {
        $thread = QaThread::factory()->create();
        $reply = QaReply::factory()->forThread($thread)->create();
        $otherReply = QaReply::factory()->forThread($thread)->create();

        app(DestroyAction::class)($reply);

        $this->assertDatabaseMissing('qa_replies', ['id' => $reply->id]);

        // 親の投稿スレッドは削除されない
        $this->assertDatabaseHas('qa_threads', ['id' => $thread->id]);

        // 他の回答は削除されない
        $this->assertDatabaseHas('qa_replies', ['id' => $otherReply->id]);
    }
}
