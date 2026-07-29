<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExcelTemplate extends Model
{
    protected $fillable = [
        'original_name',
        'storage_path',
        'parsed_data',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'parsed_data' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
