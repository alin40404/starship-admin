@extends('theme::layouts.app')

@section('title', '会员中心 — '.($theme['title'] ?? 'Starship'))

@section('content')
    <div class="auth-shell">
        <div class="page-hero">
            <p class="page-hero__eyebrow">Member</p>
            <h1>会员中心</h1>
            <p class="page-hero__lede">当前已登录</p>
        </div>

        <div class="panel">
            <div class="member-meta">
                <div><strong>用户名</strong>：{{ $member->username }}</div>
                <div><strong>昵称</strong>：{{ $member->nickname ?: '—' }}</div>
                <div><strong>邮箱</strong>：{{ $member->email ?: '—' }}</div>
                <div><strong>手机</strong>：{{ $member->mobile ?: '—' }}</div>
            </div>

            <form class="member-form" method="post" action="{{ route('member.logout') }}">
                @csrf
                <button type="submit">退出登录</button>
            </form>
        </div>
    </div>
@endsection
