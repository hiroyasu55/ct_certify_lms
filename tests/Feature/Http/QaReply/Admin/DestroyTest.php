<?php

declare(strict_types=1);

namespace Tests\Feature\Http\QaReply\Admin;

use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_管理者は回答を削除できる(): void
    {
        $admin = User::factory()->admin()->create();
        $reply = QaReply::factory()->create();

        $response = $this->actingAs($admin)
            ->delete(route('admin.qa-board.replies.destroy', ['thread' => $reply->qa_thread_id, 'reply' => $reply->id]));

        $response->assertRedirect(route('admin.qa-board.show', $reply->qa_thread_id));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('qa_replies', ['id' => $reply->id]);
    }

    public function test_管理者はアーカイブされた資格の回答を削除できる(): void
    {
        $admin = User::factory()->admin()->create();
        $reply = QaReply::factory()->create();

        $response = $this->actingAs($admin)
            ->delete(route('admin.qa-board.replies.destroy', ['thread' => $reply->qa_thread_id, 'reply' => $reply->id]));

        $response->assertRedirect(route('admin.qa-board.show', $reply->qa_thread_id));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('qa_replies', ['id' => $reply->id]);
    }

    public function test_受講者は自身の投稿でも回答を削除できない(): void
    {
        $student = User::factory()->student()->create();
        $reply = QaReply::factory()->for($student)->create();

        $response = $this->actingAs($student)
            ->delete(route('admin.qa-board.replies.destroy', ['thread' => $reply->qa_thread_id, 'reply' => $reply->id]));

        $response->assertForbidden();
        $this->assertDatabaseHas('qa_replies', ['id' => $reply->id]);
    }

    public function test_コーチは自身の投稿でも回答を削除できない(): void
    {
        $coach = User::factory()->coach()->create();
        $reply = QaReply::factory()->for($coach)->create();

        $response = $this->actingAs($coach)
            ->delete(route('admin.qa-board.replies.destroy', ['thread' => $reply->qa_thread_id, 'reply' => $reply->id]));

        $response->assertForbidden();
        $this->assertDatabaseHas('qa_replies', ['id' => $reply->id]);
    }

    public function test_元スレッドが異なる場合404エラー(): void
    {
        $admin = User::factory()->admin()->create();
        $reply = QaReply::factory()->create();
        $otherThread = QaThread::factory()->create();

        $response = $this->actingAs($admin)
            ->delete(route('admin.qa-board.replies.destroy', ['thread' => $otherThread->id, 'reply' => $reply->id]));

        $response->assertNotFound();
        $this->assertDatabaseHas('qa_replies', ['id' => $reply->id]);
    }
}
