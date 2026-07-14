@extends('theme::layouts.app')

@section('title', __('theme::ui.auth_register_title').' — Starship')
@section('body_class', 'is-member-page')

@section('content')
    <div class="member-page">
        <div class="brand">{{ __('theme::ui.auth_member') }}</div>
        <h1>{{ __('theme::ui.auth_register_title') }}</h1>
        <p class="lede">{{ __('theme::ui.auth_register_lede') }}</p>

        <div class="panel">
            @if ($errors->any())
                <ul class="member-errors">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif

            <form class="member-form" method="post" action="{{ route('member.register.submit') }}">
                @csrf
                <label for="username">{{ __('theme::ui.auth_username') }}</label>
                <input id="username" type="text" name="username" value="{{ old('username') }}" required autocomplete="username">

                <label for="nickname">{{ __('theme::ui.auth_nickname') }}</label>
                <input id="nickname" type="text" name="nickname" value="{{ old('nickname') }}">

                <label for="email">{{ __('theme::ui.auth_email') }}</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email">

                <label for="mobile">{{ __('theme::ui.auth_mobile') }}</label>
                <input id="mobile" type="text" name="mobile" value="{{ old('mobile') }}" autocomplete="tel">

                <label for="password">{{ __('theme::ui.auth_password') }}</label>
                <input id="password" type="password" name="password" required autocomplete="new-password">

                <label for="password_confirmation">{{ __('theme::ui.auth_password_confirm') }}</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">

                <button type="submit">{{ __('theme::ui.auth_submit_register') }}</button>
            </form>

            <p class="member-links">
                {{ __('theme::ui.auth_has_account') }}
                <a href="{{ route('member.login') }}">{{ __('theme::ui.auth_go_login') }}</a>
                · <a href="{{ url('/') }}">{{ __('theme::ui.auth_back_home') }}</a>
            </p>
        </div>
    </div>
@endsection
