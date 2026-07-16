<?php

namespace Modules\System\App\Support;

use Modules\System\App\Models\Admin;
use Starship\Core\Contracts\Admin\AdminProviderInterface;

class AdminProvider implements AdminProviderInterface
{
    public function find(string|int $id): ?object
    {
        return Admin::query()->find($id);
    }

    public function current(): ?object
    {
        $user = request()->user();

        return $user instanceof Admin ? $user : null;
    }
}
