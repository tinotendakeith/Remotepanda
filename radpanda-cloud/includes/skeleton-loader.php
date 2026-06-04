<?php
if (defined('RADPANDA_CLOUD_SKELETON_LOADED')) {
    return;
}
define('RADPANDA_CLOUD_SKELETON_LOADED', true);
?>
<style>
    .rpc-skeleton-overlay {
        position: fixed;
        inset: 0;
        z-index: 99999;
        display: none;
        pointer-events: none;
        background: #eef5fd;
    }
    .rpc-skeleton-overlay.is-visible { display: block; }
    .rpc-skeleton-progress {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        overflow: hidden;
        background: rgba(13, 58, 105, .08);
    }
    .rpc-skeleton-progress:before {
        content: "";
        display: block;
        width: 45%;
        height: 100%;
        background: linear-gradient(90deg, #ef1d2f, #0c7c78);
        animation: rpcSkeletonBar 1.1s ease-in-out infinite;
    }
    .rpc-skeleton-shell {
        margin-left: 250px;
        padding: 28px;
    }
    .rpc-skeleton-top {
        height: 94px;
        margin: -28px -28px 28px;
        background: #fff;
        border-bottom: 1px solid #dce7f4;
    }
    .rpc-skeleton-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 18px;
    }
    .rpc-skeleton-body {
        display: grid;
        grid-template-columns: minmax(0, 1.5fr) minmax(320px, .75fr);
        gap: 18px;
    }
    .rpc-skeleton-card,
    .rpc-skeleton-panel {
        background: #fff;
        border: 1px solid #cfe0f3;
        border-radius: 12px;
        overflow: hidden;
        min-height: 128px;
        box-shadow: 0 10px 24px rgba(6, 26, 51, .04);
    }
    .rpc-skeleton-panel { min-height: 330px; padding: 22px; }
    .rpc-skeleton-line,
    .rpc-skeleton-tile {
        position: relative;
        overflow: hidden;
        border-radius: 999px;
        background: #e6eef8;
    }
    .rpc-skeleton-card { padding: 18px; }
    .rpc-skeleton-line { height: 13px; margin: 0 0 14px; }
    .rpc-skeleton-line.short { width: 34%; }
    .rpc-skeleton-line.medium { width: 58%; }
    .rpc-skeleton-line.long { width: 84%; }
    .rpc-skeleton-tile { height: 54px; border-radius: 10px; margin-top: 18px; }
    .rpc-skeleton-line:after,
    .rpc-skeleton-tile:after {
        content: "";
        position: absolute;
        inset: 0;
        transform: translateX(-100%);
        background: linear-gradient(90deg, transparent, rgba(255,255,255,.75), transparent);
        animation: rpcSkeletonSweep 1.25s infinite;
    }
    .rpc-skeleton-button-loading {
        position: relative;
        color: transparent !important;
    }
    .rpc-skeleton-button-loading:after {
        content: "";
        position: absolute;
        width: 16px;
        height: 16px;
        border: 2px solid rgba(255,255,255,.65);
        border-top-color: #fff;
        border-radius: 50%;
        animation: rpcSkeletonSpin .7s linear infinite;
    }
    @keyframes rpcSkeletonSweep { to { transform: translateX(100%); } }
    @keyframes rpcSkeletonBar { 0% { transform: translateX(-110%); } 100% { transform: translateX(250%); } }
    @keyframes rpcSkeletonSpin { to { transform: rotate(360deg); } }
    @media (max-width: 850px) {
        .rpc-skeleton-shell { margin-left: 0; padding: 18px; }
        .rpc-skeleton-grid, .rpc-skeleton-body { grid-template-columns: 1fr; }
    }
</style>
<div class="rpc-skeleton-overlay" id="rpcSkeletonOverlay" aria-hidden="true">
    <div class="rpc-skeleton-progress"></div>
    <div class="rpc-skeleton-shell">
        <div class="rpc-skeleton-top"></div>
        <div class="rpc-skeleton-grid">
            <div class="rpc-skeleton-card"><div class="rpc-skeleton-line short"></div><div class="rpc-skeleton-tile"></div></div>
            <div class="rpc-skeleton-card"><div class="rpc-skeleton-line short"></div><div class="rpc-skeleton-tile"></div></div>
            <div class="rpc-skeleton-card"><div class="rpc-skeleton-line short"></div><div class="rpc-skeleton-tile"></div></div>
            <div class="rpc-skeleton-card"><div class="rpc-skeleton-line short"></div><div class="rpc-skeleton-tile"></div></div>
        </div>
        <div class="rpc-skeleton-body">
            <div class="rpc-skeleton-panel">
                <div class="rpc-skeleton-line medium"></div>
                <div class="rpc-skeleton-line long"></div>
                <div class="rpc-skeleton-line long"></div>
                <div class="rpc-skeleton-line medium"></div>
            </div>
            <div class="rpc-skeleton-panel">
                <div class="rpc-skeleton-line medium"></div>
                <div class="rpc-skeleton-line long"></div>
                <div class="rpc-skeleton-line long"></div>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    if (window.RadpandaCloudSkeleton) return;
    var overlay;
    function ready(fn) {
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn);
        else fn();
    }
    function show() {
        overlay = overlay || document.getElementById('rpcSkeletonOverlay');
        if (overlay) overlay.classList.add('is-visible');
    }
    function hide() {
        overlay = overlay || document.getElementById('rpcSkeletonOverlay');
        if (overlay) overlay.classList.remove('is-visible');
        document.querySelectorAll('.rpc-skeleton-button-loading').forEach(function (btn) {
            btn.classList.remove('rpc-skeleton-button-loading');
        });
    }
    function shouldIgnoreLink(link) {
        var href = link.getAttribute('href') || '';
        return !href || href === '#' || href.indexOf('javascript:') === 0 || link.target === '_blank' ||
            link.hasAttribute('download') || link.dataset.noSkeleton === '1' ||
            href.charAt(0) === '#';
    }
    ready(function () {
        document.addEventListener('click', function (event) {
            var link = event.target.closest ? event.target.closest('a') : null;
            if (link && !shouldIgnoreLink(link)) show();
        }, true);
        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (form && form.dataset && form.dataset.noSkeleton === '1') return;
            var button = form.querySelector('button[type="submit"],input[type="submit"]');
            if (button) button.classList.add('rpc-skeleton-button-loading');
            show();
        }, true);
        window.addEventListener('pageshow', hide);
        window.addEventListener('beforeunload', show);
    });
    window.RadpandaCloudSkeleton = { show: show, hide: hide };
})();
</script>
