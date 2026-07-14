@extends('theme::layouts.app')

@section('title', (__('theme::ui.home_title')).' — Starship')

@section('content')
    <section class="page-hero">
        <div class="container">
            <p class="page-hero__eyebrow">{{ __('theme::ui.home_eyebrow') }}</p>
            <h1>{{ __('theme::ui.home_title') }}</h1>
            <p class="page-hero__lede">
                {{ __('theme::ui.home_lede') }}
            </p>
            <div class="btn-row">
                @auth('member')
                    <a class="btn btn--primary" href="{{ route('member.account') }}">{{ __('theme::ui.nav_account') }}</a>
                    <form method="post" action="{{ route('member.logout') }}">
                        @csrf
                        <button class="btn btn--ghost" type="submit">{{ __('theme::ui.auth_logout') }}</button>
                    </form>
                @else
                    <a class="btn btn--primary" href="{{ route('member.login') }}">{{ __('theme::ui.member_login') }}</a>
                    <a class="btn btn--ghost" href="{{ route('member.register') }}">{{ __('theme::ui.member_register') }}</a>
                @endauth
            </div>
        </div>
    </section>

    <div class="container">
        <div class="panel">
            <ul class="meta-list">
                <li><span>{{ __('theme::ui.meta_slug') }}</span> · <strong data-theme-slug>{{ $theme['slug'] ?? 'default' }}</strong></li>
                <li><span>{{ __('theme::ui.meta_directory') }}</span> · <strong>{{ $theme['directory'] ?? 'default' }}</strong></li>
                <li><span>{{ __('theme::ui.meta_type') }}</span> · <strong>{{ $theme['type'] ?? 'frontend' }}</strong></li>
            </ul>
        </div>
    </div>
@endsection
