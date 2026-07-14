@extends('member::layouts.app')

@section('title', '会员注册')

@section('content')
    <h1>会员注册</h1>
    <p class="sub">创建前台会员账号</p>

    @if ($errors->any())
        <ul class="errors">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="post" action="{{ route('member.register.submit') }}">
        @csrf
        <label for="username">用户名</label>
        <input id="username" type="text" name="username" value="{{ old('username') }}" required autocomplete="username">

        <label for="nickname">昵称（可选）</label>
        <input id="nickname" type="text" name="nickname" value="{{ old('nickname') }}">

        <label for="email">邮箱（可选）</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email">

        <label for="mobile">手机号（可选）</label>
        <input id="mobile" type="text" name="mobile" value="{{ old('mobile') }}" autocomplete="tel">

        <label for="password">密码</label>
        <input id="password" type="password" name="password" required autocomplete="new-password">

        <label for="password_confirmation">确认密码</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">

        <button type="submit">注册</button>
    </form>

    <p class="links">
        已有账号？<a href="{{ route('member.login') }}">登录</a>
        · <a href="{{ url('/') }}">返回首页</a>
    </p>
@endsection
