@extends('theme::layouts.app')

@section('title', ($theme['title'] ?? '企业主题').' — Starship')

@push('head')
<style>
    .hero {
        --hero-bg: url("{{ theme_asset('images/hero-bg.png') }}");
    }
</style>
@endpush

@section('content')
    <section class="hero" aria-label="首页主视觉">
        <div class="hero__bg" aria-hidden="true"></div>
        <div class="hero__veil" aria-hidden="true"></div>
        <div class="hero__inner">
            <p class="hero__brand">Starship</p>
            <h1 class="hero__title">为企业构建可信赖的数字化基座</h1>
            <p class="hero__lede">
                融合先进技术与行业实践，助力企业数字化转型，释放业务增长新动能。
            </p>
            <div class="hero__ctas">
                <a class="cta-link" href="#solutions">了解方案 →</a>
                @auth('member')
                    <a class="cta-link" href="{{ route('member.account') }}">会员中心 →</a>
                @else
                    <a class="cta-link" href="{{ route('member.login') }}">会员登录 →</a>
                @endauth
            </div>
        </div>
    </section>

    <section id="solutions" class="section">
        <div class="container">
            <p class="section__eyebrow">Solutions</p>
            <h2 class="section__title">方案</h2>
            <p class="section__lede">
                以模块化平台能力支撑后台治理、会员体系与可扩展业务前台，主题可独立换肤而不改动业务内核。
            </p>
            <ul class="feature-list">
                <li>
                    <strong>统一治理后台</strong>
                    <p>权限、菜单、组织与配置一体化，支撑多团队协同落地。</p>
                </li>
                <li>
                    <strong>可插拔扩展</strong>
                    <p>模块 / 插件 / 主题分层，接入支付、短信等能力时不影响主业务。</p>
                </li>
                <li>
                    <strong>前台多主题</strong>
                    <p>Blade 主题热切换，企业官网与运营站可分轨呈现。</p>
                </li>
            </ul>
        </div>
    </section>

    <section id="cases" class="section section--darker">
        <div class="container">
            <p class="section__eyebrow">Cases</p>
            <h2 class="section__title">案例</h2>
            <p class="section__lede">示例场景占位，后续可替换为真实客户故事。</p>
            <ul class="case-list">
                <li>
                    <span>制造</span>
                    <strong>集团数字化运营中枢</strong>
                    <p>统一账号与权限后，跨事业部后台与会员门户同仓演进。</p>
                </li>
                <li>
                    <span>服务</span>
                    <strong>渠道合作门户</strong>
                    <p>深色品牌站承接线索，会员登录进入业务协作区。</p>
                </li>
                <li>
                    <span>科技</span>
                    <strong>产品官网与控制台主题分离</strong>
                    <p>营销站与管理端视觉独立，共用同一套核心模块。</p>
                </li>
            </ul>
        </div>
    </section>

    <section id="about" class="section">
        <div class="container">
            <p class="section__eyebrow">About</p>
            <h2 class="section__title">关于</h2>
            <p class="about-copy">
                Starship Corporate 主题面向企业官网 Demo：深色科技感首屏、半透明导航与青色强调色，
                用于展示「可切换前台主题」能力。业务逻辑仍由 Module 承载，主题仅负责展示层。
            </p>
        </div>
    </section>
@endsection
