<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditEvent extends Model
{
    protected $fillable = ['actor_id', 'action', 'subject_type', 'subject_id'];

    public static function record(User $actor, string $action, Model $subject): void
    {
        static::query()->create([
            'actor_id' => $actor->id,
            'action' => $action,
            'subject_type' => class_basename($subject),
            'subject_id' => $subject->getKey(),
        ]);
    }
}
