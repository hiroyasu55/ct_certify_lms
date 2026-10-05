<?php

declare(strict_types=1);

namespace Tests\Feature\Http\QaThread;

use App\Models\QaThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_投稿主は投稿編集画面を表示できる(): void
    {
        $thread = QaThread::factory()->create();

        $response = $this->actingAs($thread->user)->get(route('qa-board.edit', $thread));

        $response->assertOk();
    }

    public function test_投稿主でない受講生は投稿編集画面を表示できない(): void
    {
        $thread = QaThread::factory()->create();
        $other = User::factory()->student()->create();

        $response = $this->actingAs($other)->get(route('qa-board.edit', $thread));

        $response->assertForbidden();
    }

    public function test_投稿主は投稿を更新できる(): void
    {
        $thread = QaThread::factory()->create();

        $response = $this->actingAs($thread->user)->patch(route('qa-board.update', $thread), [
            'title' => '更新後タイトル',
            'body' => '更新後本文',
        ]);

        $response->assertRedirect(route('qa-board.show', $thread));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('qa_threads', [
            'id' => $thread->id,
            'title' => '更新後タイトル',
            'body' => '更新後本文',
        ]);
    }

    public function test_投稿主でない受講生は投稿を更新できない(): void
    {
        $other = User::factory()->student()->create();
        $thread = QaThread::factory()->create(['title' => '元のタイトル']);

        $response = $this->actingAs($other)->patch(route('qa-board.update', $thread), [
            'title' => '書き換え',
            'body' => '書き換え',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('qa_threads', ['id' => $thread->id, 'title' => '元のタイトル']);
    }
}
