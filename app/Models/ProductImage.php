<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductImage extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    /** Return a root-safe URL for local and external product images. */
    public function url(): string
    {
        if (Str::startsWith($this->image_path, ['http://', 'https://', '/'])) {
            return $this->image_path;
        }

        return asset($this->image_path);
    }

    /** Return the public-disk path only for images uploaded through the admin. */
    public function publicDiskPath(): ?string
    {
        if (! Str::startsWith($this->image_path, 'storage/')) {
            return null;
        }

        return Str::after($this->image_path, 'storage/');
    }
}
