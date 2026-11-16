<?php

namespace App\Domain\Organisation\Actions;

use App\Domain\Content\Actions\WriteTranslations;
use App\Domain\Media\Actions\SyncMediaUsage;
use App\Domain\Organisation\Models\Leader;
use App\Domain\Organisation\Models\Pillar;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Saves a pillar or forum with its languages (PLAN.md §7 Pillars & forums).
 */
final class SavePillar
{
    public function __construct(
        private readonly WriteTranslations $translations,
        private readonly SyncMediaUsage $usage,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Pillar $pillar, array $data, User $actor): Pillar
    {
        /** @var array<string, array<string, mixed>> $translations */
        $translations = (array) ($data['translations'] ?? []);
        WriteTranslations::requireName($translations);

        $headId = filled($data['head_leader_id'] ?? null) && Leader::query()->whereKey($data['head_leader_id'])->exists()
            ? (string) $data['head_leader_id'] : null;

        return DB::transaction(function () use ($pillar, $data, $translations, $actor, $headId): Pillar {
            $pillar->fill([
                'head_leader_id' => $headId,
                'logo_id' => filled($data['logo_id'] ?? null) ? (int) $data['logo_id'] : null,
                'cover_image_id' => filled($data['cover_image_id'] ?? null) ? (int) $data['cover_image_id'] : null,
                'phone' => filled($data['phone'] ?? null) ? trim((string) $data['phone']) : null,
                'email' => WriteTranslations::email($data['email'] ?? null),
                'socials' => WriteTranslations::socials($data['socials'] ?? null, Leader::SOCIALS),
                'sort' => max(0, (int) ($data['sort'] ?? 0)),
                'accent' => in_array($data['accent'] ?? null, Pillar::ACCENTS, true) ? $data['accent'] : null,
            ]);
            if (! $pillar->exists) {
                $pillar->created_by = $actor->id;
            }
            $pillar->updated_by = $actor->id;
            $pillar->save();

            $this->translations->handle($pillar, $translations, ['summary'], $actor->can('pillars.manage'));
            $this->usage->handle($pillar, 'logo', [$pillar->logo_id]);
            $this->usage->handle($pillar, 'cover_image', [$pillar->cover_image_id]);

            return $pillar->refresh();
        });
    }
}
