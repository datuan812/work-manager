<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminAuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['actor_id', 'actor_name', 'action', 'subject_type', 'subject_id', 'subject_name', 'details'];

    protected function casts(): array
    {
        return ['details' => 'array'];
    }

    public static function record(?User $actor, string $action, Model $subject, array $details = []): void
    {
        self::create([
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->name ?? 'Hệ thống',
            'action' => $action,
            'subject_type' => $subject->getTable(),
            'subject_id' => $subject->getKey(),
            'subject_name' => $subject->name ?? $subject->title,
            'details' => $details ?: null,
        ]);
    }
}
