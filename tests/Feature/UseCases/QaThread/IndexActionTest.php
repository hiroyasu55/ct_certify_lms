<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\QaThread;

use App\Models\Certification;
use App\Models\QaThread;
use App\Models\User;
use App\UseCases\QaThread\IndexAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ContentTestHelpers;
use Tests\TestCase;

class IndexActionTest extends TestCase
{
    use ContentTestHelpers, RefreshDatabase;

    public function test_受講生は公開済の全ての資格の質問を閲覧可能(): void
    {
        $student = User::factory()->student()->create();

        $second = QaThread::factory()
            ->for(Certification::factory()->published())
            ->create(['created_at' => now()->subDays(1)]);
        $third = QaThread::factory()
            ->for(Certification::factory()->published())
            ->create(['created_at' => now()->subDays(2)]);
        $first = QaThread::factory()
            ->for(Certification::factory()->published())
            ->create(['created_at' => now()]);

        $result = app(IndexAction::class)($student);

        $this->assertSame(
            [$first->id, $second->id, $third->id],
            $result->pluck('id')->all(),
        );
    }

    public function test_非公開の資格の質問は除外(): void
    {
        $student = User::factory()->student()->create();

        // アーカイブされた資格
        QaThread::factory()
            ->for(Certification::factory()->archived())
            ->create();

        $result = app(IndexAction::class)($student);

        $this->assertCount(0, $result);
    }

    public function test_ページネーションで20件(): void
    {
        $student = User::factory()->student()->create();
        QaThread::factory()
            ->for(Certification::factory()->published())
            ->count(21)->create();

        $result = app(IndexAction::class)($student);

        $this->assertSame(20, $result->perPage());
        $this->assertCount(20, $result->items());
        $this->assertSame(21, $result->total());
    }

    public function test_資格で検索(): void
    {
        $student = User::factory()->student()->create();
        $cert = Certification::factory()->published()->create();
        $otherCert = Certification::factory()->published()->create();

        $target = QaThread::factory()
            ->for($cert)
            ->create();
        QaThread::factory()
            ->for($otherCert)
            ->create();

        $result = app(IndexAction::class)($student, ['certification_id' => $cert->id]);

        $this->assertSame([$target->id], $result->pluck('id')->all());
    }

    public function test_ステータスで検索(): void
    {
        $student = User::factory()->student()->create();
        $open = QaThread::factory()->open()->create();
        $resolved = QaThread::factory()->resolved()->create();

        $result = app(IndexAction::class)($student, ['status' => 'unresolved']);

        $this->assertSame([$open->id], $result->pluck('id')->all());

        $result = app(IndexAction::class)($student, ['status' => 'resolved']);

        $this->assertSame([$resolved->id], $result->pluck('id')->all());
    }

    public function test_キーワード検索(): void
    {
        $student = User::factory()->student()->create();
        $byTitle = QaThread::factory()->create([
            'title' => 'あいうえお',
            'body' => 'かきくけこ',
            'created_at' => now()->subDays(1),
        ]);
        $byContent = QaThread::factory()->create([
            'title' => 'かきくけこ',
            'body' => 'あいうえお',
            'created_at' => now()->subDays(2)]);
        $byReplyBody = QaThread::factory()
            ->hasReplies(1, [
                'body' => 'あいうえお',
            ])
            ->create([
                'title' => 'さしすせそ',
                'body' => 'さしすせそ',
                'created_at' => now()->subDays(3),
            ]);

        $result = app(IndexAction::class)($student, ['keyword' => 'いうえ']);

        $this->assertSame(
            [$byTitle->id, $byContent->id, $byReplyBody->id],
            $result->pluck('id')->all(),
        );
    }

    public function test_資格・ステータス・キーワード検索併用(): void
    {
        $student = User::factory()->student()->create();
        $cert = Certification::factory()->published()->create();
        $otherCert = Certification::factory()->published()->create();
        $target = QaThread::factory()->for($cert)->resolved()->create(['title' => 'あいうえお']);
        QaThread::factory()->for($cert)->open()->create(['title' => 'あいうえお']);
        QaThread::factory()->for($otherCert)->resolved()->create(['title' => 'あいうえお']);
        QaThread::factory()->for($cert)->resolved()->create(['title' => 'あいうお']);

        $result = app(IndexAction::class)($student, [
            'certification_id' => $cert->id,
            'status' => 'resolved',
            'keyword' => 'いうえ',
        ]);

        $this->assertSame(
            [$target->id],
            $result->pluck('id')->all(),
        );
    }

    public function test_コーチは公開済かつ担当資格の質問を閲覧可能(): void
    {
        $coach = User::factory()->coach()->create();
        $cert = Certification::factory()->published()->create();
        $this->assignCoach($coach, $cert);

        QaThread::factory()
            ->for($cert)
            ->create([
                'title' => '5日前',
                'created_at' => now()->subDays(5),
            ]);
        QaThread::factory()
            ->for($cert)
            ->create([
                'title' => '今日',
                'created_at' => now(),
            ]);
        QaThread::factory()
            ->for($cert)
            ->create([
                'title' => '3日前',
                'created_at' => now()->subDays(3),
            ]);

        $result = app(IndexAction::class)($coach);

        $this->assertSame(
            ['今日', '3日前', '5日前'],
            $result->pluck('title')->all(),
        );
    }

    public function test_コーチは担当外の資格の質問を除外(): void
    {
        $coach = User::factory()->coach()->create();
        $other = User::factory()->coach()->create();
        $cert = Certification::factory()->published()->create();
        $this->assignCoach($other, $cert);

        // 担当外資格
        QaThread::factory()
            ->for(Certification::factory()->archived())
            ->create();

        $result = app(IndexAction::class)($coach);

        $this->assertCount(0, $result);
    }

    public function test_管理者は非公開を含めて全ての資格の質問を閲覧可能(): void
    {
        $admin = User::factory()->admin()->create();

        $published = QaThread::factory()
            ->for(Certification::factory()->published())
            ->create(['created_at' => now()->subdays(1)]);
        $archived = QaThread::factory()
            ->for(Certification::factory()->archived())
            ->create(['created_at' => now()->subdays(2)]);

        $result = app(IndexAction::class)($admin);

        $this->assertSame(
            [$published->id, $archived->id],
            $result->pluck('id')->all(),
        );
    }
}
