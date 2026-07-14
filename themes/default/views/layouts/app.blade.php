<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $theme['title'] ?? 'Starship' }}</title>
    <style>
        :root {
            --theme-bg: #f4f7fb;
            --theme-surface: #ffffff;
            --theme-accent: #2563eb;
            --theme-text: #0f172a;
            --theme-muted: #64748b;
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
    </style>
</head>
<body>
    <div class="shell">
        @yield('content')
    </div>
</body>
</html>
