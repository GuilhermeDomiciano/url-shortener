import './bootstrap';

/**
 * URL Shortener — Frontend JavaScript
 * Handles: form submission, clipboard copy, analytics chart, recent clicks table.
 */

// ---------------------------------------------------------------------------
// Utility helpers
// ---------------------------------------------------------------------------

/**
 * Format an ISO date string to a human-readable relative time (e.g. "3 min ago").
 * Falls back to the formatted date string for older timestamps.
 */
function relativeTime(isoString) {
    if (!isoString) return 'N/A';
    const date = new Date(isoString);
    const now = new Date();
    const diffSeconds = Math.floor((now - date) / 1000);

    if (diffSeconds < 60) return `${diffSeconds}s ago`;
    if (diffSeconds < 3600) return `${Math.floor(diffSeconds / 60)}m ago`;
    if (diffSeconds < 86400) return `${Math.floor(diffSeconds / 3600)}h ago`;
    if (diffSeconds < 604800) return `${Math.floor(diffSeconds / 86400)}d ago`;

    return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}

/**
 * Format an ISO date string to a full readable datetime.
 */
function formatDate(isoString) {
    if (!isoString) return 'N/A';
    return new Date(isoString).toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

/**
 * Mask the last octet of an IPv4 address (e.g. 192.168.1.5 → 192.168.1.xxx).
 * IPv6 addresses have the last segment masked similarly.
 */
function maskIp(ip) {
    if (!ip) return 'N/A';
    if (ip.includes('.')) {
        const parts = ip.split('.');
        parts[parts.length - 1] = 'xxx';
        return parts.join('.');
    }
    if (ip.includes(':')) {
        const parts = ip.split(':');
        parts[parts.length - 1] = 'xxxx';
        return parts.join(':');
    }
    return ip;
}

/**
 * Extract a simplified browser/OS summary from a user-agent string.
 */
function parseUserAgent(ua) {
    if (!ua) return 'Unknown';
    let browser = 'Unknown Browser';
    let os = '';

    if (ua.includes('Edg/')) browser = 'Edge';
    else if (ua.includes('OPR/') || ua.includes('Opera')) browser = 'Opera';
    else if (ua.includes('Chrome/')) browser = 'Chrome';
    else if (ua.includes('Safari/') && !ua.includes('Chrome')) browser = 'Safari';
    else if (ua.includes('Firefox/')) browser = 'Firefox';
    else if (ua.includes('MSIE') || ua.includes('Trident/')) browser = 'IE';
    else if (ua.includes('curl/')) browser = 'curl';

    if (ua.includes('Windows NT')) os = 'Windows';
    else if (ua.includes('Macintosh') || ua.includes('Mac OS X')) os = 'macOS';
    else if (ua.includes('Linux')) os = 'Linux';
    else if (ua.includes('Android')) os = 'Android';
    else if (ua.includes('iPhone') || ua.includes('iPad')) os = 'iOS';

    return os ? `${browser} / ${os}` : browser;
}

/**
 * Copy text to clipboard and provide visual feedback on a button.
 * @param {string} text - Text to copy
 * @param {HTMLElement} btn - Button element to show feedback on
 * @param {HTMLElement} labelEl - Span within the button to update
 */
async function copyToClipboard(text, btn, labelEl) {
    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
        } else {
            // Fallback for non-secure contexts
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.focus();
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
        }
        const original = labelEl.textContent;
        labelEl.textContent = 'Copied!';
        btn.disabled = true;
        setTimeout(() => {
            labelEl.textContent = original;
            btn.disabled = false;
        }, 2000);
    } catch {
        labelEl.textContent = 'Failed';
        setTimeout(() => { labelEl.textContent = 'Copy'; }, 2000);
    }
}

// ---------------------------------------------------------------------------
// Home page — URL shortening form
// ---------------------------------------------------------------------------

function initShortenForm() {
    const form = document.getElementById('shorten-form');
    if (!form) return;

    const submitBtn = document.getElementById('submit-btn');
    const submitLabel = document.getElementById('submit-label');
    const submitSpinner = document.getElementById('submit-spinner');
    const errorBanner = document.getElementById('error-banner');
    const errorBannerMsg = document.getElementById('error-banner-message');
    const resultCard = document.getElementById('result-card');
    const advancedToggle = document.getElementById('advanced-toggle');
    const advancedOptions = document.getElementById('advanced-options');
    const toggleIcon = document.getElementById('toggle-icon');
    const copyBtn = document.getElementById('copy-btn');
    const copyLabel = document.getElementById('copy-label');
    const shortenAnotherBtn = document.getElementById('shorten-another-btn');
    const analyticsLink = document.getElementById('analytics-link');

    // Advanced options toggle
    if (advancedToggle) {
        advancedToggle.addEventListener('click', () => {
            const isOpen = advancedOptions.classList.toggle('hidden') === false;
            advancedToggle.setAttribute('aria-expanded', String(isOpen));
            toggleIcon.style.transform = isOpen ? 'rotate(90deg)' : 'rotate(0deg)';
            // Update visible label — the text lives in the last text node of the button
            const lastText = [...advancedToggle.childNodes].reverse().find(n => n.nodeType === Node.TEXT_NODE);
            if (lastText) {
                lastText.textContent = isOpen ? ' Hide advanced options' : ' Show advanced options';
            }
        });
    }

    // Inline field error helpers
    function setFieldError(fieldId, message) {
        const field = document.getElementById(fieldId);
        const errorEl = document.getElementById(`${fieldId}_error`);
        if (field) {
            field.classList.add('border-red-500');
            field.setAttribute('aria-invalid', 'true');
        }
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.classList.remove('hidden');
        }
    }

    function clearFieldError(fieldId) {
        const field = document.getElementById(fieldId);
        const errorEl = document.getElementById(`${fieldId}_error`);
        if (field) {
            field.classList.remove('border-red-500');
            field.removeAttribute('aria-invalid');
        }
        if (errorEl) {
            errorEl.textContent = '';
            errorEl.classList.add('hidden');
        }
    }

    function clearAllErrors() {
        ['original_url', 'custom_slug', 'expires_at'].forEach(clearFieldError);
        if (errorBanner) errorBanner.classList.add('hidden');
    }

    function showBannerError(message) {
        if (errorBannerMsg) errorBannerMsg.textContent = message;
        if (errorBanner) errorBanner.classList.remove('hidden');
    }

    function setLoading(loading) {
        submitBtn.disabled = loading;
        submitLabel.textContent = loading ? 'Shortening...' : 'Shorten URL';
        submitSpinner.classList.toggle('hidden', !loading);
    }

    function showResultCard(data) {
        form.classList.add('hidden');
        resultCard.classList.remove('hidden');

        const shortUrl = data.short_url || `${window.location.origin}/${data.slug}`;
        document.getElementById('short-url-display').value = shortUrl;
        document.getElementById('result-original-url').textContent = data.original_url;
        document.getElementById('result-created-at').textContent = formatDate(data.created_at);

        if (data.expires_at) {
            document.getElementById('result-expiry').textContent = formatDate(data.expires_at);
            document.getElementById('result-expiry-row').classList.remove('hidden');
        } else {
            document.getElementById('result-expiry-row').classList.add('hidden');
        }

        if (analyticsLink) {
            analyticsLink.href = `/analytics/${data.slug}`;
        }
    }

    // Copy button
    if (copyBtn) {
        copyBtn.addEventListener('click', () => {
            const url = document.getElementById('short-url-display').value;
            copyToClipboard(url, copyBtn, copyLabel);
        });
    }

    // Shorten another
    if (shortenAnotherBtn) {
        shortenAnotherBtn.addEventListener('click', () => {
            resultCard.classList.add('hidden');
            form.classList.remove('hidden');
            form.reset();
            clearAllErrors();
            // Close advanced options
            if (advancedOptions && !advancedOptions.classList.contains('hidden')) {
                advancedOptions.classList.add('hidden');
                advancedToggle.setAttribute('aria-expanded', 'false');
                toggleIcon.style.transform = 'rotate(0deg)';
            }
        });
    }

    // Form submission
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearAllErrors();

        const originalUrl = document.getElementById('original_url').value.trim();
        const customSlug = document.getElementById('custom_slug')?.value.trim() || '';
        const expiresAt = document.getElementById('expires_at')?.value || '';

        // Client-side validation
        if (!originalUrl) {
            setFieldError('original_url', 'Please enter a URL.');
            document.getElementById('original_url').focus();
            return;
        }

        // Build request payload
        const payload = { original_url: originalUrl };
        if (customSlug) payload.custom_slug = customSlug;
        if (expiresAt) {
            // Convert datetime-local to ISO 8601 with Z suffix
            payload.expires_at = new Date(expiresAt).toISOString();
        }

        setLoading(true);

        try {
            const response = await fetch('/api/links', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json();

            if (response.ok) {
                showResultCard(data);
            } else if (response.status === 422) {
                // Validation errors
                const errors = data.errors || {};
                if (errors.original_url?.length) {
                    setFieldError('original_url', errors.original_url[0]);
                }
                if (errors.custom_slug?.length) {
                    setFieldError('custom_slug', errors.custom_slug[0]);
                }
                if (errors.expires_at?.length) {
                    setFieldError('expires_at', errors.expires_at[0]);
                }
                if (!Object.keys(errors).length && data.message) {
                    showBannerError(data.message);
                }
            } else if (response.status === 409) {
                setFieldError('custom_slug', data.message || 'This custom slug is already in use.');
            } else if (response.status === 429) {
                showBannerError('Too many requests. Please wait before trying again.');
            } else {
                showBannerError(data.message || 'Something went wrong. Please try again.');
            }
        } catch {
            showBannerError('Network error. Please check your connection and try again.');
        } finally {
            setLoading(false);
        }
    });
}

// ---------------------------------------------------------------------------
// Analytics page — chart + recent clicks
// ---------------------------------------------------------------------------

function initAnalyticsPage() {
    const slug = window.ANALYTICS_SLUG;
    if (!slug) return;

    const loadingState = document.getElementById('loading-state');
    const errorState = document.getElementById('error-state');
    const analyticsContent = document.getElementById('analytics-content');
    const errorMessage = document.getElementById('error-message');
    const retryBtn = document.getElementById('retry-btn');

    let allDailyClicks = [];
    let allRecentClicks = [];
    let visibleClicksCount = 20;
    let chartInstance = null;
    let currentRange = 30;

    // State management
    function showLoading() {
        loadingState.classList.remove('hidden');
        errorState.classList.add('hidden');
        analyticsContent.classList.add('hidden');
    }

    function showError(message) {
        loadingState.classList.add('hidden');
        errorState.classList.remove('hidden');
        analyticsContent.classList.add('hidden');
        if (errorMessage) errorMessage.textContent = message;
    }

    function showContent() {
        loadingState.classList.add('hidden');
        errorState.classList.add('hidden');
        analyticsContent.classList.remove('hidden');
    }

    // Header copy button
    const headerCopyBtn = document.getElementById('header-copy-btn');
    const headerCopyLabel = document.getElementById('header-copy-label');
    if (headerCopyBtn) {
        headerCopyBtn.addEventListener('click', () => {
            const appUrl = window.APP_URL || window.location.origin;
            const shortUrl = `${appUrl}/${slug}`;
            copyToClipboard(shortUrl, headerCopyBtn, headerCopyLabel);
        });
    }

    // Populate header fields
    function populateHeader(data) {
        const appUrl = window.APP_URL || window.location.origin;
        const shortUrl = `${appUrl}/${slug}`;

        const headerShortUrl = document.getElementById('header-short-url');
        if (headerShortUrl) headerShortUrl.textContent = shortUrl.replace(/^https?:\/\//, '');

        const headerOriginalUrl = document.getElementById('header-original-url');
        const headerOriginalUrlText = document.getElementById('header-original-url-text');
        if (headerOriginalUrl && data.original_url) {
            headerOriginalUrl.href = data.original_url;
            if (headerOriginalUrlText) headerOriginalUrlText.textContent = data.original_url;
        }

        const statusBadge = document.getElementById('status-badge');
        if (statusBadge) {
            const isExpired = data.expires_at && new Date(data.expires_at) < new Date();
            if (isExpired) {
                statusBadge.textContent = 'Expired';
                statusBadge.className = 'inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold shrink-0 bg-red-100 text-red-700';
            } else {
                statusBadge.textContent = 'Active';
                statusBadge.className = 'inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold shrink-0 bg-green-100 text-green-700';
            }
        }

        const metaCreatedAt = document.getElementById('meta-created-at');
        if (metaCreatedAt) metaCreatedAt.textContent = formatDate(data.created_at);

        const metaExpiresRow = document.getElementById('meta-expires-row');
        const metaExpiresAt = document.getElementById('meta-expires-at');
        if (data.expires_at && metaExpiresRow && metaExpiresAt) {
            metaExpiresAt.textContent = formatDate(data.expires_at);
            metaExpiresRow.classList.remove('hidden');
        } else if (metaExpiresRow) {
            metaExpiresRow.classList.add('hidden');
        }
    }

    // Populate KPI cards
    function populateKPIs(summary) {
        const kpiTotal = document.getElementById('kpi-total-clicks');
        const kpiToday = document.getElementById('kpi-clicks-today');
        const kpiUnique = document.getElementById('kpi-unique-ips');
        const kpiLast = document.getElementById('kpi-last-click');

        if (kpiTotal) kpiTotal.textContent = (summary.total_clicks ?? 0).toLocaleString();
        if (kpiToday) kpiToday.textContent = (summary.clicks_today ?? 0).toLocaleString();
        if (kpiUnique) kpiUnique.textContent = (summary.unique_ips ?? 0).toLocaleString();
        if (kpiLast) kpiLast.textContent = summary.last_click_at ? relativeTime(summary.last_click_at) : 'Never';
    }

    // Build the daily clicks bar chart using vanilla Canvas API (no external chart library)
    function buildChart(dailyClicks, rangeDays) {
        const chartContainer = document.getElementById('chart-container');
        const chartEmpty = document.getElementById('chart-empty');
        const canvas = document.getElementById('clicks-chart');
        const chartTableBody = document.getElementById('chart-table-body');

        // Filter to last N days
        const cutoff = new Date();
        cutoff.setDate(cutoff.getDate() - rangeDays);
        const filtered = dailyClicks.filter(d => new Date(d.date) >= cutoff);

        // Fill missing days with zero
        const dataMap = {};
        filtered.forEach(d => { dataMap[d.date] = d.count; });

        const labels = [];
        const values = [];
        for (let i = rangeDays - 1; i >= 0; i--) {
            const d = new Date();
            d.setDate(d.getDate() - i);
            const dateStr = d.toISOString().split('T')[0];
            labels.push(dateStr);
            values.push(dataMap[dateStr] || 0);
        }

        const hasData = values.some(v => v > 0);

        if (!hasData) {
            chartContainer.classList.add('hidden');
            chartEmpty.classList.remove('hidden');
        } else {
            chartContainer.classList.remove('hidden');
            chartEmpty.classList.add('hidden');
        }

        // Populate accessible table
        if (chartTableBody) {
            chartTableBody.innerHTML = labels.map((label, i) =>
                `<tr class="border-b border-neutral-100">
                    <td class="py-1.5 pr-4">${label}</td>
                    <td class="py-1.5 text-right">${values[i].toLocaleString()}</td>
                </tr>`
            ).join('');
        }

        if (!hasData) return;

        // Draw vanilla Canvas bar chart
        const dpr = window.devicePixelRatio || 1;
        const containerRect = chartContainer.getBoundingClientRect();
        const W = containerRect.width || 800;
        const H = 260;

        canvas.width = W * dpr;
        canvas.height = H * dpr;
        canvas.style.width = W + 'px';
        canvas.style.height = H + 'px';

        const ctx = canvas.getContext('2d');
        ctx.scale(dpr, dpr);

        const padding = { top: 20, right: 16, bottom: 60, left: 50 };
        const chartW = W - padding.left - padding.right;
        const chartH = H - padding.top - padding.bottom;

        const maxValue = Math.max(...values, 1);

        ctx.clearRect(0, 0, W, H);

        // Y-axis gridlines
        const ySteps = 4;
        ctx.strokeStyle = '#E5E7EB';
        ctx.lineWidth = 1;
        ctx.setLineDash([3, 3]);
        ctx.font = '11px ui-sans-serif, system-ui, sans-serif';
        ctx.fillStyle = '#6B7280';
        ctx.textAlign = 'right';

        for (let i = 0; i <= ySteps; i++) {
            const yVal = Math.round((maxValue / ySteps) * i);
            const y = padding.top + chartH - (chartH * i / ySteps);
            ctx.beginPath();
            ctx.moveTo(padding.left, y);
            ctx.lineTo(padding.left + chartW, y);
            ctx.stroke();
            ctx.fillText(yVal.toString(), padding.left - 8, y + 4);
        }

        ctx.setLineDash([]);

        // Bars
        const barCount = labels.length;
        const barGap = 2;
        const barW = Math.max((chartW / barCount) - barGap, 2);

        values.forEach((val, i) => {
            const barH = (val / maxValue) * chartH;
            const x = padding.left + i * (barW + barGap);
            const y = padding.top + chartH - barH;

            ctx.fillStyle = '#4F46E5';
            ctx.beginPath();
            ctx.roundRect(x, y, barW, barH, [3, 3, 0, 0]);
            ctx.fill();
        });

        // X-axis labels — show every Nth label to avoid overlap
        const labelStep = Math.ceil(barCount / 10);
        ctx.fillStyle = '#6B7280';
        ctx.textAlign = 'center';
        ctx.font = '11px ui-sans-serif, system-ui, sans-serif';

        labels.forEach((label, i) => {
            if (i % labelStep !== 0 && i !== barCount - 1) return;
            const x = padding.left + i * (barW + barGap) + barW / 2;
            const y = padding.top + chartH + 18;
            const shortLabel = label.slice(5); // MM-DD
            ctx.save();
            ctx.translate(x, y);
            ctx.rotate(-Math.PI / 6);
            ctx.fillText(shortLabel, 0, 0);
            ctx.restore();
        });

        // Tooltip tracking on hover
        canvas.title = 'Hover over a bar to see click count';
    }

    // Range selector buttons
    document.querySelectorAll('.range-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const range = parseInt(btn.dataset.range, 10);
            currentRange = range;
            document.querySelectorAll('.range-btn').forEach(b => b.classList.remove('active-range'));
            btn.classList.add('active-range');
            buildChart(allDailyClicks, currentRange);
        });
    });

    // Populate recent clicks table
    function renderRecentClicks() {
        const tbody = document.getElementById('recent-clicks-body');
        const clicksEmpty = document.getElementById('clicks-empty');
        const loadMoreContainer = document.getElementById('load-more-container');

        if (!tbody) return;

        if (!allRecentClicks.length) {
            tbody.innerHTML = '';
            if (clicksEmpty) clicksEmpty.classList.remove('hidden');
            if (loadMoreContainer) loadMoreContainer.classList.add('hidden');
            return;
        }

        if (clicksEmpty) clicksEmpty.classList.add('hidden');

        const visible = allRecentClicks.slice(0, visibleClicksCount);
        tbody.innerHTML = visible.map(click => {
            const timeTitle = formatDate(click.clicked_at);
            const relTime = relativeTime(click.clicked_at);
            const ip = click.ip_masked || 'N/A';
            const ua = click.user_agent_parsed || 'Unknown';
            return `<tr class="border-b border-neutral-100 hover:bg-neutral-50 transition-colors">
                <td class="py-3 pr-4">
                    <span title="${timeTitle}" class="cursor-default">${relTime}</span>
                </td>
                <td class="py-3 pr-4 font-mono text-xs text-neutral-700">${ip}</td>
                <td class="py-3 text-neutral-600">${ua}</td>
            </tr>`;
        }).join('');

        if (allRecentClicks.length > visibleClicksCount) {
            if (loadMoreContainer) loadMoreContainer.classList.remove('hidden');
        } else {
            if (loadMoreContainer) loadMoreContainer.classList.add('hidden');
        }
    }

    // Load more button
    const loadMoreBtn = document.getElementById('load-more-btn');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', () => {
            visibleClicksCount += 20;
            renderRecentClicks();
        });
    }

    // Fetch analytics data
    async function fetchAnalytics() {
        showLoading();
        try {
            const response = await fetch(`/api/links/${encodeURIComponent(slug)}/analytics`, {
                headers: { 'Accept': 'application/json' },
            });

            if (!response.ok) {
                const data = await response.json().catch(() => ({}));
                showError(data.message || `Failed to load analytics (HTTP ${response.status}).`);
                return;
            }

            const data = await response.json();

            populateHeader(data);
            populateKPIs(data);

            allDailyClicks = data.daily_clicks || [];
            allRecentClicks = data.recent_clicks || [];

            showContent();
            buildChart(allDailyClicks, currentRange);
            renderRecentClicks();

        } catch {
            showError('Network error. Please check your connection and try again.');
        }
    }

    // Retry button
    if (retryBtn) {
        retryBtn.addEventListener('click', fetchAnalytics);
    }

    fetchAnalytics();
}

// ---------------------------------------------------------------------------
// Boot
// ---------------------------------------------------------------------------

document.addEventListener('DOMContentLoaded', () => {
    initShortenForm();
    initAnalyticsPage();
});
