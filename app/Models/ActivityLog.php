<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'action', 'model_type', 'model_id', 'description', 'changes', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'changes' => 'array',
        'user_id' => 'integer',
        'model_id' => 'integer',
    ];

    /**
     * The user that performed the action (nullable).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
