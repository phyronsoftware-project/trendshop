<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductTranslation extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['specifications' => 'array'];
    }
}
