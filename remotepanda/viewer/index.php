<?php
$studyint = isset($_GET['studyint']) ? trim((string)$_GET['studyint']) : '';
$embed = isset($_GET['embed']) && $_GET['embed'] === '1';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Radpanda DICOM Viewer</title>
<style>
html,body{margin:0;padding:0;height:100%;font-family:Arial,sans-serif;background:#0b1220;color:#e5e7eb}
.toolbar{display:flex;align-items:center;gap:6px;flex-wrap:wrap;padding:8px 10px;background:#111827;border-bottom:1px solid #1f2937}
.btn{border:1px solid #334155;background:#1f2937;color:#e5e7eb;border-radius:6px;padding:6px 10px;font-size:12px;cursor:pointer}
.btn.active{background:#1e3a8a;border-color:#60a5fa}.btn:disabled{opacity:.5;cursor:not-allowed}
.status{font-size:12px;color:#9ca3af}
.wrap{height:calc(100% - 52px);display:grid;grid-template-columns:180px 1fr 280px;min-height:0}
.left{border-right:1px solid #1f2937;background:#0f172a;overflow:auto}
main.viewer-main{display:flex;min-height:0;height:100%}
#viewer{flex:1 1 auto;margin:8px;border:1px solid #1f2937;background:#000;min-height:200px;height:calc(100vh - 84px);position:relative;overflow:hidden;touch-action:none}
#rpMeasureOverlay{position:absolute;inset:0;width:100%;height:100%;pointer-events:none;z-index:20}
#rpMeasureOverlay.active{pointer-events:auto}
.side{border-left:1px solid #1f2937;background:#0f172a;padding:10px;overflow:auto}
.h{padding:10px 12px;border-bottom:1px solid #1f2937;font-weight:600;font-size:13px}
#seriesList{padding:8px}
.series{padding:8px;border:1px solid #2b3b52;border-radius:6px;background:#0c1a30;margin-bottom:8px;cursor:pointer}
.series.active{border-color:#60a5fa;background:#132340}
.series .a{font-size:12px}.series .b{font-size:11px;color:#94a3b8;margin-top:3px}
.meta-row{display:grid;grid-template-columns:90px 1fr;gap:8px;margin-bottom:6px;font-size:12px}
.meta-row .k{color:#93c5fd}
body.embed .toolbar{padding:6px 8px}
body.embed .wrap{height:calc(100% - 44px);grid-template-columns:1fr}
body.embed .left,body.embed .side{display:none}
body.embed main.viewer-main{height:100%;min-height:0}
body.embed #viewer{height:calc(100vh - 58px);margin:6px;min-height:0}
body.embed .status{margin-left:auto}
@media (max-width:980px){.wrap{grid-template-columns:1fr;grid-template-rows:auto 1fr auto}.left{border-right:none;border-bottom:1px solid #1f2937}.side{border-left:none;border-top:1px solid #1f2937}}
</style>
</head>
<body class="<?php echo $embed ? 'embed' : ''; ?>">
<div class="toolbar">
  <button id="prev" class="btn" disabled>Prev</button>
  <button id="next" class="btn" disabled>Next</button>
  <button class="btn tool active" data-tool="Wwwc">WW/WL</button>
  <button class="btn tool" data-tool="Pan">Pan</button>
  <button class="btn tool" data-tool="Zoom">Zoom</button>
  <button class="btn tool" data-tool="Length">Distance</button>
  <button class="btn tool" data-tool="RectangleRoi">ROI</button>
  <button id="reset" class="btn">Reset</button>
  <span id="status" class="status">Loading viewer...</span>
</div>
<div class="wrap">
  <aside class="left">
    <div class="h">Series</div>
    <div id="seriesList"></div>
  </aside>
  <main class="viewer-main"><div id="viewer"></div></main>
  <aside class="side">
    <h4 style="margin:0 0 10px 0;">Study Metadata</h4>
    <div id="meta"></div>
  </aside>
</div>
<script>
(function(){
  const studyint = <?php echo json_encode($studyint, JSON_UNESCAPED_SLASHES); ?>;
  const viewerExp = <?php echo json_encode(isset($_GET['viewer_exp']) ? (string)$_GET['viewer_exp'] : ''); ?>;
  const viewerToken = <?php echo json_encode(isset($_GET['viewer_token']) ? (string)$_GET['viewer_token'] : ''); ?>;
  const viewerAuthQuery = viewerExp && viewerToken
    ? '&viewer_exp=' + encodeURIComponent(viewerExp) + '&viewer_token=' + encodeURIComponent(viewerToken)
    : '';
  const statusEl = document.getElementById('status');
  const metaEl = document.getElementById('meta');
  const prevBtn = document.getElementById('prev');
  const nextBtn = document.getElementById('next');
  const viewer = document.getElementById('viewer');
  const seriesListEl = document.getElementById('seriesList');
  const toolBtns = Array.from(document.querySelectorAll('.tool'));
  const resetBtn = document.getElementById('reset');

  let activeImageIds = [];
  let index = 0;
  let toolsReady = false;
  let currentTool = 'Wwwc';
  let isDragging = false;
  let lastPointer = null;
  let measureOverlay = null;
  let activeMeasure = null;
  let seriesEntries = [];
  let currentSeriesIndex = 0;

  function setStatus(t){ statusEl.textContent = t; }
  function disableTools(){
    toolBtns.forEach(function(btn){
      btn.classList.remove('active');
    });
  }

  function scriptWithFallback(urls, done){
    let i = 0;
    function tryNext(){
      if(i >= urls.length){ done(false); return; }
      const s = document.createElement('script');
      s.src = urls[i++];
      let settled = false;
      const timer = setTimeout(function(){ if(settled) return; settled = true; s.remove(); tryNext(); }, 3500);
      s.onload = function(){ if(settled) return; settled = true; clearTimeout(timer); done(true); };
      s.onerror = function(){ if(settled) return; settled = true; clearTimeout(timer); tryNext(); };
      document.head.appendChild(s);
    }
    tryNext();
  }

  const viewerAppBaseUrl = (function(){
    const marker = '/viewer/';
    const path = window.location.pathname || '';
    const pos = path.indexOf(marker);
    return pos >= 0 ? path.slice(0, pos) : '';
  })();

  function loadLibraries(cb){
    const required = [
      ['https://cdn.jsdelivr.net/npm/dicom-parser@1.8.21/dist/dicomParser.min.js','https://unpkg.com/dicom-parser@1.8.21/dist/dicomParser.min.js'],
      ['https://cdn.jsdelivr.net/npm/cornerstone-core@2.6.1/dist/cornerstone.min.js','https://unpkg.com/cornerstone-core@2.6.1/dist/cornerstone.min.js'],
      ['https://cdn.jsdelivr.net/npm/cornerstone-wado-image-loader@3.2.0/dist/cornerstoneWADOImageLoader.js','https://unpkg.com/cornerstone-wado-image-loader@3.2.0/dist/cornerstoneWADOImageLoader.js','https://cdnjs.cloudflare.com/ajax/libs/cornerstone-wado-image-loader/3.1.0/cornerstoneWADOImageLoader.js','https://cdnjs.cloudflare.com/ajax/libs/cornerstone-wado-image-loader/2.1.1/cornerstoneWADOImageLoader.js', viewerAppBaseUrl + '/viewer/vendor/cornerstoneWADOImageLoader.bundle.min.js']
    ];
    const optional = [
      ['https://cdn.jsdelivr.net/npm/hammerjs@2.0.8/hammer.min.js','https://unpkg.com/hammerjs@2.0.8/hammer.min.js'],
      ['https://cdn.jsdelivr.net/npm/cornerstone-math@0.1.10/dist/cornerstoneMath.min.js','https://unpkg.com/cornerstone-math@0.1.10/dist/cornerstoneMath.min.js'],
      ['https://cdn.jsdelivr.net/npm/cornerstone-tools@4.22.1/dist/cornerstoneTools.min.js','https://unpkg.com/cornerstone-tools@4.22.1/dist/cornerstoneTools.min.js']
    ];

    let pos = 0;
    function loadRequired(){
      if(pos >= required.length){ pos = 0; loadOptional(); return; }
      setStatus('Loading required library ' + (pos + 1) + ' / ' + required.length + '...');
      scriptWithFallback(required[pos], function(ok){ if(!ok){ cb(false); return; } pos += 1; loadRequired(); });
    }
    function loadOptional(){
      if(pos >= optional.length){ cb(true); return; }
      setStatus('Loading optional library ' + (pos + 1) + ' / ' + optional.length + '...');
      scriptWithFallback(optional[pos], function(){ pos += 1; loadOptional(); });
    }
    loadRequired();
  }

  function esc(v){
    return String(v == null ? '' : v)
      .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\"/g,'&quot;').replace(/'/g,'&#039;');
  }

  function setMeta(image){
    const ds = image && image.data ? image.data : null;
    const rows = [
      ['Study', studyint || '-'],
      ['Patient', ds ? (ds.string('x00100010') || '-') : '-'],
      ['PID', ds ? (ds.string('x00100020') || '-') : '-'],
      ['Accession', ds ? (ds.string('x00080050') || '-') : '-'],
      ['Date', ds ? (ds.string('x00080020') || '-') : '-'],
      ['Modality', ds ? (ds.string('x00080060') || '-') : '-'],
      ['Series', ds ? (ds.string('x0008103e') || '-') : '-'],
      ['Instance', ds ? (ds.string('x00200013') || '-') : '-']
    ];
    metaEl.innerHTML = rows.map(function(r){ return '<div class="meta-row"><div class="k">'+esc(r[0])+'</div><div>'+esc(r[1])+'</div></div>'; }).join('');
  }

  function syncSeriesHighlight(seriesName){
    if(!seriesEntries.length) return;
    let idx = currentSeriesIndex;
    if(seriesName){
      for(let i = 0; i < seriesEntries.length; i += 1){
        if(seriesEntries[i].title === seriesName){ idx = i; break; }
      }
    } else {
      for(let i = 0, c = 0; i < seriesEntries.length; i += 1){
        c += seriesEntries[i].ids.length;
        if(index < c){ idx = i; break; }
      }
    }
    currentSeriesIndex = idx;
    Array.from(seriesListEl.querySelectorAll('.series')).forEach(function(n, i){ n.classList.toggle('active', i === currentSeriesIndex); });
  }

  function render(){
    if(!activeImageIds.length) return;
    setStatus('Loading image ' + (index + 1) + ' / ' + activeImageIds.length + '...');
    cornerstone.loadAndCacheImage(activeImageIds[index]).then(function(image){
      cornerstone.displayImage(viewer, image);
      setMeta(image);
      setStatus('Image ' + (index + 1) + ' / ' + activeImageIds.length);
      prevBtn.disabled = index <= 0;
      nextBtn.disabled = index >= activeImageIds.length - 1;
      const seriesName = image && image.data ? (image.data.string('x0008103e') || '') : '';
      syncSeriesHighlight(seriesName);
    }).catch(function(err){
      var msg = 'Unknown error';
      if (err && err.message) {
        msg = err.message;
      } else if (typeof err === 'string') {
        msg = err;
      } else {
        try { msg = JSON.stringify(err); } catch (e) {}
      }

      // Some studies contain non-Part10 objects; skip and continue rendering.
      if (/DICM prefix not found/i.test(msg)) {
        activeImageIds.splice(index, 1);
        if (!activeImageIds.length) {
          prevBtn.disabled = true;
          nextBtn.disabled = true;
          setStatus('No renderable DICOM Part10 images found in this study.');
          return;
        }

        if (index >= activeImageIds.length) {
          index = activeImageIds.length - 1;
        }

        setStatus('Skipped non-renderable file. Loading next image...');
        setTimeout(render, 0);
        return;
      }

      setStatus('Render failed: ' + msg);
    });
  }

  function resizeViewer(){
    if(typeof cornerstone === 'undefined' || !viewer) return;
    try {
      cornerstone.resize(viewer, true);
    } catch(e) {}
  }

  function clamp(v, min, max){ return Math.max(min, Math.min(max, v)); }

  function setViewerCursor(){
    if(!viewer) return;
    const cursors = {
      Wwwc: 'ew-resize',
      Pan: 'grab',
      Zoom: 'ns-resize',
      Length: 'crosshair',
      RectangleRoi: 'crosshair'
    };
    viewer.style.cursor = cursors[currentTool] || 'default';
  }

  function ensureMeasureOverlay(){
    if(measureOverlay) return measureOverlay;
    measureOverlay = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    measureOverlay.setAttribute('id', 'rpMeasureOverlay');
    measureOverlay.setAttribute('aria-hidden', 'true');
    viewer.appendChild(measureOverlay);
    return measureOverlay;
  }

  function clearMeasurements(){
    const overlay = ensureMeasureOverlay();
    overlay.innerHTML = '';
    activeMeasure = null;
  }

  function svgPoint(e){
    const rect = viewer.getBoundingClientRect();
    return {
      x: clamp(e.clientX - rect.left, 0, rect.width),
      y: clamp(e.clientY - rect.top, 0, rect.height)
    };
  }

  function svgEl(name, attrs){
    const el = document.createElementNS('http://www.w3.org/2000/svg', name);
    Object.keys(attrs || {}).forEach(function(k){ el.setAttribute(k, attrs[k]); });
    return el;
  }

  function setLabel(label, x, y, text){
    label.setAttribute('x', String(x + 8));
    label.setAttribute('y', String(Math.max(16, y - 8)));
    label.textContent = text;
  }

  function startMeasure(e){
    if(currentTool !== 'Length' && currentTool !== 'RectangleRoi') return false;
    const overlay = ensureMeasureOverlay();
    const p = svgPoint(e);
    const group = svgEl('g', {});
    let shape;
    if(currentTool === 'Length'){
      shape = svgEl('line', {
        x1: p.x, y1: p.y, x2: p.x, y2: p.y,
        stroke: '#38bdf8', 'stroke-width': '2'
      });
    } else {
      shape = svgEl('rect', {
        x: p.x, y: p.y, width: 0, height: 0,
        fill: 'rgba(56,189,248,0.08)', stroke: '#38bdf8', 'stroke-width': '2'
      });
    }
    const label = svgEl('text', {
      x: p.x + 8, y: Math.max(16, p.y - 8),
      fill: '#e0f2fe', 'font-size': '13', 'font-family': 'Arial, sans-serif',
      'paint-order': 'stroke', stroke: '#020617', 'stroke-width': '3'
    });
    group.appendChild(shape);
    group.appendChild(label);
    overlay.appendChild(group);
    activeMeasure = { tool: currentTool, start: p, shape: shape, label: label };
    e.preventDefault();
    return true;
  }

  function updateMeasure(e){
    if(!activeMeasure) return;
    const p = svgPoint(e);
    const s = activeMeasure.start;
    if(activeMeasure.tool === 'Length'){
      activeMeasure.shape.setAttribute('x2', String(p.x));
      activeMeasure.shape.setAttribute('y2', String(p.y));
      const dist = Math.sqrt(Math.pow(p.x - s.x, 2) + Math.pow(p.y - s.y, 2));
      setLabel(activeMeasure.label, p.x, p.y, Math.round(dist) + ' px');
    } else {
      const x = Math.min(s.x, p.x);
      const y = Math.min(s.y, p.y);
      const w = Math.abs(p.x - s.x);
      const h = Math.abs(p.y - s.y);
      activeMeasure.shape.setAttribute('x', String(x));
      activeMeasure.shape.setAttribute('y', String(y));
      activeMeasure.shape.setAttribute('width', String(w));
      activeMeasure.shape.setAttribute('height', String(h));
      setLabel(activeMeasure.label, x + w, y, Math.round(w) + ' x ' + Math.round(h) + ' px');
    }
  }

  function finishMeasure(){
    activeMeasure = null;
  }

  function applyFallbackDrag(dx, dy){
    if(!activeImageIds.length) return;
    const vp = cornerstone.getViewport(viewer);
    if(!vp) return;

    if(currentTool === 'Pan'){
      vp.translation.x += dx;
      vp.translation.y += dy;
    } else if(currentTool === 'Zoom'){
      const factor = 1 + (dy * -0.01);
      vp.scale = clamp(vp.scale * factor, 0.1, 20);
    } else if(currentTool === 'Wwwc'){
      const width = (vp.voi && vp.voi.windowWidth) ? vp.voi.windowWidth : 256;
      const center = (vp.voi && vp.voi.windowCenter) ? vp.voi.windowCenter : 128;
      vp.voi.windowWidth = clamp(width + dx * 2, 1, 20000);
      vp.voi.windowCenter = clamp(center + dy * 2, -20000, 20000);
    }

    cornerstone.setViewport(viewer, vp);
  }

  function setTool(name){
    currentTool = name;
    toolBtns.forEach(function(b){ b.classList.toggle('active', b.dataset.tool === name); });
    const overlay = ensureMeasureOverlay();
    overlay.classList.toggle('active', name === 'Length' || name === 'RectangleRoi');
    setViewerCursor();

    if(!toolsReady || typeof cornerstoneTools === 'undefined'){
      if(name === 'Length' || name === 'RectangleRoi'){
        setStatus(name === 'Length' ? 'Distance tool active: drag across the image.' : 'ROI tool active: drag a box on the image.');
      } else {
        setStatus(name + ' tool active: drag on the image.');
      }
      return;
    }

    ['Wwwc','Pan','Zoom','Length','RectangleRoi'].forEach(function(t){ try{ cornerstoneTools.setToolPassive(t); }catch(e){} });
    try{ cornerstoneTools.setToolActive(name, { mouseButtonMask: 1 }); }catch(e){ setStatus('Using Radpanda ' + name + ' tool.'); }
    if(name === 'Length'){
      setStatus('Distance tool active: drag across the image.');
    } else if(name === 'RectangleRoi'){
      setStatus('ROI tool active: drag a box on the image.');
    } else {
      setStatus(name + ' tool active: drag on the image.');
    }
  }

  function groupSeries(files){
    const groups = {};
    files.forEach(function(f){
      const p = String(f.path || '');
      const key = p.indexOf('/') >= 0 ? p.split('/')[0] : 'Series';
      if(!groups[key]) groups[key] = [];
      groups[key].push('wadouri:' + f.url);
    });
    return groups;
  }

  function jumpToSeries(seriesIdx){
    if(seriesIdx < 0 || seriesIdx >= seriesEntries.length) return;
    currentSeriesIndex = seriesIdx;
    let start = 0;
    for(let i = 0; i < seriesIdx; i += 1){ start += seriesEntries[i].ids.length; }
    index = start;
    syncSeriesHighlight(seriesEntries[seriesIdx].title);
    render();
  }

  function renderSeriesPanel(seriesMap){
    seriesListEl.innerHTML = '';
    const keys = Object.keys(seriesMap);
    seriesEntries = [];
    if(!keys.length){
      seriesListEl.innerHTML = '<div class="series"><div class="a">No series found</div></div>';
      return;
    }

    activeImageIds = [];
    keys.forEach(function(k, idx){
      const ids = seriesMap[k];
      seriesEntries.push({ title: k, ids: ids.slice() });
      activeImageIds = activeImageIds.concat(ids);

      const item = document.createElement('div');
      item.className = 'series' + (idx === 0 ? ' active' : '');
      item.innerHTML = '<div class="a">' + esc(k) + '</div><div class="b">Images: ' + ids.length + '</div>';
      item.addEventListener('click', function(){ jumpToSeries(idx); });
      seriesListEl.appendChild(item);
    });

    currentSeriesIndex = 0;
    index = 0;
  }

  function initToolsSafe(){
    toolsReady = false;
    if(typeof cornerstoneTools === 'undefined'){ disableTools(); return; }
    try {
      if(!cornerstoneTools.external){ cornerstoneTools.external = {}; }
      cornerstoneTools.external.cornerstone = cornerstone;
      if(typeof cornerstoneMath !== 'undefined'){ cornerstoneTools.external.cornerstoneMath = cornerstoneMath; }
      if(typeof window.Hammer !== 'undefined'){ cornerstoneTools.external.Hammer = window.Hammer; }

      if(typeof cornerstoneTools.init === 'function'){
        cornerstoneTools.init({ mouseEnabled:true, touchEnabled:true, globalToolSyncEnabled:false, showSVGCursors:true });
      }

      [['WwwcTool','Wwwc'],['PanTool','Pan'],['ZoomTool','Zoom'],['LengthTool','Length'],['RectangleRoiTool','RectangleRoi'],['StackScrollMouseWheelTool','StackScrollMouseWheel']].forEach(function(d){
        if(typeof cornerstoneTools[d[0]] !== 'undefined'){ cornerstoneTools.addTool(cornerstoneTools[d[0]]); }
      });
      try{ cornerstoneTools.setToolActive('StackScrollMouseWheel', {}); }catch(e){}
      toolsReady = true;
    } catch (toolErr){
      disableTools();
      setStatus('Tools disabled: ' + (toolErr && toolErr.message ? toolErr.message : 'Tool init failed') + '. Using fallback tools.');
    }
  }

  function initViewer(){
    if(!studyint){ setStatus('Missing study identifier.'); return; }
    if(typeof cornerstone==='undefined' || typeof cornerstoneWADOImageLoader==='undefined' || typeof dicomParser==='undefined'){
      setStatus('Viewer libraries unavailable after load (core).');
      return;
    }

    if(!cornerstoneWADOImageLoader.external){ cornerstoneWADOImageLoader.external = {}; }
    cornerstoneWADOImageLoader.external.cornerstone = cornerstone;
    cornerstoneWADOImageLoader.external.dicomParser = dicomParser;

    // Keep decoding path conservative for mixed browser/clinic environments.
    if (typeof cornerstoneWADOImageLoader.configure === 'function') {
      cornerstoneWADOImageLoader.configure({
        useWebWorkers: false,
        beforeSend: function(xhr){
          try { xhr.setRequestHeader('Accept', 'application/dicom'); } catch(e) {}
        }
      });
    }

    if (cornerstoneWADOImageLoader.webWorkerManager && typeof cornerstoneWADOImageLoader.webWorkerManager.initialize === 'function') {
      try {
        cornerstoneWADOImageLoader.webWorkerManager.initialize({
          maxWebWorkers: 1,
          startWebWorkersOnDemand: false,
          taskConfiguration: {
            decodeTask: {
              initializeCodecsOnStartup: false,
              usePDFJS: false,
              strict: false
            }
          }
        });
      } catch (wwErr) {
        // Non-fatal: continue without workers.
      }
    }

    cornerstone.enable(viewer);
    ensureMeasureOverlay();
    window.addEventListener('resize', resizeViewer);
    window.addEventListener('message', function(e){
      if(e && e.data && e.data.type === 'rp-viewer-resize'){
        setTimeout(resizeViewer, 0);
        setTimeout(resizeViewer, 150);
      }
    });
    initToolsSafe();
    setTool('Wwwc');

    prevBtn.addEventListener('click', function(){ if(index > 0){ index -= 1; render(); } });
    nextBtn.addEventListener('click', function(){ if(index < activeImageIds.length - 1){ index += 1; render(); } });
    viewer.addEventListener('wheel', function(e){
      if(!activeImageIds.length) return;
      if(currentTool === 'Zoom'){
        applyFallbackDrag(0, e.deltaY > 0 ? 12 : -12);
        e.preventDefault();
        return;
      }
      if(e.deltaY > 0 && index < activeImageIds.length - 1){ index += 1; render(); }
      else if(e.deltaY < 0 && index > 0){ index -= 1; render(); }
    });

    viewer.addEventListener('mousedown', function(e){
      if(e.button !== 0) return;
      if(startMeasure(e)) return;
      if(currentTool !== 'Wwwc' && currentTool !== 'Pan' && currentTool !== 'Zoom') return;
      isDragging = true;
      lastPointer = { x: e.clientX, y: e.clientY };
      if(currentTool === 'Pan') viewer.style.cursor = 'grabbing';
      e.preventDefault();
    });
    window.addEventListener('mouseup', function(){ isDragging = false; lastPointer = null; finishMeasure(); setViewerCursor(); });
    window.addEventListener('mousemove', function(e){
      if(activeMeasure){ updateMeasure(e); return; }
      if(!isDragging || !lastPointer) return;
      const dx = e.clientX - lastPointer.x;
      const dy = e.clientY - lastPointer.y;
      lastPointer = { x: e.clientX, y: e.clientY };
      applyFallbackDrag(dx, dy);
    });

    toolBtns.forEach(function(btn){ btn.addEventListener('click', function(){ setTool(btn.dataset.tool); }); });
    resetBtn.addEventListener('click', function(){
      clearMeasurements();
      try{ cornerstone.reset(viewer); render(); }catch(e){}
      setStatus('Viewer reset.');
    });

    setStatus('Loading file list...');
    fetch(viewerAppBaseUrl + '/api/list-study-files.php?studyint=' + encodeURIComponent(studyint) + viewerAuthQuery, { cache:'no-store' })
      .then(function(res){
        return res.text().then(function(raw){
          var data = null;
          try { data = JSON.parse(raw); }
          catch (e) { throw new Error(raw || ('HTTP ' + res.status)); }
          if (!res.ok) {
            throw new Error(data && data.error ? data.error : ('HTTP ' + res.status));
          }
          return data;
        });
      })
      .then(function(data){
        if(!data.success || !Array.isArray(data.files) || !data.files.length){ throw new Error(data.error || 'No files found'); }
        setStatus('Found ' + data.files.length + ' DICOM file(s).');
        const seriesMap = groupSeries(data.files);
        renderSeriesPanel(seriesMap);
        render();
        setTimeout(resizeViewer, 0);
        setTimeout(resizeViewer, 250);
      })
      .catch(function(err){
        setStatus('Load failed: ' + (err && err.message ? err.message : 'Unknown error'));
      });
  }

  window.addEventListener('error', function(e){
    if(e && e.message){ setStatus('Viewer error: ' + e.message); }
  });

  loadLibraries(function(ok){
    if(!ok){
      setStatus('Failed to load required viewer libraries. Add local /viewer/vendor/cornerstoneWADOImageLoader.bundle.min.js');
      return;
    }
    try{ initViewer(); }
    catch(err){ setStatus('Init failed: ' + (err && err.message ? err.message : 'Unknown error')); }
  });
})();
</script>
</body>
</html>





