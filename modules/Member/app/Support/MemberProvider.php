<?php

namespace Modules\Member\App\Support;

use Illuminate\Support\Facades\Auth;
use Modules\Member\App\Models\Member;
use Starship\Core\Contracts\Member\MemberProviderInterface;

class MemberProvider implements MemberProviderInterface
{
    public function find(string|int $id): ?object
    {
        return Member::query()->find($id);
    }

    public function current(): ?object
    {
        $user = Auth::guard('member')->user();

        return $user instanceof Member ? $user : null;
    }
}
