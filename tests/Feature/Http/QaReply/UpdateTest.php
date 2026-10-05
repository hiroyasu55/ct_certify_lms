<?php

declare(strict_types=1);

namespace Tests\Feature\Http\QaReply;

use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_投稿主は回答編集画面を表示できる(): void
    {
        $reply = QaReply::factory()->create();

        $response = $this->actingAs($reply->user)
            ->get(route('qa-board.replies.edit', ['thread' => $reply->qa_thread_id, 'reply' => $reply->id]));

        $response->assertOk();
        $response->assertSee($reply->body);
    }

    public function test_投稿主以外は回答編集画面を表示できない(): void
    {
        $other = User::factory()->coach()->create();
        $reply = QaReply::factory()->create();

        $response = $this->actingAs($other)
            ->get(route('qa-board.replies.edit', ['thread' => $reply->qa_thread_id, 'reply' => $reply->id]));

        $response->assertForbidden();
    }

    public function test_投稿主は回答を更新できる(): void
    {
        $reply = QaReply::factory()->create();

        $response = $this->actingAs($reply->user)
            ->patch(route('qa-board.replies.update', ['thread' => $reply->qa_thread_id, 'reply' => $reply->id]), [
                'body' => '更新後の回答',
            ]);

        $response->assertRedirect(route('qa-board.show', $reply->qa_thread_id).'#reply-'.$reply->id);
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('qa_replies', ['id' => $reply->id, 'body' => '更新後の回答']);
    }

    public function test_投稿主以外は回答を更新できない(): void
    {
        $other = User::factory()->coach()->create();
        $reply = QaReply::factory()->create(['body' => '元の回答']);

        $response = $this->actingAs($other)
            ->patch(route('qa-board.replies.update', ['thread' => $reply->qa_thread_id, 'reply' => $reply->id]), [
                'body' => '書き換え',
            ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('qa_replies', ['id' => $reply->id, 'body' => '元の回答']);
    }

    public function test_元スレッドが異なる場合404エラー(): void
    {
        $reply = QaReply::factory()->create(['body' => '元の回答']);
        $otherThread = QaThread::factory()->create();

        $response = $this->actingAs($reply->user)
            ->patch(route('qa-board.replies.update', ['thread' => $otherThread->id, 'reply' => $reply->id]), [
                'body' => '書き換え',
            ]);

        $response->assertNotFound();
        $this->assertDatabaseHas('qa_replies', ['id' => $reply->id, 'body' => '元の回答']);
    }

    public function test_回答編集のバリデーション(): void
    {
        $reply = QaReply::factory()->create(['body' => '元の回答']);

        $this->actingAs($reply->user)
            ->patch(route('qa-board.replies.update', ['thread' => $reply->qa_thread_id, 'reply' => $reply->id]), [
                'body' => '',
            ])
            ->assertSessionHasErrors('body');
        $this->assertDatabaseHas('qa_replies', ['id' => $reply->id, 'body' => '元の回答']);
        
        $this->actingAs($reply->user)
            ->patch(route('qa-board.replies.update', ['thread' => $reply->qa_thread_id, 'reply' => $reply->id]), [
                'body' => str_repeat('あ', 5001),
            ])
            ->assertSessionHasErrors('body');
        $this->assertDatabaseHas('qa_replies', ['id' => $reply->id, 'body' => '元の回答']);
    }
}
