@extends('member::layouts.app')

@section('title', '会员中心')

@section('content')
    <h1>会员中心</h1>
    <p class="sub">当前已登录</p>

    <div class="meta">
        <div><strong>用户名</strong>：{{ $member->username }}</div>
        <div><strong>昵称</strong>：{{ $member->nickname ?: '—' }}</div>
        <div><strong>邮箱</strong>：{{ $member->email ?: '—' }}</div>
        <div><strong>手机</strong>：{{ $member->mobile ?: '—' }}</div>
    </div>

    <form method="post" action="{{ route('member.logout') }}">
        @csrf
        <button type="submit">退出登录</button>
    </form>

    <p class="links"><a href="{{ url('/') }}">返回首页</a></p>
@endsection
