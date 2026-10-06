<?php

declare(strict_types=1);

namespace Tests\Feature\Http\QaReply;

use App\Models\Certification;
use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ContentTestHelpers;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use ContentTestHelpers, RefreshDatabase;

    public function test_コーチは担当する資格の質問への回答を投稿できる(): void
    {
        $coach = User::factory()->coach()->create();
        $cert = Certification::factory()->published()->create();
        $this->assignCoach($coach, $cert);
        $thread = QaThread::factory()->for($cert)->create();

        $response = $this->actingAs($coach)->post(route('qa-board.replies.store', $thread), [
            'body' => 'コーチからの回答',
        ]);

        $reply = QaReply::query()->sole();
        $response->assertRedirect(route('qa-board.show', $thread).'#reply-'.$reply->id);
        $response->assertSessionHas('success');
        $this->assertSame($thread->id, $reply->qa_thread_id);
        $this->assertSame($coach->id, $reply->user_id);
        $this->assertSame('コーチからの回答', $reply->body);
    }

    public function test_コーチは担当でない資格の質問への回答は投稿できない(): void
    {
        $coach = User::factory()->coach()->create();
        $thread = QaThread::factory()->create();

        $response = $this->actingAs($coach)->post(route('qa-board.replies.store', $thread), [
            'body' => 'コーチからの回答',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('qa_replies', 0);
    }

    public function test_受講生は回答を投稿できる(): void
    {
        $student = User::factory()->student()->create();
        $thread = QaThread::factory()->create();

        $response = $this->actingAs($student)->post(route('qa-board.replies.store', $thread), [
            'body' => '受講生からの回答',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('qa_replies', ['qa_thread_id' => $thread->id, 'user_id' => $student->id]);
    }

    public function test_管理者は回答を投稿できない(): void
    {
        $admin = User::factory()->admin()->create();
        $thread = QaThread::factory()->create();

        $response = $this->actingAs($admin)->post(route('qa-board.replies.store', $thread), [
            'body' => '管理者からの回答',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('qa_replies', 0);
    }

    public function test_回答のバリデーション(): void
    {
        $student = User::factory()->student()->create();
        $thread = QaThread::factory()->create();

        $this->actingAs($student)
            ->post(route('qa-board.replies.store', $thread), [])
            ->assertSessionHasErrors('body');

        $this->actingAs($student)
            ->post(route('qa-board.replies.store', $thread), ['body' => str_repeat('あ', 5001)])
            ->assertSessionHasErrors('body');

        $this->assertDatabaseCount('qa_replies', 0);
    }
}
