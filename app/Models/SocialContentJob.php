<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialContentJob extends Model
{
    use HasFactory;

    public const STATUSES = [
        'detected',
        'needs_product',
        'validated',
        'generating_content',
        'generating_media',
        'waiting_video',
        'waiting_approval',
        'approved',
        'publishing',
        'published',
        'partially_published',
        'rejected',
        'failed',
    ];

    public const APPROVAL_STATUSES = ['pending', 'approved', 'rejected'];

    protected $fillable = [
        'job_key',
        'product_id',
        'source_driver',
        'source_file_id',
        'source_file_name',
        'source_mime_type',
        'source_url',
        'source_hash',
        'status',
        'approval_status',
        'product_snapshot',
        'generated_content',
        'media',
        'publishing',
        'approval_note',
        'last_error',
        'attempts',
        'approved_at',
        'published_at',
    ];

    protected $casts = [
        'product_snapshot' => 'array',
        'generated_content' => 'array',
        'media' => 'array',
        'publishing' => 'array',
        'approved_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getRouteKeyName(): string
    {
        return 'job_key';
    }
}
