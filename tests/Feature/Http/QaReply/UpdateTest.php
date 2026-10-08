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

class UpdateTest extends TestCase
{
    use ContentTestHelpers, RefreshDatabase;

    public function test_受講者は自身の投稿した回答の編集画面を表示できる(): void
    {
        $student = User::factory()->student()->create();
        $reply = QaReply::factory()->for($student)->create();

        $response = $this->actingAs($student)
            ->get(route('qa-board.replies.edit', ['thread' => $reply->qa_thread_id, 'reply' => $reply->id]));

        $response->assertOk();
        $response->assertSee($reply->body);
    }

    public function test_受講者は自身の投稿でない回答の編集画面を表示できない(): void
    {
        $student = User::factory()->student()->create();
        $reply = QaReply::factory()->create();

        $response = $this->actingAs($student)
            ->get(route('qa-board.replies.edit', ['thread' => $reply->qa_thread_id, 'reply' => $reply->id]));

        $response->assertForbidden();
    }

    public function test_受講者はアーカイブ済資格の回答の編集画面で404エラー(): void
    {
        $student = User::factory()->student()->create();
        $cert = Certification::factory()->archived()->create();
        $thread = QaThread::factory()->for($cert)->create();
        $reply = QaReply::factory()->forThread($thread)->create();

        $response = $this->actingAs($student)
            ->get(route('qa-board.replies.edit', ['thread' => $reply->qa_thread_id, 'reply' => $reply->id]));

        $response->assertNotFound();
    }

    public function test_コーチは自身の投稿した回答の編集画面を表示できる(): void
    {
        $coach = User::factory()->coach()->create();
        $cert = Certification::factory()->published()->create();
        $this->assignCoach($coach, $cert);
        $thread = QaThread::factory()->for($cert)->create();
        $reply = QaReply::factory()->forThread($thread)->for($coach)->create();

        $response = $this->actingAs($coach)
            ->get(route('qa-board.replies.edit', ['thread' => $reply->qa_thread_id, 'reply' => $reply->id]));

        $response->assertOk();
        $response->assertSee($reply->body);
    }

    public function test_コーチは自身の投稿でない回答編集画面を表示できない(): void
    {
        $coach = User::factory()->coach()->create();
        $cert = Certification::factory()->published()->create();
        $this->assignCoach($coach, $cert);
        $thread = QaThread::factory()->for($cert)->create();
        $reply = QaReply::factory()->forThread($thread)->create();

        $response = $this->actingAs($coach)
            ->get(route('qa-board.replies.edit', ['thread' => $reply->qa_thread_id, 'reply' => $reply->id]));

        $response->assertForbidden();
    }

    public function test_コーチはアーカイブ済資格の回答の編集画面で404エラー(): void
    {
        $coach = User::factory()->coach()->create();
        $cert = Certification::factory()->archived()->create();
        $this->assignCoach($coach, $cert);
        $thread = QaThread::factory()->for($cert)->create();
        $reply = QaReply::factory()->forThread($thread)->for($coach)->create();

        $response = $this->actingAs($coach)
            ->get(route('qa-board.replies.edit', ['thread' => $reply->qa_thread_id, 'reply' => $reply->id]));

        $response->assertNotFound();
    }

    public function test_コーチは担当資格でない回答の編集画面を表示できない(): void
    {
        $coach = User::factory()->coach()->create();
        $cert = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($cert)->create();
        $reply = QaReply::factory()->forThread($thread)->for($coach)->create();

        $response = $this->actingAs($coach)
            ->get(route('qa-board.replies.edit', ['thread' => $reply->qa_thread_id, 'reply' => $reply->id]));

        $response->assertForbidden();
    }

    public function test_受講者は自身の投稿した回答を更新できる(): void
    {
        $student = User::factory()->student()->create();
        $reply = QaReply::factory()->for($student)->create(['body' => '元の回答']);

        $response = $this->actingAs($student)
            ->patch(route('qa-board.replies.update', ['thread' => $reply->qa_thread_id, 'reply' => $reply->id]), [
                'body' => '更新後の回答',
            ]);

        $response->assertRedirect(route('qa-board.show', $reply->qa_thread_id).'#reply-'.$reply->id);
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('qa_replies', ['id' => $reply->id, 'body' => '更新後の回答']);
    }

    public function test_受講者は自身の投稿でない回答を更新できない(): void
    {
        $student = User::factory()->student()->create();
        $reply = QaReply::factory()->create(['body' => '元の回答']);

        $response = $this->actingAs($student)
            ->patch(route('qa-board.replies.update', ['thread' => $reply->qa_thread_id, 'reply' => $reply->id]), [
                'body' => '書き換え',
            ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('qa_replies', ['id' => $reply->id, 'body' => '元の回答']);
    }

    public function test_受講者はアーカイブ済資格の回答更新時に404エラー(): void
    {
        $cert = Certification::factory()->archived()->create();
        $thread = QaThread::factory()->for($cert)->create();
        $reply = QaReply::factory()->forThread($thread)->create(['body' => '元の回答']);

        $response = $this->actingAs($reply->user)
            ->patch(route('qa-board.replies.update', ['thread' => $reply->qa_thread_id, 'reply' => $reply->id]), [
                'body' => '書き換え',
            ]);

        $response->assertNotFound();
        $this->assertDatabaseHas('qa_replies', ['id' => $reply->id, 'body' => '元の回答']);
    }

    public function test_コーチは自身の投稿した回答を更新できる(): void
    {
        $coach = User::factory()->coach()->create();
        $cert = Certification::factory()->published()->create();
        $this->assignCoach($coach, $cert);
        $thread = QaThread::factory()->for($cert)->create();
        $reply = QaReply::factory()->forThread($thread)->for($coach)->create(['body' => '元の回答']);

        $response = $this->actingAs($coach)
            ->patch(route('qa-board.replies.update', ['thread' => $reply->qa_thread_id, 'reply' => $reply->id]), [
                'body' => '更新後の回答',
            ]);

        $response->assertRedirect(route('qa-board.show', $reply->qa_thread_id).'#reply-'.$reply->id);
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('qa_replies', ['id' => $reply->id, 'body' => '更新後の回答']);
    }

    public function test_コーチはアーカイブ済資格の回答を更新時に404エラー(): void
    {
        $coach = User::factory()->coach()->create();
        $cert = Certification::factory()->archived()->create();
        $this->assignCoach($coach, $cert);
        $thread = QaThread::factory()->for($cert)->create();
        $reply = QaReply::factory()->forThread($thread)->for($coach)->create(['body' => '元の回答']);

        $response = $this->actingAs($coach)
            ->patch(route('qa-board.replies.update', ['thread' => $reply->qa_thread_id, 'reply' => $reply->id]), [
                'body' => '更新後の回答',
            ]);

        $response->assertNotFound();
        $this->assertDatabaseHas('qa_replies', ['id' => $reply->id, 'body' => '元の回答']);
    }

    public function test_コーチは担当資格でない回答を更新できない(): void
    {
        $coach = User::factory()->coach()->create();
        $cert = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($cert)->create();
        $reply = QaReply::factory()->forThread($thread)->for($coach)->create(['body' => '元の回答']);

        $response = $this->actingAs($coach)
            ->patch(route('qa-board.replies.update', ['thread' => $reply->qa_thread_id, 'reply' => $reply->id]), [
                'body' => '更新後の回答',
            ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('qa_replies', ['id' => $reply->id, 'body' => '元の回答']);
    }

    public function test_元スレッドが異なる場合404エラー(): void
    {
        $student = User::factory()->student()->create();
        $reply = QaReply::factory()->create(['body' => '元の回答']);
        $otherThread = QaThread::factory()->create();

        $response = $this->actingAs($student)
            ->patch(route('qa-board.replies.update', ['thread' => $otherThread->id, 'reply' => $reply->id]), [
                'body' => '書き換え',
            ]);

        $response->assertNotFound();
        $this->assertDatabaseHas('qa_replies', ['id' => $reply->id, 'body' => '元の回答']);
    }

    public function test_回答編集のバリデーション(): void
    {
        $student = User::factory()->student()->create();
        $reply = QaReply::factory()->for($student)->create(['body' => '元の回答']);

        $this->actingAs($student)
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
