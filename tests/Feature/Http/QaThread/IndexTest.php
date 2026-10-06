<?php

declare(strict_types=1);

namespace Tests\Feature\Http\QaThread;

use App\Models\Certification;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ContentTestHelpers;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use ContentTestHelpers, RefreshDatabase;

    public function test_受講生はスレッド一覧を閲覧できる(): void
    {
        $student = User::factory()->student()->create();
        $thread = QaThread::factory()->create(['title' => '一覧に出る質問']);

        $response = $this->actingAs($student)->get(route('qa-board.index'));

        $response->assertOk();
        $response->assertSee($thread->title);
    }

    public function test_コーチは担当資格のスレッド一覧を閲覧できる(): void
    {
        $coach = User::factory()->coach()->create();

        $cert = Certification::factory()->published()->create();
        $this->assignCoach($coach, $cert);
        $otherCert = Certification::factory()->published()->create();

        $targetThread = QaThread::factory()->for($cert)->create(['title' => '担当資格の質問']);
        $otherThread = QaThread::factory()->for($otherCert)->create(['title' => '担当外資格の質問']);

        $response = $this->actingAs($coach)->get(route('qa-board.index'));

        $response->assertOk();
        $response->assertSee($targetThread->title);
        $response->assertDontSee($otherThread->title);
    }

    public function test_管理者はスレッド一覧を閲覧できない(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('qa-board.index'));

        $response->assertForbidden();
    }

    public function test_受講生は公開中の資格で検索できる(): void
    {
        $student = User::factory()->student()->create();
        $publishedCert = Certification::factory()->published()->create();
        $archivedCert = Certification::factory()->archived()->create();

        $response = $this->actingAs($student)->get(route('qa-board.index'));

        $response->assertViewHas(
            'certifications',
            fn ($certs) => $certs->pluck('id')->contains($publishedCert->id)
                && ! $certs->pluck('id')->contains($archivedCert->id)
        );
    }

    public function test_コーチは公開中かつ自身が担当の資格で検索できる(): void
    {
        $coach = User::factory()->coach()->create();
        $myCert = Certification::factory()->published()->create();
        $this->assignCoach($coach, $myCert);
        $otherCert = Certification::factory()->published()->create();
        $archivedCert = Certification::factory()->archived()->create();

        $response = $this->actingAs($coach)->get(route('qa-board.index'));

        $response->assertViewHas(
            'certifications',
            fn ($certs) => $certs->pluck('id')->contains($myCert->id)
                && ! $certs->pluck('id')->contains($otherCert->id)
                && ! $certs->pluck('id')->contains($archivedCert->id)
        );
    }

    public function test_検索のバリデーション正常(): void
    {
        $student = User::factory()->student()->create();
        $cert = Certification::factory()->create();

        $response = $this->actingAs($student)->get(route('qa-board.index', [
            'certification_id' => $cert->id,
            'status' => 'unresolved',
            'keyword' => 'あいう',
        ]));

        $response->assertSessionHasNoErrors();

        $response = $this->actingAs($student)->get(route('qa-board.index', [
            'status' => 'resolved',
        ]));

        $response->assertSessionHasNoErrors();
    }

    public function test_資格検索のバリデーションエラー(): void
    {
        $student = User::factory()->student()->create();
        Certification::factory()->create();

        // 存在しない資格ID
        $response = $this->actingAs($student)->get(route('qa-board.index', ['certification_id' => 'X']));

        $response->assertSessionHasErrors(['certification_id']);
    }

    public function test_ステータス検索のバリデーションエラー(): void
    {
        $student = User::factory()->student()->create();

        // 不正なステータス
        $response = $this->actingAs($student)->get(route('qa-board.index', ['status' => 'rresolved']));

        $response->assertSessionHasErrors(['status']);
    }
}
