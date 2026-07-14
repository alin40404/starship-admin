@extends('theme::layouts.app')

@section('title', '会员中心 — '.($theme['title'] ?? 'Starship'))
@section('shell_class', 'shell--narrow')

@section('content')
    <div class="brand">{{ $theme['title'] ?? 'Starship' }} · Member</div>
    <h1>会员中心</h1>
    <p class="lede">当前已登录</p>

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

        <p class="member-links">
            <a href="{{ url('/') }}">返回首页</a>
        </p>
    </div>
@endsection
