<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\QaReply;

use App\Models\QaThread;
use App\Models\User;
use App\UseCases\QaReply\StoreAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_コーチは回答を投稿できる(): void
    {
        $coach = User::factory()->coach()->create();
        $thread = QaThread::factory()->create();

        $reply = app(StoreAction::class)($coach, $thread, ['body' => '回答本文']);

        $this->assertDatabaseHas('qa_replies', [
            'id' => $reply->id,
            'qa_thread_id' => $thread->id,
            'user_id' => $coach->id,
            'body' => '回答本文',
        ]);
    }

    public function test_受講生は回答を投稿できる(): void
    {
        $student = User::factory()->student()->create();
        $thread = QaThread::factory()->create();

        $reply = app(StoreAction::class)($student, $thread, ['body' => '回答本文']);

        $this->assertDatabaseHas('qa_replies', [
            'id' => $reply->id,
            'qa_thread_id' => $thread->id,
            'user_id' => $student->id,
        ]);
    }
}
