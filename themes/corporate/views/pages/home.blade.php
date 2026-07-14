@extends('theme::layouts.app')

@section('title', (__('theme::ui.nav_home')).' — Starship')

@push('head')
<style>
    .hero {
        --hero-bg: url("{{ theme_asset('images/hero-bg.png') }}");
    }
</style>
@endpush

@section('content')
    <section class="hero" aria-label="{{ __('theme::ui.nav_home') }}">
        <div class="hero__bg" aria-hidden="true"></div>
        <div class="hero__veil" aria-hidden="true"></div>
        <div class="hero__inner">
            <p class="hero__brand">{{ __('theme::ui.hero_brand') }}</p>
            <h1 class="hero__title">{{ __('theme::ui.hero_title') }}</h1>
            <p class="hero__lede">
                {{ __('theme::ui.hero_lede') }}
            </p>
            <div class="hero__ctas">
                <a class="cta-link" href="#solutions">{{ __('theme::ui.hero_cta_solutions') }}</a>
                @auth('member')
                    <a class="cta-link" href="{{ route('member.account') }}">{{ __('theme::ui.hero_cta_account') }}</a>
                @else
                    <a class="cta-link" href="{{ route('member.login') }}">{{ __('theme::ui.hero_cta_login') }}</a>
                @endauth
            </div>
        </div>
    </section>

    <section id="solutions" class="section">
        <div class="container">
            <p class="section__eyebrow">{{ __('theme::ui.solutions_eyebrow') }}</p>
            <h2 class="section__title">{{ __('theme::ui.solutions_title') }}</h2>
            <p class="section__lede">
                {{ __('theme::ui.solutions_lede') }}
            </p>
            <ul class="feature-list">
                <li>
                    <strong>{{ __('theme::ui.solutions_1_title') }}</strong>
                    <p>{{ __('theme::ui.solutions_1_body') }}</p>
                </li>
                <li>
                    <strong>{{ __('theme::ui.solutions_2_title') }}</strong>
                    <p>{{ __('theme::ui.solutions_2_body') }}</p>
                </li>
                <li>
                    <strong>{{ __('theme::ui.solutions_3_title') }}</strong>
                    <p>{{ __('theme::ui.solutions_3_body') }}</p>
                </li>
            </ul>
        </div>
    </section>

    <section id="cases" class="section section--darker">
        <div class="container">
            <p class="section__eyebrow">{{ __('theme::ui.cases_eyebrow') }}</p>
            <h2 class="section__title">{{ __('theme::ui.cases_title') }}</h2>
            <p class="section__lede">{{ __('theme::ui.cases_lede') }}</p>
            <ul class="case-list">
                <li>
                    <span>{{ __('theme::ui.cases_1_tag') }}</span>
                    <strong>{{ __('theme::ui.cases_1_title') }}</strong>
                    <p>{{ __('theme::ui.cases_1_body') }}</p>
                </li>
                <li>
                    <span>{{ __('theme::ui.cases_2_tag') }}</span>
                    <strong>{{ __('theme::ui.cases_2_title') }}</strong>
                    <p>{{ __('theme::ui.cases_2_body') }}</p>
                </li>
                <li>
                    <span>{{ __('theme::ui.cases_3_tag') }}</span>
                    <strong>{{ __('theme::ui.cases_3_title') }}</strong>
                    <p>{{ __('theme::ui.cases_3_body') }}</p>
                </li>
            </ul>
        </div>
    </section>

    <section id="about" class="section">
        <div class="container">
            <p class="section__eyebrow">{{ __('theme::ui.about_eyebrow') }}</p>
            <h2 class="section__title">{{ __('theme::ui.about_title') }}</h2>
            <p class="about-copy">
                {{ __('theme::ui.about_body') }}
            </p>
        </div>
    </section>
@endsection
