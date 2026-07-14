@extends('theme::layouts.app')

@section('title', __('theme::ui.auth_account_title').' — Starship')

@section('content')
    <div class="auth-shell">
        <div class="page-hero">
            <p class="page-hero__eyebrow">{{ __('theme::ui.auth_member') }}</p>
            <h1>{{ __('theme::ui.auth_account_title') }}</h1>
            <p class="page-hero__lede">{{ __('theme::ui.auth_account_lede') }}</p>
        </div>

        <div class="panel">
            <div class="member-meta">
                <div><strong>{{ __('theme::ui.auth_field_username') }}</strong>：{{ $member->username }}</div>
                <div><strong>{{ __('theme::ui.auth_field_nickname') }}</strong>：{{ $member->nickname ?: '—' }}</div>
                <div><strong>{{ __('theme::ui.auth_field_email') }}</strong>：{{ $member->email ?: '—' }}</div>
                <div><strong>{{ __('theme::ui.auth_field_mobile') }}</strong>：{{ $member->mobile ?: '—' }}</div>
            </div>

            <form class="member-form" method="post" action="{{ route('member.logout') }}">
                @csrf
                <button type="submit">{{ __('theme::ui.auth_logout') }}</button>
            </form>
        </div>
    </div>
@endsection
