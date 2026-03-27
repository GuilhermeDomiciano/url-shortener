<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Health — URL Shortener</title>
    <style>
        /* --- Custom Properties ------------------------------------------ */
        :root {
            --color-bg:          #FDFDFC;
            --color-card-bg:     #ffffff;
            --color-text-primary:   #1b1b18;
            --color-text-secondary: #706f6c;
            --color-border:      #e3e3e0;
            --color-accent:      #f53003;
            --color-status-ok:   #16a34a;
            --color-status-error:#dc2626;

            --font-family: 'Instrument Sans', ui-sans-serif, system-ui, -apple-system, sans-serif;
            --radius-sm: 0.25rem;
            --radius-lg: 0.5rem;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --color-bg:          #0a0a0a;
                --color-card-bg:     #161615;
                --color-text-primary:   #EDEDEC;
                --color-text-secondary: #A1A09A;
                --color-border:      #3E3E3A;
                --color-accent:      #FF4433;
                --color-status-ok:   #22c55e;
                --color-status-error:#f87171;
            }
        }

        /* --- Reset & Base ---------------------------------------------- */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html, body {
            height: 100%;
        }

        body {
            background-color: var(--color-bg);
            color: var(--color-text-primary);
            font-family: var(--font-family);
            font-size: 0.875rem;
            line-height: 1.5;
            border-top: 4px solid var(--color-accent);
        }

        /* --- Layout ---------------------------------------------------- */
        .page-wrapper {
            min-height: 100%;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 3rem 1rem;
        }

        .container {
            width: 100%;
            max-width: 480px;
        }

        /* --- Header ---------------------------------------------------- */
        .page-header {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .page-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--color-text-primary);
            letter-spacing: -0.01em;
        }

        .page-timestamp {
            font-size: 0.75rem;
            color: var(--color-text-secondary);
            white-space: nowrap;
        }

        .page-divider {
            border: none;
            border-top: 1px solid var(--color-border);
            margin-bottom: 1.5rem;
        }

        /* --- Cards ----------------------------------------------------- */
        .card {
            background-color: var(--color-card-bg);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            padding: 1.25rem 1.5rem;
            margin-bottom: 1rem;
        }

        .card-label {
            font-size: 0.6875rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--color-text-secondary);
            margin-bottom: 0.75rem;
        }

        /* --- Overall status badge -------------------------------------- */
        .overall-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .status-dot--ok    { background-color: var(--color-status-ok); }
        .status-dot--error { background-color: var(--color-status-error); }

        .overall-status-text {
            font-size: 1rem;
            font-weight: 600;
        }

        .overall-status-text--ok    { color: var(--color-status-ok); }
        .overall-status-text--error { color: var(--color-status-error); }

        /* --- Service rows ---------------------------------------------- */
        .service-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.625rem;
        }

        .service-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .service-name {
            font-weight: 500;
            color: var(--color-text-primary);
        }

        .service-status {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            font-size: 0.8125rem;
        }

        .service-status--ok    { color: var(--color-status-ok); }
        .service-status--error { color: var(--color-status-error); }

        /* --- Footer ---------------------------------------------------- */
        .page-footer {
            margin-top: 1.25rem;
            font-size: 0.75rem;
            color: var(--color-text-secondary);
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .footer-sep {
            color: var(--color-border);
        }
    </style>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
</head>
<body>
    <div class="page-wrapper">
        <main class="container" role="main" aria-label="System health status">

            {{-- Page header --}}
            <header class="page-header">
                <h1 class="page-title">System Health</h1>
                <time class="page-timestamp" datetime="{{ $timestamp ?? now()->toIso8601String() }}">
                    {{ $timestamp ?? now()->toIso8601String() }}
                </time>
            </header>

            <hr class="page-divider" aria-hidden="true">

            {{-- Overall status card --}}
            <section class="card" aria-label="Overall status">
                <p class="card-label">Overall Status</p>
                @php
                    $overallOk = ($status ?? 'ok') === 'ok';
                @endphp
                <div class="overall-status">
                    <span
                        class="status-dot {{ $overallOk ? 'status-dot--ok' : 'status-dot--error' }}"
                        role="img"
                        aria-label="{{ $overallOk ? 'Operational' : 'Degraded' }}"
                    ></span>
                    <span class="overall-status-text {{ $overallOk ? 'overall-status-text--ok' : 'overall-status-text--error' }}">
                        {{ $overallOk ? 'Operational' : 'Degraded' }}
                    </span>
                </div>
            </section>

            {{-- Services card --}}
            <section class="card" aria-label="Service statuses">
                <p class="card-label">Services</p>
                <ul class="service-list" role="list">
                    @php
                        $services = $services ?? ['database' => 'ok', 'redis' => 'ok'];
                    @endphp
                    @foreach ($services as $name => $serviceStatus)
                        @php $isOk = $serviceStatus === 'ok'; @endphp
                        <li class="service-item" role="listitem">
                            <span class="service-name">
                                {{ match($name) {
                                    'database' => 'PostgreSQL',
                                    'redis'    => 'Redis',
                                    default    => ucfirst($name),
                                } }}
                            </span>
                            <span class="service-status {{ $isOk ? 'service-status--ok' : 'service-status--error' }}"
                                  aria-label="{{ ucfirst($name) }} is {{ $serviceStatus }}">
                                <span class="status-dot {{ $isOk ? 'status-dot--ok' : 'status-dot--error' }}"
                                      aria-hidden="true"></span>
                                {{ $serviceStatus }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            </section>

            {{-- Footer meta --}}
            <footer class="page-footer" role="contentinfo">
                <span>GET /health</span>
                <span class="footer-sep" aria-hidden="true">·</span>
                <span>HTTP {{ $overallOk ? '200' : '503' }}</span>
                <span class="footer-sep" aria-hidden="true">·</span>
                <span>application/json</span>
            </footer>

        </main>
    </div>
</body>
</html>
