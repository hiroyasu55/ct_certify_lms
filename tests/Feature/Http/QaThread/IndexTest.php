<?php

declare(strict_types=1);

namespace Tests\Feature\Http\QaThread;

use App\Enums\QaThreadStatus;
use App\Models\Certification;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_受講生はスレッド一覧を閲覧できる(): void
    {
        $student = User::factory()->student()->create();
        $thread = QaThread::factory()->create(['title' => '一覧に出る質問']);

        $response = $this->actingAs($student)->get(route('qa-board.index'));

        $response->assertOk();
        $response->assertSee($thread->title);
    }

    public function test_コーチはスレッド一覧を閲覧できる(): void
    {
        $coach = User::factory()->coach()->create();

        $response = $this->actingAs($coach)->get(route('qa-board.index'));

        $response->assertOk();
    }

    public function test_管理者はスレッド一覧を閲覧できない(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('qa-board.index'));

        $response->assertForbidden();
    }
}
