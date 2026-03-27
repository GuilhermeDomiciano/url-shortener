<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Analytics — {{ $slug }} — {{ config('app.name', 'URL Shortener') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-neutral-50 text-neutral-900 min-h-screen flex flex-col font-sans">

    <!-- Navigation -->
    <header class="bg-white border-b border-neutral-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2 font-semibold text-lg text-indigo-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                </svg>
                <span>Short.ly</span>
            </a>
            <nav class="flex items-center gap-4">
                <a href="/" class="text-sm text-neutral-700 hover:text-indigo-600 transition-colors">Home</a>
                <a href="/dashboard"
                    class="text-sm px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium">
                    Dashboard
                </a>
            </nav>
        </div>
    </header>

    <main class="flex-1 max-w-7xl mx-auto w-full px-6 py-10">

        <!-- Back Link -->
        <a href="/dashboard"
            class="inline-flex items-center gap-1.5 text-sm text-neutral-600 hover:text-indigo-600 mb-6 transition-colors focus:outline-none focus:underline">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Back to Dashboard
        </a>

        <!-- Loading State -->
        <div id="loading-state" class="text-center py-20">
            <svg class="w-10 h-10 text-indigo-500 animate-spin mx-auto mb-4" fill="none" viewBox="0 0 24 24" aria-label="Loading analytics">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <p class="text-neutral-500 text-sm">Loading analytics...</p>
        </div>

        <!-- Error State -->
        <div id="error-state" class="hidden text-center py-20">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h2 class="text-lg font-semibold text-neutral-900 mb-2">Failed to load analytics</h2>
            <p id="error-message" class="text-sm text-neutral-500 mb-6">The analytics data could not be retrieved.</p>
            <button id="retry-btn" type="button"
                class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors">
                Retry
            </button>
        </div>

        <!-- Analytics Content -->
        <div id="analytics-content" class="hidden">

            <!-- Page Header -->
            <div class="mb-8">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-3 mb-1">
                            <h1 class="text-2xl font-bold text-neutral-900 truncate" id="header-short-url"></h1>
                            <span id="status-badge"
                                class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold shrink-0"></span>
                        </div>
                        <a id="header-original-url" href="#" target="_blank" rel="noopener noreferrer"
                            class="inline-flex items-center gap-1 text-sm text-neutral-600 hover:text-indigo-600 transition-colors max-w-xl truncate focus:outline-none focus:underline"
                            aria-label="Open original URL in new tab">
                            <span id="header-original-url-text" class="truncate"></span>
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>
                    </div>
                    <button id="header-copy-btn" type="button"
                        class="shrink-0 h-10 px-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition-colors flex items-center gap-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <span id="header-copy-label">Copy URL</span>
                    </button>
                </div>

                <!-- Meta Row -->
                <div class="flex flex-wrap gap-x-6 gap-y-1 mt-4 text-sm text-neutral-500">
                    <span>
                        <span class="font-medium text-neutral-700">Created:</span>
                        <span id="meta-created-at"></span>
                    </span>
                    <span id="meta-expires-row">
                        <span class="font-medium text-neutral-700">Expires:</span>
                        <span id="meta-expires-at"></span>
                    </span>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8" role="region" aria-label="Key performance indicators">
                <div class="bg-white rounded-xl border border-neutral-200 shadow-sm p-6">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-9 h-9 bg-indigo-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5" />
                            </svg>
                        </div>
                        <span class="text-sm text-neutral-600 font-medium">Total Clicks</span>
                    </div>
                    <p class="text-3xl font-bold text-neutral-900" id="kpi-total-clicks">—</p>
                    <p class="text-xs text-neutral-500 mt-1">All time</p>
                </div>

                <div class="bg-white rounded-xl border border-neutral-200 shadow-sm p-6">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-9 h-9 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                        <span class="text-sm text-neutral-600 font-medium">Clicks Today</span>
                    </div>
                    <p class="text-3xl font-bold text-neutral-900" id="kpi-clicks-today">—</p>
                    <p class="text-xs text-neutral-500 mt-1">Last 24 hours</p>
                </div>

                <div class="bg-white rounded-xl border border-neutral-200 shadow-sm p-6">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-9 h-9 bg-purple-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0" />
                            </svg>
                        </div>
                        <span class="text-sm text-neutral-600 font-medium">Unique IPs</span>
                    </div>
                    <p class="text-3xl font-bold text-neutral-900" id="kpi-unique-ips">—</p>
                    <p class="text-xs text-neutral-500 mt-1">Approx. unique visitors</p>
                </div>

                <div class="bg-white rounded-xl border border-neutral-200 shadow-sm p-6">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-9 h-9 bg-amber-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span class="text-sm text-neutral-600 font-medium">Last Click</span>
                    </div>
                    <p class="text-lg font-bold text-neutral-900 leading-tight" id="kpi-last-click">—</p>
                    <p class="text-xs text-neutral-500 mt-1">Most recent</p>
                </div>
            </div>

            <!-- Daily Clicks Chart -->
            <div class="bg-white rounded-xl border border-neutral-200 shadow-sm p-6 mb-8">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <h2 class="text-lg font-semibold text-neutral-900">Daily Clicks</h2>
                    <div class="flex gap-1 bg-neutral-100 p-1 rounded-lg" role="group" aria-label="Chart date range">
                        <button type="button" data-range="7"
                            class="range-btn px-3 py-1.5 text-xs font-medium rounded-md transition-colors">7 days</button>
                        <button type="button" data-range="30"
                            class="range-btn px-3 py-1.5 text-xs font-medium rounded-md transition-colors active-range">30 days</button>
                        <button type="button" data-range="90"
                            class="range-btn px-3 py-1.5 text-xs font-medium rounded-md transition-colors">90 days</button>
                    </div>
                </div>

                <!-- Chart empty state -->
                <div id="chart-empty" class="hidden py-12 text-center text-neutral-500 text-sm">
                    <svg class="w-10 h-10 text-neutral-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    Not enough data yet. Clicks will appear here once the link receives traffic.
                </div>

                <!-- Canvas chart -->
                <div id="chart-container" class="relative" style="height: 260px;">
                    <canvas id="clicks-chart" aria-label="Daily clicks bar chart"></canvas>
                </div>

                <!-- Accessible table fallback -->
                <details class="mt-4">
                    <summary class="text-xs text-neutral-500 cursor-pointer hover:text-neutral-700 focus:outline-none focus:underline">
                        View data as table (screen reader friendly)
                    </summary>
                    <div class="overflow-x-auto mt-3">
                        <table id="chart-table" class="w-full text-xs text-left text-neutral-600">
                            <thead>
                                <tr class="border-b border-neutral-200">
                                    <th class="pb-2 font-medium">Date</th>
                                    <th class="pb-2 font-medium text-right">Clicks</th>
                                </tr>
                            </thead>
                            <tbody id="chart-table-body"></tbody>
                        </table>
                    </div>
                </details>
            </div>

            <!-- Recent Clicks Table -->
            <div class="bg-white rounded-xl border border-neutral-200 shadow-sm p-6">
                <h2 class="text-lg font-semibold text-neutral-900 mb-1">Recent Clicks</h2>
                <p class="text-xs text-neutral-500 mb-5">IP addresses are partially masked for privacy.</p>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left" aria-label="Recent clicks">
                        <thead>
                            <tr class="border-b border-neutral-200 text-xs font-medium text-neutral-500 uppercase tracking-wide">
                                <th class="pb-3 pr-4">Time</th>
                                <th class="pb-3 pr-4">IP Address</th>
                                <th class="pb-3">Browser / OS</th>
                            </tr>
                        </thead>
                        <tbody id="recent-clicks-body">
                            <!-- Rows injected by JS -->
                        </tbody>
                    </table>
                </div>

                <!-- Empty state for table -->
                <div id="clicks-empty" class="hidden py-10 text-center text-sm text-neutral-500">
                    <svg class="w-8 h-8 text-neutral-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5" />
                    </svg>
                    No clicks recorded yet.
                </div>

                <!-- Load More -->
                <div id="load-more-container" class="hidden mt-5 text-center">
                    <button id="load-more-btn" type="button"
                        class="text-sm text-indigo-600 hover:text-indigo-800 font-medium focus:outline-none focus:underline transition-colors">
                        Load more
                    </button>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-neutral-50 border-t border-neutral-200 py-6 px-6 text-center text-sm text-neutral-500 mt-10">
        &copy; {{ date('Y') }} {{ config('app.name', 'URL Shortener') }}. Built with Laravel 11 + Redis + PostgreSQL.
    </footer>

    <script>
        window.ANALYTICS_SLUG = @json($slug);
        window.APP_URL = @json(config('app.url'));
    </script>
</body>
</html>
