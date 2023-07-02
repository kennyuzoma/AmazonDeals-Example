<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class StatusUpdate extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'service',
        'user_id',
        'imported',
        'body',
        'send_at',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'send_at' => 'datetime:Y-m-d H:i:s'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function connected_accounts()
    {
        return $this->belongsToMany(ConnectedAccount::class);
    }
}
