(function(){
  const studyint = "1.2.713.77728038.2561510.9.3.6.9397619077.1735.0490892001.9";
  const statusEl = document.getElementById('status');
  const metaEl = document.getElementById('meta');
  const prevBtn = document.getElementById('prev');
  const nextBtn = document.getElementById('next');
  const viewer = document.getElementById('viewer');
  const toolBtns = Array.from(document.querySelectorAll('.tool'));
  const resetBtn = document.getElementById('reset');

  let imageIds = [];
  let index = 0;

  function setStatus(t){ statusEl.textContent = t; }

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
    metaEl.innerHTML = rows.map(r => '<div class="meta-row"><div class="k">'+r[0]+'</div><div>'+String(r[1]).replace(/</g,'&lt;')+'</div></div>').join('');
  }

  function render(){
    if(!imageIds.length) return;
    setStatus('Loading image '+(index+1)+' / '+imageIds.length+'...');
    cornerstone.loadAndCacheImage(imageIds[index]).then(function(image){
      cornerstone.displayImage(viewer, image);
      setMeta(image);
      setStatus('Image '+(index+1)+' / '+imageIds.length);
      prevBtn.disabled = index <= 0;
      nextBtn.disabled = index >= imageIds.length - 1;
    }).catch(function(err){
      setStatus('Render failed: '+(err && err.message ? err.message : 'Unknown error'));
    });
  }

  function setTool(name){
    ['Wwwc','Pan','Zoom','Length','RectangleRoi'].forEach(function(t){ try{ cornerstoneTools.setToolPassive(t); }catch(e){} });
    try{ cornerstoneTools.setToolActive(name, { mouseButtonMask: 1 }); }catch(e){ setStatus('Tool not available: '+name); }
    toolBtns.forEach(b => b.classList.toggle('active', b.dataset.tool === name));
  }

  if(!studyint){ setStatus('Missing study identifier.'); return; }
  if(typeof cornerstone==='undefined' || typeof cornerstoneTools==='undefined' || typeof cornerstoneWADOImageLoader==='undefined' || typeof dicomParser==='undefined'){ setStatus('Viewer libraries failed to load.'); return; }

  cornerstoneWADOImageLoader.external.cornerstone = cornerstone;
  cornerstoneWADOImageLoader.external.dicomParser = dicomParser;
  cornerstoneTools.external.cornerstone = cornerstone;
  cornerstoneTools.external.cornerstoneMath = cornerstoneMath;
  if(typeof cornerstoneTools.init === 'function'){ cornerstoneTools.init({ mouseEnabled:true, touchEnabled:true, globalToolSyncEnabled:false, showSVGCursors:true }); }

  [['WwwcTool','Wwwc'],['PanTool','Pan'],['ZoomTool','Zoom'],['LengthTool','Length'],['RectangleRoiTool','RectangleRoi'],['StackScrollMouseWheelTool','StackScrollMouseWheel']].forEach(function(d){
    try{ if(typeof cornerstoneTools[d[0]] !== 'undefined'){ cornerstoneTools.addTool(cornerstoneTools[d[0]]); } }catch(e){}
  });
  try{ cornerstoneTools.setToolActive('StackScrollMouseWheel', {}); }catch(e){}

  cornerstone.enable(viewer);
  setTool('Wwwc');

  prevBtn.addEventListener('click', function(){ if(index>0){ index--; render(); } });
  nextBtn.addEventListener('click', function(){ if(index<imageIds.length-1){ index++; render(); } });
  viewer.addEventListener('wheel', function(e){ if(!imageIds.length) return; if(e.deltaY>0 && index<imageIds.length-1){ index++; render(); } else if(e.deltaY<0 && index>0){ index--; render(); } });
  toolBtns.forEach(function(btn){ btn.addEventListener('click', function(){ setTool(btn.dataset.tool); }); });
  resetBtn.addEventListener('click', function(){ try{ cornerstone.reset(viewer); render(); }catch(e){} });

  setStatus('Loading file list...');
  fetch('/remotepanda/api/list-study-files.php?studyint='+encodeURIComponent(studyint), { cache:'no-store' })
    .then(function(res){ return res.json(); })
    .then(function(data){
      if(!data.success || !Array.isArray(data.files) || !data.files.length){ throw new Error(data.error || 'No files found'); }
      imageIds = data.files.map(function(f){ return 'wadouri:'+f.url; });
      index = 0;
      render();
    })
    .catch(function(err){
      setStatus('Load failed: '+(err && err.message ? err.message : 'Unknown error'));
    });
})();

