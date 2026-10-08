<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\QaThread;

use App\Exceptions\QaThread\QaThreadHasRepliesException;
use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use App\UseCases\QaThread\DestroyAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestroyActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_受講生が回答のないスレッドを削除する(): void
    {
        $student = User::factory()->student()->create();
        $thread = QaThread::factory()->create();

        app(DestroyAction::class)($student, $thread);

        $this->assertDatabaseMissing('qa_threads', ['id' => $thread->id]);
    }

    public function test_回答のあるスレッドを受講者が削除すると例外(): void
    {
        $student = User::factory()->student()->create();
        $thread = QaThread::factory()->create();
        QaReply::factory()->forThread($thread)->create();

        $this->expectException(QaThreadHasRepliesException::class);

        app(DestroyAction::class)($student, $thread);
    }

    public function test_回答のあるスレッドを管理者は削除可能(): void
    {
        $thread = QaThread::factory()->create();
        $reply = QaReply::factory()->forThread($thread)->create();
        $admin = User::factory()->admin()->create();

        app(DestroyAction::class)($admin, $thread);

        $this->assertDatabaseMissing('qa_threads', ['id' => $thread->id]);
        // 回答も削除
        $this->assertDatabaseMissing('qa_replies', ['id' => $reply->id]);
    }

    public function test_他のスレッドには影響しない(): void
    {
        $student = User::factory()->student()->create();
        $thread = QaThread::factory()->create();
        $other = QaThread::factory()->create();
        $otherReply = QaReply::factory()->forThread($other)->create();

        app(DestroyAction::class)($student, $thread);

        $this->assertDatabaseHas('qa_threads', ['id' => $other->id]);
        $this->assertDatabaseHas('qa_replies', ['id' => $otherReply->id]);
    }
}
