@extends('theme::layouts.app')

@section('title', __('theme::ui.auth_account_title').' — Starship')
@section('body_class', 'is-member-page')

@section('content')
    <div class="member-page">
        <div class="brand">{{ __('theme::ui.auth_member') }}</div>
        <h1>{{ __('theme::ui.auth_account_title') }}</h1>
        <p class="lede">{{ __('theme::ui.auth_account_lede') }}</p>

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

            <p class="member-links">
                <a href="{{ url('/') }}">{{ __('theme::ui.auth_back_home') }}</a>
            </p>
        </div>
    </div>
@endsection
