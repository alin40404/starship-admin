<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $theme['title'] ?? 'Starship' }}</title>
    <style>
        :root {
            --theme-bg: #111827;
            --theme-surface: #1f2937;
            --theme-accent: #34d399;
            --theme-text: #f9fafb;
            --theme-muted: #9ca3af;
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
    </style>
</head>
<body>
    <div class="shell">
        @yield('content')
    </div>
</body>
</html>
