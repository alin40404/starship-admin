@extends('theme::layouts.app')

@section('title', __('theme::ui.auth_login_title').' — Starship')

@section('content')
    <div class="auth-shell">
        <div class="page-hero">
            <p class="page-hero__eyebrow">{{ __('theme::ui.auth_member') }}</p>
            <h1>{{ __('theme::ui.auth_login_title') }}</h1>
            <p class="page-hero__lede">{{ __('theme::ui.auth_login_lede') }}</p>
        </div>

        <div class="panel">
            @if ($errors->any())
                <ul class="member-errors">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif

            <form class="member-form" method="post" action="{{ route('member.login.submit') }}">
                @csrf
                <label for="account">{{ __('theme::ui.auth_account') }}</label>
                <input id="account" type="text" name="account" value="{{ old('account') }}" required autocomplete="username">

                <label for="password">{{ __('theme::ui.auth_password') }}</label>
                <input id="password" type="password" name="password" required autocomplete="current-password">

                <div class="row">
                    <input id="remember" type="checkbox" name="remember" value="1" @checked(old('remember'))>
                    <label for="remember">{{ __('theme::ui.auth_remember') }}</label>
                </div>

                <button type="submit">{{ __('theme::ui.auth_submit_login') }}</button>
            </form>

            <p class="member-links">
                {{ __('theme::ui.auth_no_account') }}
                <a href="{{ route('member.register') }}">{{ __('theme::ui.auth_go_register') }}</a>
            </p>
        </div>
    </div>
@endsection
