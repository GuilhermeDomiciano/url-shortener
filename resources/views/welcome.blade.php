<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'URL Shortener') }} — Shorten your links. Track every click.</title>

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

    <!-- Hero Section -->
    <main class="flex-1">
        <section class="min-h-[480px] flex flex-col items-center justify-center py-20 px-6"
            style="background: linear-gradient(135deg, #EEF2FF 0%, #F9FAFB 100%);">
            <div class="text-center mb-10 max-w-2xl">
                <h1 class="text-4xl font-bold text-neutral-900 mb-4 leading-tight">
                    Shorten your links.<br>Track every click.
                </h1>
                <p class="text-lg text-neutral-700">
                    Fast, reliable, and analytics-ready URL shortener powered by Redis &amp; PostgreSQL.
                </p>
            </div>

            <!-- Shortener Form Card -->
            <div class="bg-white rounded-xl shadow-md border border-neutral-200 w-full max-w-2xl p-8">

                <!-- Error Banner -->
                <div id="error-banner"
                    class="hidden mb-5 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm flex items-start gap-3"
                    role="alert" aria-live="polite">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span id="error-banner-message"></span>
                </div>

                <!-- Form -->
                <form id="shorten-form" novalidate>
                    <div class="mb-5">
                        <label for="original_url" class="block text-sm font-medium text-neutral-700 mb-2">
                            Long URL <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <input
                            id="original_url"
                            name="original_url"
                            type="url"
                            required
                            maxlength="2048"
                            placeholder="https://your-long-url.com/with/a/very/long/path"
                            class="w-full h-11 px-4 border border-neutral-200 rounded-lg text-base focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                            aria-describedby="original_url_error"
                        >
                        <p id="original_url_error" class="hidden mt-1.5 text-sm text-red-600" role="alert" aria-live="polite"></p>
                    </div>

                    <!-- Advanced Options Toggle -->
                    <div class="mb-5">
                        <button
                            type="button"
                            id="advanced-toggle"
                            aria-expanded="false"
                            aria-controls="advanced-options"
                            class="flex items-center gap-1.5 text-sm text-indigo-600 hover:text-indigo-800 font-medium transition-colors focus:outline-none focus:underline"
                        >
                            <svg id="toggle-icon" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                            Show advanced options
                        </button>
                    </div>

                    <!-- Advanced Options Panel -->
                    <div id="advanced-options" class="hidden mb-5 space-y-4 p-4 bg-neutral-50 rounded-lg border border-neutral-200">
                        <div>
                            <label for="custom_slug" class="block text-sm font-medium text-neutral-700 mb-2">
                                Custom slug <span class="text-neutral-400 font-normal">(optional)</span>
                            </label>
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-neutral-500 shrink-0">{{ config('app.url') }}/</span>
                                <input
                                    id="custom_slug"
                                    name="custom_slug"
                                    type="text"
                                    maxlength="50"
                                    placeholder="my-custom-link"
                                    class="flex-1 h-10 px-3 border border-neutral-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                                    aria-describedby="custom_slug_error"
                                >
                            </div>
                            <p id="custom_slug_error" class="hidden mt-1.5 text-sm text-red-600" role="alert" aria-live="polite"></p>
                        </div>

                        <div>
                            <label for="expires_at" class="block text-sm font-medium text-neutral-700 mb-2">
                                Expiry date <span class="text-neutral-400 font-normal">(optional)</span>
                            </label>
                            <input
                                id="expires_at"
                                name="expires_at"
                                type="datetime-local"
                                class="w-full h-10 px-3 border border-neutral-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                                aria-describedby="expires_at_error"
                            >
                            <p id="expires_at_error" class="hidden mt-1.5 text-sm text-red-600" role="alert" aria-live="polite"></p>
                        </div>
                    </div>

                    <button
                        id="submit-btn"
                        type="submit"
                        class="w-full h-12 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                    >
                        <svg id="submit-spinner" class="hidden w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span id="submit-label">Shorten URL</span>
                    </button>
                </form>

                <!-- Result Card -->
                <div id="result-card" class="hidden">
                    <div class="flex flex-col items-center gap-4">
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <h2 class="text-lg font-semibold text-neutral-900">Your link is ready!</h2>

                        <div class="w-full flex items-center gap-2">
                            <input
                                id="short-url-display"
                                type="text"
                                readonly
                                class="flex-1 h-11 px-4 bg-neutral-50 border border-neutral-200 rounded-lg text-sm font-mono text-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                aria-label="Shortened URL"
                            >
                            <button
                                id="copy-btn"
                                type="button"
                                class="h-11 px-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition-colors flex items-center gap-1.5 whitespace-nowrap focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                aria-label="Copy short URL to clipboard"
                            >
                                <svg id="copy-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                </svg>
                                <span id="copy-label">Copy</span>
                            </button>
                        </div>

                        <div id="result-meta" class="w-full text-sm text-neutral-500 space-y-1">
                            <p class="truncate">
                                <span class="font-medium text-neutral-700">Original:</span>
                                <span id="result-original-url"></span>
                            </p>
                            <p id="result-expiry-row" class="hidden">
                                <span class="font-medium text-neutral-700">Expires:</span>
                                <span id="result-expiry"></span>
                            </p>
                            <p>
                                <span class="font-medium text-neutral-700">Created:</span>
                                <span id="result-created-at"></span>
                            </p>
                        </div>

                        <div class="flex items-center gap-4 pt-2">
                            <a id="analytics-link" href="#"
                                class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                View Analytics
                            </a>
                            <button
                                id="shorten-another-btn"
                                type="button"
                                class="text-sm text-neutral-600 hover:text-neutral-800 font-medium transition-colors focus:outline-none focus:underline"
                            >
                                Shorten another link
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Feature Cards -->
        <section class="py-16 px-6 max-w-7xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-xl border border-neutral-200 shadow-sm p-6">
                    <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h3 class="font-semibold text-neutral-900 mb-2">Instant Redirect</h3>
                    <p class="text-sm text-neutral-600">Cache-first architecture delivers sub-10ms redirects using Redis.</p>
                </div>

                <div class="bg-white rounded-xl border border-neutral-200 shadow-sm p-6">
                    <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <h3 class="font-semibold text-neutral-900 mb-2">Click Analytics</h3>
                    <p class="text-sm text-neutral-600">Track every click with timestamps, IP address, and user agent data.</p>
                </div>

                <div class="bg-white rounded-xl border border-neutral-200 shadow-sm p-6">
                    <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="font-semibold text-neutral-900 mb-2">Link Expiration</h3>
                    <p class="text-sm text-neutral-600">Set expiry dates to automatically disable links after a certain date.</p>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-neutral-50 border-t border-neutral-200 py-6 px-6 text-center text-sm text-neutral-500">
        &copy; {{ date('Y') }} {{ config('app.name', 'URL Shortener') }}. Built with Laravel 11 + Redis + PostgreSQL.
    </footer>

</body>
</html>
