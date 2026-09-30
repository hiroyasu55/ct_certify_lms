<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CertificationStatus;
use App\Enums\QaThreadStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Certification;
use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * 開発用 質問掲示板シーダー。
 *
 * **設計思想(状態網羅 + 固定アカウント)**:
 *
 * 1. **固定アカウントのサンプルスレッド**: 固定 student が投稿した「未回答」「対応中」「解決済」の 3 状態を用意し、
 *    一覧のバッジ表示・詳細画面の編集 / 削除ボタン(投稿者本人のみ)を固定アカウントで確認できるようにする。
 *    固定 coach の回答も付け、回答の編集 / 削除ボタン(回答者本人のみ)とコーチバッジを確認できるようにする。
 *
 * 2. **退会済みユーザーのスレッド**: 投稿者が論理削除済みの場合に投稿者名が「不明」と表示されることを確認する。
 *
 * 3. **demo スレッドを 20 件超投入**: 1 ページ 20 件のページネーションと、資格 / 状態 / キーワード絞り込みを確認する。
 *
 * 依存順序: `UserSeeder` → `CertificationSeeder` → 本 Seeder。
 */
final class QaThreadSeeder extends Seeder
{
    public function run(): void
    {
        $certifications = Certification::query()
            ->where('status', CertificationStatus::Published)
            ->orderBy('created_at')
            ->get();

        if ($certifications->isEmpty()) {
            $this->command?->warn('QaThreadSeeder: 公開中の資格が存在しません。先に CertificationSeeder を実行してください。');

            return;
        }

        $this->seedFixedAccountThreads($certifications);
        $this->seedWithdrawnAuthorThread($certifications);
        $this->seedDemoThreads($certifications);
    }

    /**
     * @param Collection<int, Certification> $certifications
     */
    private function seedFixedAccountThreads(Collection $certifications): void
    {
        $student = User::query()->where('email', 'student@certify-lms.test')->first();
        $coach = User::query()->where('email', 'coach@certify-lms.test')->first();
        if ($student === null || $coach === null) {
            return;
        }

        $cert = $certifications->first();

        // 未回答: 回答 0 件
        $this->createThread($cert, $student, [
            'title' => '2 分探索木の平均比較回数のオーダーがイメージできません',
            'body' => "教材のアルゴリズム章で、2 分探索木の平均比較回数が O(log n) になる理由がつかめません。\n木が偏った場合はどうなるのかも知りたいです。",
            'days_ago' => 1,
        ]);

        // 対応中: 回答あり・未解決
        $inProgress = $this->createThread($cert, $student, [
            'title' => '基数変換の問題で 2 の補数の求め方が分かりません',
            'body' => '負の数を 8 ビットの 2 の補数で表す手順を教えてください。ビット反転して 1 を足す理由も知りたいです。',
            'days_ago' => 3,
        ]);
        $this->createReply($inProgress, $coach, 'ビット反転して 1 を足すと、元の数と足したときにちょうど 2^8 になるからです。まず -5 で手順を追ってみましょう。', 2);

        // 解決済: 回答あり・解決済
        $resolved = $this->createThread($cert, $student, [
            'title' => '模試のサブネットマスクの計算で答えが合いません',
            'body' => '/26 のサブネットで利用できるホスト数が 64 だと思ったのですが、正解は 62 でした。なぜ 2 つ減るのでしょうか。',
            'days_ago' => 7,
            'status' => QaThreadStatus::Resolved,
            'resolved_days_ago' => 5,
        ]);
        $this->createReply($resolved, $coach, 'ネットワークアドレスとブロードキャストアドレスはホストに割り当てられないため、64 から 2 を引いた 62 になります。', 6);
        $this->createReply($resolved, $student, '理解できました。ありがとうございます!', 5);
    }

    /**
     * @param Collection<int, Certification> $certifications
     */
    private function seedWithdrawnAuthorThread(Collection $certifications): void
    {
        $withdrawn = User::onlyTrashed()->where('role', UserRole::Student)->first();
        if ($withdrawn === null) {
            return;
        }

        $this->createThread($certifications->first(), $withdrawn, [
            'title' => '午後問題の時間配分のコツを教えてください',
            'body' => '午後問題でいつも時間が足りなくなります。どの順番で解くのがおすすめでしょうか。',
            'days_ago' => 20,
        ]);
    }

    /**
     * @param Collection<int, Certification> $certifications
     */
    private function seedDemoThreads(Collection $certifications): void
    {
        $students = User::query()->where('role', UserRole::Student)->where('status', UserStatus::InProgress)->get();
        $coaches = User::query()->where('role', UserRole::Coach)->get();
        if ($students->isEmpty() || $coaches->isEmpty()) {
            return;
        }

        foreach (range(1, 22) as $i) {
            $isResolved = $i % 4 === 0;
            $thread = $this->createThread($certifications->random(), $students->random(), [
                'title' => fake()->realText(40),
                'body' => fake()->realText(200),
                'days_ago' => 8 + $i,
                'status' => $isResolved ? QaThreadStatus::Resolved : QaThreadStatus::Open,
                'resolved_days_ago' => $isResolved ? 7 + $i : null,
            ]);

            // 3 件に 1 件は未回答のまま残す
            if ($i % 3 !== 0) {
                $this->createReply($thread, $coaches->random(), fake()->realText(150), 7 + $i);
            }
        }
    }

    /**
     * @param array{title: string, body: string, days_ago: int, status?: QaThreadStatus, resolved_days_ago?: int|null} $attrs
     */
    private function createThread(Certification $cert, User $author, array $attrs): QaThread
    {
        $createdAt = now()->subDays($attrs['days_ago']);
        $status = $attrs['status'] ?? QaThreadStatus::Open;
        $resolvedDaysAgo = $attrs['resolved_days_ago'] ?? null;

        return QaThread::factory()->create([
            'certification_id' => $cert->id,
            'user_id' => $author->id,
            'title' => $attrs['title'],
            'body' => $attrs['body'],
            'status' => $status,
            'resolved_at' => $status === QaThreadStatus::Resolved && $resolvedDaysAgo !== null
                ? now()->subDays($resolvedDaysAgo)
                : null,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    private function createReply(QaThread $thread, User $author, string $body, int $daysAgo): QaReply
    {
        $createdAt = now()->subDays($daysAgo);

        return QaReply::factory()->forThread($thread)->create([
            'user_id' => $author->id,
            'body' => $body,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
