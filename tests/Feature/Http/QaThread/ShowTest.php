<?php

declare(strict_types=1);

namespace Tests\Feature\Http\QaThread;

use App\Models\Certification;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ContentTestHelpers;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use ContentTestHelpers, RefreshDatabase;

    /**
     * 資格にコーチをアサインする
     */
    public function test_受講生は公開中の投稿を閲覧できる(): void
    {
        $student = User::factory()->student()->create();
        $cert = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($cert)->create();

        $response = $this->actingAs($student)->get(route('qa-board.show', $thread));

        $response->assertOk();
        $response->assertSee($thread->title);
    }

    public function test_受講生はアーカイブされた資格の投稿を閲覧できない(): void
    {
        $student = User::factory()->student()->create();
        $cert = Certification::factory()->archived()->create();
        $thread = QaThread::factory()->for($cert)->create();

        $response = $this->actingAs($student)->get(route('qa-board.show', $thread));

        $response->assertForbidden();
    }

    public function test_コーチは公開中かつ担当資格の投稿を閲覧できる(): void
    {
        $coach = User::factory()->coach()->create();
        $cert = Certification::factory()->published()->create();
        $this->assignCoach($coach, $cert);
        $thread = QaThread::factory()->for($cert)->create();

        $response = $this->actingAs($coach)->get(route('qa-board.show', $thread));

        $response->assertOk();
        $response->assertSee($thread->title);
    }

    public function test_コーチはアーカイブされた資格の投稿を閲覧できない(): void
    {
        $student = User::factory()->coach()->create();
        $cert = Certification::factory()->archived()->create();
        $thread = QaThread::factory()->for($cert)->create();

        $response = $this->actingAs($student)->get(route('qa-board.show', $thread));

        $response->assertForbidden();
    }

    public function test_コーチは担当でない資格の投稿を閲覧できない(): void
    {
        $coach = User::factory()->coach()->create();
        $cert = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($cert)->create();

        $response = $this->actingAs($coach)->get(route('qa-board.show', $thread));

        $response->assertForbidden();
    }

    public function test_管理者は投稿を閲覧できない(): void
    {
        $admin = User::factory()->admin()->create();
        $thread = QaThread::factory()->create();

        $response = $this->actingAs($admin)->get(route('qa-board.show', $thread));

        $response->assertForbidden();
    }
}
