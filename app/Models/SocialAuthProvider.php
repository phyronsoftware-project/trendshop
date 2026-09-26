<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['provider', 'display_name', 'icon_path', 'is_enabled', 'sort_order'])]
class SocialAuthProvider extends Model
{
    protected function casts(): array
    {
        return ['is_enabled' => 'boolean'];
    }
}
