<?php

declare(strict_types=1);

namespace Tests\Feature\Http\QaReply;

use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_投稿主は回答を削除できる(): void
    {
        $reply = QaReply::factory()->create();

        $response = $this->actingAs($reply->user)
            ->delete(route('qa-board.replies.destroy', ['thread' => $reply->qa_thread_id, 'reply' => $reply->id]));

        $response->assertRedirect(route('qa-board.show', $reply->qa_thread_id));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('qa_replies', ['id' => $reply->id]);
    }

    public function test_投稿主以外は回答を削除できない(): void
    {
        $other = User::factory()->coach()->create();
        $reply = QaReply::factory()->create();

        $response = $this->actingAs($other)
            ->delete(route('qa-board.replies.destroy', ['thread' => $reply->qa_thread_id, 'reply' => $reply->id]));

        $response->assertForbidden();
        $this->assertDatabaseHas('qa_replies', ['id' => $reply->id]);
    }

    public function test_元スレッドが異なる場合404エラー(): void
    {
        $reply = QaReply::factory()->create();
        $otherThread = QaThread::factory()->create();

        $response = $this->actingAs($reply->user)
            ->delete(route('qa-board.replies.destroy', ['thread' => $otherThread->id, 'reply' => $reply->id]));

        $response->assertNotFound();
        $this->assertDatabaseHas('qa_replies', ['id' => $reply->id]);
    }
}
