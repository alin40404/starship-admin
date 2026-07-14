@extends('theme::layouts.app')

@section('title', '会员登录 — '.($theme['title'] ?? 'Starship'))

@section('content')
    <div class="auth-shell">
        <div class="page-hero">
            <p class="page-hero__eyebrow">Member</p>
            <h1>会员登录</h1>
            <p class="page-hero__lede">使用用户名 / 邮箱 / 手机号登录</p>
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
                <label for="account">账号</label>
                <input id="account" type="text" name="account" value="{{ old('account') }}" required autocomplete="username">

                <label for="password">密码</label>
                <input id="password" type="password" name="password" required autocomplete="current-password">

                <div class="row">
                    <input id="remember" type="checkbox" name="remember" value="1" @checked(old('remember'))>
                    <label for="remember">记住我</label>
                </div>

                <button type="submit">登录</button>
            </form>

            <p class="member-links">
                还没有账号？<a href="{{ route('member.register') }}">注册</a>
            </p>
        </div>
    </div>
@endsection
