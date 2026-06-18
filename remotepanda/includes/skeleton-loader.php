<?php
if (defined('RADPANDA_SKELETON_LOADER_LOADED')) {
    return;
}
define('RADPANDA_SKELETON_LOADER_LOADED', true);
?>
<style>
.rp-skeleton-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 99998;
    display: none;
    pointer-events: none;
}
.rp-skeleton-overlay.is-visible {
    display: block;
}
.rp-skeleton-progress {
    height: 3px;
    overflow: hidden;
    background: rgba(215, 228, 245, .78);
    box-shadow: 0 1px 8px rgba(8, 34, 72, .14);
}
.rp-skeleton-progress:before {
    content: "";
    display: block;
    width: 34%;
    height: 100%;
    border-radius: 999px;
    background: linear-gradient(90deg, #ed1b24, #2f8fd7, #12b981);
    animation: rpSkeletonBar 1s ease-in-out infinite;
}
.rp-skeleton-toast {
    position: fixed;
    top: 14px;
    right: 18px;
    display: inline-flex;
    align-items: center;
    gap: 9px;
    max-width: calc(100vw - 36px);
    padding: 9px 13px;
    border: 1px solid #cfe0f5;
    border-radius: 999px;
    background: rgba(255, 255, 255, .96);
    color: #06213d;
    font-size: 13px;
    font-weight: 700;
    box-shadow: 0 12px 28px rgba(8, 34, 72, .14);
}
.rp-skeleton-toast:before {
    content: "";
    width: 14px;
    height: 14px;
    border: 2px solid #d7e4f5;
    border-top-color: #ed1b24;
    border-radius: 50%;
    animation: rpSkeletonSpin .7s linear infinite;
    flex: 0 0 auto;
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
    min-height: 86px;
    border: 1px dashed #c8ddf5;
    border-radius: 10px;
    padding: 14px;
    background: #f7fbff;
}
.rp-skeleton-line {
    position: relative;
    overflow: hidden;
    height: 10px;
    margin: 10px 0;
    border-radius: 999px;
    background: #edf4fc;
}
.rp-skeleton-line:after {
    content: "";
    position: absolute;
    inset: 0;
    transform: translateX(-100%);
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.8), transparent);
    animation: rpSkeletonSweep 1.25s ease-in-out infinite;
}
.rp-skeleton-line.medium { width: 62%; }
.rp-skeleton-line.long { width: 88%; }
@keyframes rpSkeletonSweep {
    100% { transform: translateX(100%); }
}
@keyframes rpSkeletonBar {
    0% { transform: translateX(-75%); }
    100% { transform: translateX(320%); }
}
@keyframes rpSkeletonSpin {
    to { transform: rotate(360deg); }
}
@media (max-width: 700px) {
    .rp-skeleton-toast {
        top: 10px;
        right: 10px;
        left: 10px;
        justify-content: center;
    }
}
</style>
<div class="rp-skeleton-overlay" id="rpSkeletonOverlay" aria-live="polite" aria-hidden="true">
    <div class="rp-skeleton-progress"></div>
    <div class="rp-skeleton-toast" id="rpSkeletonToast">Loading...</div>
</div>
<script>
(function () {
    if (window.RadpandaSkeleton) {
        return;
    }
    var overlay;
    var toast;
    var timer;
    function getOverlay() {
        overlay = overlay || document.getElementById('rpSkeletonOverlay');
        return overlay;
    }
    function getToast() {
        toast = toast || document.getElementById('rpSkeletonToast');
        return toast;
    }
    function show(delay, message) {
        clearTimeout(timer);
        timer = setTimeout(function () {
            var el = getOverlay();
            var label = getToast();
            if (label) {
                label.textContent = message || 'Loading...';
            }
            if (el) {
                el.classList.add('is-visible');
                el.setAttribute('aria-hidden', 'false');
            }
        }, typeof delay === 'number' ? delay : 120);
    }
    function hide() {
        clearTimeout(timer);
        var el = getOverlay();
        if (el) {
            el.classList.remove('is-visible');
            el.setAttribute('aria-hidden', 'true');
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
        var count = rows || 3;
        var html = '';
        for (var i = 0; i < count; i++) {
            html += '<div class="rp-skeleton-line ' + (i % 2 === 0 ? 'long' : 'medium') + '"></div>';
        }
        target.innerHTML = html;
    }
    window.RadpandaSkeleton = { show: show, hide: hide, within: localSkeleton };
    document.addEventListener('click', function (event) {
        var link = event.target.closest ? event.target.closest('a') : null;
        if (link && !shouldIgnoreLink(link)) {
            show(120, 'Opening...');
            return;
        }
        var button = event.target.closest ? event.target.closest('button,input[type="submit"]') : null;
        if (button && button.type === 'submit' && !button.dataset.noSkeleton) {
            button.classList.add('rp-skeleton-button-loading');
            show(180, 'Saving...');
        }
    }, true);
    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (form && form.dataset && form.dataset.noSkeleton === '1') return;
        show(180, 'Saving...');
    }, true);
    window.addEventListener('pageshow', hide);
    window.addEventListener('beforeunload', function () { show(0, 'Opening...'); });
})();
</script>
