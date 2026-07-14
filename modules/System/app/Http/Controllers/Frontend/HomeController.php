<?php

namespace Modules\System\App\Http\Controllers\Frontend;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Starship\Core\Theme\ThemeLoader;

class HomeController extends Controller
{
    public function index(ThemeLoader $themes): View
    {
        if (! $themes->registered()) {
            $themes->register('frontend');
        }

        return view($themes->view('home'));
    }
}
