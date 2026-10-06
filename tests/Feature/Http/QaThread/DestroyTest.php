<?php

declare(strict_types=1);

namespace Tests\Feature\Http\QaThread;

use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_投稿主は回答が無い投稿を削除できる(): void
    {
        $thread = QaThread::factory()->create();

        $response = $this->actingAs($thread->user)->delete(route('qa-board.destroy', $thread));

        $response->assertRedirect(route('qa-board.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('qa_threads', ['id' => $thread->id]);
    }

    public function test_投稿主以外は投稿を削除できない(): void
    {
        $other = User::factory()->student()->create();
        $thread = QaThread::factory()->create();

        $response = $this->actingAs($other)->delete(route('qa-board.destroy', $thread));

        $response->assertForbidden();
        $this->assertDatabaseHas('qa_threads', ['id' => $thread->id]);
    }

    public function test_回答が存在する投稿は削除できない(): void
    {
        $thread = QaThread::factory()->create();
        $reply = QaReply::factory()->forThread($thread)->create();

        $response = $this->actingAs($thread->user)->delete(route('qa-board.destroy', $thread));

        $response->assertForbidden();
        $this->assertDatabaseHas('qa_threads', ['id' => $thread->id]);
        $this->assertDatabaseHas('qa_replies', ['id' => $reply->id]);
    }
}
