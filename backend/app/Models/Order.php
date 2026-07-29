<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $fillable = [
        'excel_template_id',
        'tier_index',
        'tier_name',
        'client_name',
        'client_email',
        'comment',
        'items',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'total_amount' => 'decimal:2',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ExcelTemplate::class, 'excel_template_id');
    }
}
