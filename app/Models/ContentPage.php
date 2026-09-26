<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentPage extends Model
{
    protected $guarded = [];

    public function translations(): HasMany
    {
        return $this->hasMany(ContentPageTranslation::class);
    }

    // Return the selected language with a safe English fallback.
    public function translation(?string $locale = null): ?ContentPageTranslation
    {
        $locale ??= app()->getLocale();

        return $this->translations->firstWhere('locale', $locale) ?? $this->translations->firstWhere('locale', 'en') ?? $this->translations->first();
    }
}
