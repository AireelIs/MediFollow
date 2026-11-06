<?php
// Shared animated appearance button. The SVG shows the current appearance.
if (!defined('MEDIFOLLOW_LOGIN') && !defined('MEDIFOLLOW_DASHBOARD')
    && !defined('MEDIFOLLOW_ACCOUNTS') && !defined('MEDIFOLLOW_RECORD') && !defined('MEDIFOLLOW_MESSAGES')) {
    http_response_code(404);
    exit;
}
?>
<button type="button" class="theme-toggle" id="theme-toggle" aria-label="Switch to dark mode" aria-pressed="false" title="Switch to dark mode">
    <span class="theme-thumb" aria-hidden="true">
    <svg class="theme-icon" width="24" height="24" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
        <defs>
            <mask id="theme-moon-mask" maskUnits="userSpaceOnUse" x="0" y="0" width="24" height="24">
                <rect width="24" height="24" fill="white" />
                <circle class="theme-moon-cutout" cx="24" cy="0" r="6" fill="black" />
            </mask>
        </defs>
        <g class="theme-orb" mask="url(#theme-moon-mask)">
            <circle class="theme-disc" cx="12" cy="12" r="5" fill="currentColor" />
        </g>
        <g class="theme-rays" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round">
            <path d="M12 2v2M12 20v2M2 12h2M20 12h2M4.93 4.93l1.42 1.42M17.65 17.65l1.42 1.42M4.93 19.07l1.42-1.42M17.65 6.35l1.42-1.42" />
        </g>
    </svg>
    </span>
    <span class="theme-label">Dark mode</span>
</button>
