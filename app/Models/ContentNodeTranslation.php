<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentNodeTranslation extends Model
{
    protected $fillable = [
        'content_node_id',
        'language_code',
        'title',
        'body_html',
        'status',
    ];

    public function contentNode(): BelongsTo
    {
        return $this->belongsTo(ContentNode::class);
    }
}
