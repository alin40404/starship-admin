<?php

namespace Modules\Member\App\Http\Controllers\Frontend;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Member\App\Models\Member;

class AuthController extends Controller
{
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::guard('member')->check()) {
            return redirect()->route('member.account');
        }

        return $this->memberView('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'account' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'max:64'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $member = Member::findByLoginIdentifier($credentials['account']);
        if (
            $member === null
            || ! $member->isEnabled()
            || ! Hash::check($credentials['password'], (string) $member->password)
        ) {
            throw ValidationException::withMessages([
                'account' => [__('member::messages.login_failed')],
            ]);
        }

        Auth::guard('member')->login($member, (bool) ($credentials['remember'] ?? false));
        $request->session()->regenerate();

        $member->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        return redirect()->intended(route('member.account'));
    }

    public function showRegisterForm(): View|RedirectResponse
    {
        if (Auth::guard('member')->check()) {
            return redirect()->route('member.account');
        }

        return $this->memberView('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique(Member::class, 'username')],
            'password' => ['required', 'string', 'min:6', 'max:64', 'confirmed'],
            'nickname' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:100', Rule::unique(Member::class, 'email')],
            'mobile' => ['nullable', 'string', 'max:20', Rule::unique(Member::class, 'mobile')],
        ]);

        $member = Member::query()->create([
            ...$data,
            'nickname' => $data['nickname'] ?? $data['username'],
            'status' => 1,
        ]);

        Auth::guard('member')->login($member);
        $request->session()->regenerate();

        return redirect()->route('member.account');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('member')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('member.login');
    }

    public function account(): View
    {
        return $this->memberView('auth.account', [
            'member' => Auth::guard('member')->user(),
        ]);
    }

    /**
     * 主题可覆盖：优先 theme::pages.member.*，否则使用模块默认视图。
     *
     * @param  array<string, mixed>  $data
     */
    protected function memberView(string $name, array $data = []): View
    {
        $themeName = 'theme::pages.member.'.str_replace('/', '.', $name);
        if (ViewFacade::exists($themeName)) {
            return view($themeName, $data);
        }

        return view('member::'.$name, $data);
    }
}
