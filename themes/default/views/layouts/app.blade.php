<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $theme['title'] ?? 'Starship')</title>
    <style>
        :root {
            --theme-bg: #f4f7fb;
            --theme-surface: #ffffff;
            --theme-accent: #2563eb;
            --theme-text: #0f172a;
            --theme-muted: #64748b;
            --theme-border: rgba(15, 23, 42, 0.1);
            --theme-danger: #dc2626;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", "PingFang SC", "Microsoft YaHei", sans-serif;
            color: var(--theme-text);
            background:
                radial-gradient(1200px 480px at 10% -10%, rgba(37, 99, 235, 0.18), transparent 60%),
                linear-gradient(180deg, var(--theme-bg), #e8eef7 100%);
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
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--theme-accent);
            font-weight: 700;
            margin-bottom: 0.75rem;
        }
        h1 {
            margin: 0 0 0.75rem;
            font-size: clamp(1.8rem, 4vw, 2.4rem);
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
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 16px;
            padding: 1.5rem 1.75rem;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
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
        .meta strong { color: var(--theme-text); font-weight: 600; }

        /* Member form helpers (theme overrides) */
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
            border-radius: 10px;
            background: #fff;
            color: var(--theme-text);
            font: inherit;
        }
        .member-form input:focus {
            outline: 2px solid rgba(37, 99, 235, 0.25);
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
            border-radius: 10px;
            background: var(--theme-accent);
            color: #fff;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
        }
        .member-form button[type="submit"]:hover {
            filter: brightness(1.05);
        }
        .member-errors {
            margin: 0 0 1rem;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            background: #fef2f2;
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
        .member-meta strong { font-weight: 600; }
    </style>
    @stack('head')
</head>
<body>
    <div class="shell @yield('shell_class')">
        @yield('content')
    </div>
</body>
</html>
