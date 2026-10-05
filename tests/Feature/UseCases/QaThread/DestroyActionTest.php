<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\QaThread;

use App\Models\QaReply;
use App\Models\QaThread;
use App\UseCases\QaThread\DestroyAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestroyActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_スレッドを削除する(): void
    {
        $thread = QaThread::factory()->create();

        app(DestroyAction::class)($thread);

        $this->assertDatabaseMissing('qa_threads', ['id' => $thread->id]);
    }

    public function test_紐付く回答も削除される(): void
    {
        $thread = QaThread::factory()->create();
        $reply = QaReply::factory()->forThread($thread)->create();

        app(DestroyAction::class)($thread);

        $this->assertDatabaseMissing('qa_replies', ['id' => $reply->id]);
    }

    public function test_他のスレッドには影響しない(): void
    {
        $thread = QaThread::factory()->create();
        $other = QaThread::factory()->create();
        $otherReply = QaReply::factory()->forThread($other)->create();

        app(DestroyAction::class)($thread);

        $this->assertDatabaseHas('qa_threads', ['id' => $other->id]);
        $this->assertDatabaseHas('qa_replies', ['id' => $otherReply->id]);
    }
}
