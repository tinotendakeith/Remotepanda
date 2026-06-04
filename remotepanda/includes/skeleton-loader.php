<?php
if (defined('RADPANDA_SKELETON_LOADER_LOADED')) {
    return;
}
define('RADPANDA_SKELETON_LOADER_LOADED', true);
?>
<style>
.rp-skeleton-overlay {
    position: fixed;
    inset: 0;
    z-index: 99998;
    display: none;
    pointer-events: none;
    background: rgba(239, 246, 255, .86);
    backdrop-filter: blur(2px);
}
.rp-skeleton-overlay.is-visible {
    display: block;
}
.rp-skeleton-progress {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    overflow: hidden;
    background: rgba(215, 228, 245, .8);
}
.rp-skeleton-progress:before {
    content: "";
    display: block;
    width: 42%;
    height: 100%;
    border-radius: 999px;
    background: linear-gradient(90deg, #ed1b24, #2f8fd7, #12b981);
    animation: rpSkeletonBar 1s ease-in-out infinite;
}
.rp-skeleton-shell {
    margin: 118px 24px 24px 260px;
    max-width: 1420px;
}
.rp-skeleton-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(160px, 1fr));
    gap: 18px;
    margin-bottom: 22px;
}
.rp-skeleton-body {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(280px, .95fr);
    gap: 18px;
}
.rp-skeleton-panel,
.rp-skeleton-card {
    border: 1px solid #d7e4f5;
    border-radius: 10px;
    background: rgba(255, 255, 255, .95);
    box-shadow: 0 14px 35px rgba(8, 34, 72, .08);
    padding: 18px;
}
.rp-skeleton-line,
.rp-skeleton-tile {
    position: relative;
    overflow: hidden;
    border-radius: 10px;
    background: #edf4fc;
}
.rp-skeleton-line:after,
.rp-skeleton-tile:after,
.rp-skeleton-card:after {
    content: "";
    position: absolute;
    inset: 0;
    transform: translateX(-100%);
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.8), transparent);
    animation: rpSkeletonSweep 1.25s ease-in-out infinite;
}
.rp-skeleton-line {
    height: 13px;
    margin: 11px 0;
}
.rp-skeleton-line.short { width: 38%; }
.rp-skeleton-line.medium { width: 62%; }
.rp-skeleton-line.long { width: 88%; }
.rp-skeleton-tile {
    height: 46px;
    margin-bottom: 12px;
}
.rp-skeleton-button-loading {
    position: relative;
    color: transparent !important;
    pointer-events: none;
}
.rp-skeleton-button-loading:after {
    content: "";
    position: absolute;
    width: 15px;
    height: 15px;
    left: calc(50% - 7.5px);
    top: calc(50% - 7.5px);
    border: 2px solid rgba(255,255,255,.65);
    border-top-color: #fff;
    border-radius: 50%;
    animation: rpSkeletonSpin .65s linear infinite;
}
.rp-skeleton-local {
    min-height: 120px;
    border: 1px dashed #c8ddf5;
    border-radius: 10px;
    padding: 14px;
    background: #f7fbff;
}
@keyframes rpSkeletonSweep {
    100% { transform: translateX(100%); }
}
@keyframes rpSkeletonBar {
    0% { transform: translateX(-60%); }
    100% { transform: translateX(240%); }
}
@keyframes rpSkeletonSpin {
    to { transform: rotate(360deg); }
}
@media (max-width: 900px) {
    .rp-skeleton-shell { margin: 110px 14px 14px 14px; }
    .rp-skeleton-grid,
    .rp-skeleton-body { grid-template-columns: 1fr; }
}
</style>
<div class="rp-skeleton-overlay" id="rpSkeletonOverlay" aria-hidden="true">
    <div class="rp-skeleton-progress"></div>
    <div class="rp-skeleton-shell">
        <div class="rp-skeleton-grid">
            <div class="rp-skeleton-card"><div class="rp-skeleton-line short"></div><div class="rp-skeleton-line medium"></div></div>
            <div class="rp-skeleton-card"><div class="rp-skeleton-line short"></div><div class="rp-skeleton-line medium"></div></div>
            <div class="rp-skeleton-card"><div class="rp-skeleton-line short"></div><div class="rp-skeleton-line medium"></div></div>
            <div class="rp-skeleton-card"><div class="rp-skeleton-line short"></div><div class="rp-skeleton-line medium"></div></div>
        </div>
        <div class="rp-skeleton-body">
            <div class="rp-skeleton-panel">
                <div class="rp-skeleton-line medium"></div>
                <div class="rp-skeleton-tile"></div>
                <div class="rp-skeleton-tile"></div>
                <div class="rp-skeleton-tile"></div>
                <div class="rp-skeleton-line long"></div>
            </div>
            <div class="rp-skeleton-panel">
                <div class="rp-skeleton-line medium"></div>
                <div class="rp-skeleton-tile"></div>
                <div class="rp-skeleton-tile"></div>
                <div class="rp-skeleton-line short"></div>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    if (window.RadpandaSkeleton) {
        return;
    }
    var overlay;
    var timer;
    function getOverlay() {
        overlay = overlay || document.getElementById('rpSkeletonOverlay');
        return overlay;
    }
    function show(delay) {
        clearTimeout(timer);
        timer = setTimeout(function () {
            var el = getOverlay();
            if (el) {
                el.classList.add('is-visible');
            }
        }, typeof delay === 'number' ? delay : 120);
    }
    function hide() {
        clearTimeout(timer);
        var el = getOverlay();
        if (el) {
            el.classList.remove('is-visible');
        }
        var loadingButtons = document.querySelectorAll('.rp-skeleton-button-loading');
        for (var i = 0; i < loadingButtons.length; i++) {
            loadingButtons[i].classList.remove('rp-skeleton-button-loading');
        }
    }
    function shouldIgnoreLink(link) {
        if (!link || !link.href) return true;
        var href = link.getAttribute('href') || '';
        var target = link.getAttribute('target') || '';
        if (target === '_blank' || href === '' || href === '#' || href.indexOf('javascript:') === 0) return true;
        if (link.hasAttribute('download') || link.dataset.noSkeleton === '1') return true;
        if (link.classList.contains('dropdown-toggle') || link.classList.contains('js-open-messaging')) return true;
        if (href.charAt(0) === '#' || href.indexOf('#') === 0) return true;
        if (link.dataset.toggle || link.getAttribute('data-toggle')) return true;
        return false;
    }
    function localSkeleton(target, rows) {
        if (!target) return;
        target.classList.add('rp-skeleton-local');
        var count = rows || 4;
        var html = '';
        for (var i = 0; i < count; i++) {
            html += '<div class="rp-skeleton-line ' + (i % 3 === 0 ? 'long' : 'medium') + '"></div>';
        }
        target.innerHTML = html;
    }
    window.RadpandaSkeleton = { show: show, hide: hide, within: localSkeleton };
    document.addEventListener('click', function (event) {
        var link = event.target.closest ? event.target.closest('a') : null;
        if (link && !shouldIgnoreLink(link)) {
            show();
            return;
        }
        var button = event.target.closest ? event.target.closest('button,input[type="submit"]') : null;
        if (button && button.type === 'submit' && !button.dataset.noSkeleton) {
            button.classList.add('rp-skeleton-button-loading');
            show(180);
        }
    }, true);
    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (form && form.dataset && form.dataset.noSkeleton === '1') return;
        show(180);
    }, true);
    window.addEventListener('pageshow', hide);
    window.addEventListener('beforeunload', function () { show(0); });
})();
</script>
