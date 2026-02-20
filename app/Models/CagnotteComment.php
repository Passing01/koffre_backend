<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CagnotteComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'cagnotte_id',
        'user_id',
        'contributor_name',
        'parent_id',
        'body',
        'is_blocked',
        'moderation_reason',
    ];

    protected $appends = [
        'time_ago',
        'author_name',
    ];

    public function cagnotte(): BelongsTo
    {
        return $this->belongsTo(Cagnotte::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(CagnotteComment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(CagnotteComment::class, 'parent_id')->with(['user:id,fullname,phone', 'replies']);
    }

    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    public function getAuthorNameAttribute(): string
    {
        if ($this->user) {
            return $this->user->fullname ?? $this->user->phone;
        }
        return $this->contributor_name ?? 'Anonyme';
    }
}
