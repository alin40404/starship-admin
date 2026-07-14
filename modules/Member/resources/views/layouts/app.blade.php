<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', '会员中心') — Starship</title>
    <style>
        :root {
            --m-bg: #f5f7fb;
            --m-card: #fff;
            --m-text: #0f172a;
            --m-muted: #64748b;
            --m-accent: #2563eb;
            --m-border: rgba(15, 23, 42, 0.1);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", "PingFang SC", "Microsoft YaHei", sans-serif;
            color: var(--m-text);
            background: linear-gradient(180deg, #eef2ff, var(--m-bg));
        }
        .wrap { max-width: 420px; margin: 0 auto; padding: 3rem 1.25rem; }
        .card {
            background: var(--m-card);
            border: 1px solid var(--m-brand, var(--m-border));
            border-radius: 14px;
            padding: 1.75rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.06);
        }
        h1 { margin: 0 0 0.35rem; font-size: 1.4rem; }
        .sub { margin: 0 0 1.25rem; color: var(--m-muted); font-size: 0.95rem; }
        label { display: block; font-size: 0.9rem; margin: 0.75rem 0 0.35rem; }
        input[type="text"], input[type="password"], input[type="email"] {
            width: 100%;
            padding: 0.65rem 0.75rem;
            border: 1px solid var(--m-border);
            border-radius: 8px;
            font-size: 1rem;
        }
        .row { display: flex; align-items: center; gap: 0.5rem; margin-top: 0.85rem; }
        .row label { margin: 0; }
        button, .btn {
            display: inline-block;
            margin-top: 1.1rem;
            width: 100%;
            border: 0;
            border-radius: 8px;
            padding: 0.7rem 1rem;
            background: var(--m-accent);
            color: #fff;
            font-size: 1rem;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
        }
        .links { margin-top: 1rem; font-size: 0.9rem; color: var(--m-muted); }
        .links a { color: var(--m-accent); }
        .errors { color: #dc2626; font-size: 0.9rem; margin: 0 0 0.75rem; padding: 0; list-style: none; }
        .meta { font-size: 0.95rem; line-height: 1.7; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            @yield('content')
        </div>
    </div>
</body>
</html>
