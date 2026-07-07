<?php

namespace Modules\System\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Modules\System\App\Support\StorageConfigRegistrar;

class Attachment extends Model
{

    protected $table = 'attachments';

    protected $guarded = [];

    public function getFileUrlAttribute(?string $value): ?string
    {
        if (! $this->file_path) {
            return $value;
        }

        StorageConfigRegistrar::apply();

        $disk = $this->storage ?: StorageConfigRegistrar::defaultDisk();

        return Storage::disk($disk)->url($this->file_path);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
}
