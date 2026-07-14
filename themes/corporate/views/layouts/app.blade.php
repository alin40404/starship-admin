<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $theme['title'] ?? 'Starship')</title>
    <style>
        :root {
            --theme-bg: #111827;
            --theme-surface: #1f2937;
            --theme-accent: #34d399;
            --theme-text: #f9fafb;
            --theme-muted: #9ca3af;
            --theme-border: rgba(255, 255, 255, 0.12);
            --theme-danger: #f87171;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", "PingFang SC", "Microsoft YaHei", sans-serif;
            color: var(--theme-text);
            background:
                radial-gradient(900px 420px at 90% 0%, rgba(52, 211, 153, 0.16), transparent 55%),
                linear-gradient(160deg, #0b1220, var(--theme-bg) 45%, #0f172a 100%);
        }
        .shell {
            max-width: 880px;
            margin: 0 auto;
            padding: 3.5rem 1.5rem 4rem;
        }
        .shell--narrow {
            max-width: 440px;
        }
        .brand {
            font-size: 0.85rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--theme-accent);
            font-weight: 700;
            margin-bottom: 0.75rem;
        }
        h1 {
            margin: 0 0 0.75rem;
            font-size: clamp(1.8rem, 4vw, 2.5rem);
            line-height: 1.2;
        }
        .lede {
            margin: 0 0 2rem;
            color: var(--theme-muted);
            font-size: 1.05rem;
            max-width: 42rem;
        }
        .panel {
            background: var(--theme-surface);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 4px;
            padding: 1.5rem 1.75rem;
        }
        .meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem 1.25rem;
            margin: 0;
            padding: 0;
            list-style: none;
            font-size: 0.95rem;
        }
        .meta span { color: var(--theme-muted); }
        .meta strong { color: var(--theme-accent); font-weight: 600; }

        /* Member form helpers (theme overlays) */
        .member-form label {
            display: block;
            margin: 0.85rem 0 0.35rem;
            font-size: 0.9rem;
            font-weight: 600;
        }
        .member-form label:first-of-type { margin-top: 0; }
        .member-form input[type="text"],
        .member-form input[type="email"],
        .member-form input[type="password"] {
            width: 100%;
            padding: 0.65rem 0.8rem;
            border: 1px solid var(--theme-border);
            border-radius: 4px;
            background: #111827;
            color: var(--theme-text);
            font: inherit;
        }
        .member-form input:focus {
            outline: 2px solid rgba(52, 211, 153, 0.35);
            border-color: var(--theme-accent);
        }
        .member-form .row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 1rem 0 0;
        }
        .member-form .row label {
            margin: 0;
            font-weight: 500;
            color: var(--theme-muted);
        }
        .member-form button[type="submit"] {
            width: 100%;
            margin-top: 1.25rem;
            padding: 0.7rem 1rem;
            border: none;
            border-radius: 4px;
            background: var(--theme-accent);
            color: #062016;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }
        .member-form button[type="submit"]:hover {
            filter: brightness(1.08);
        }
        .member-errors {
            margin: 0 0 1rem;
            padding: 0.75rem 1rem;
            border-radius: 4px;
            background: rgba(248, 113, 113, 0.12);
            color: var(--theme-danger);
            font-size: 0.9rem;
            list-style: none;
        }
        .member-errors li + li { margin-top: 0.25rem; }
        .member-links {
            margin: 1.25rem 0 0;
            color: var(--theme-muted);
            font-size: 0.9rem;
            text-align: center;
        }
        .member-links a {
            color: var(--theme-accent);
            text-decoration: none;
            font-weight: 600;
        }
        .member-links a:hover { text-decoration: underline; }
        .member-meta {
            display: grid;
            gap: 0.65rem;
            margin: 0 0 1.25rem;
            font-size: 0.95rem;
        }
        .member-meta strong { font-weight: 600; color: var(--theme-accent); }
    </style>
    @stack('head')
</head>
<body>
    <div class="shell @yield('shell_class')">
        @yield('content')
    </div>
</body>
</html>
