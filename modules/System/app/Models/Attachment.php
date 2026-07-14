<?php

namespace Modules\System\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\System\App\Support\Storage\LocalStorage;
use Modules\System\App\Support\StorageConfigRegistrar;
use Starship\Core\Contracts\Storage\StorageInterface;

class Attachment extends Model
{

    protected $table = 'attachments';

    protected $guarded = [];

    public function getFileUrlAttribute(?string $value): ?string
    {
        if (! $this->file_path) {
            return $value;
        }

        $disk = $this->storage ?: StorageConfigRegistrar::defaultDisk();
        $storage = app(StorageInterface::class);

        if ($storage instanceof LocalStorage) {
            return $storage->onDisk($disk)->url($this->file_path);
        }

        return $storage->url($this->file_path);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
}
