// Microservices Settings UI Logic (API Pool System)
(function(){
  const KEY='ms:state';
  const D={endpoints:[],roles:['Admin','Developer','Viewer','Operator'],rbac:{},tokens:[],logs:[],services:['auth','laporan','kapal','signature'],routes:[],lb:{},keys:[],ips:[],env:[],servers:[],versions:[],webhooks:[],schedules:[],status:[],usage:[]};
  let S=load(); let start=Date.now();
  function load(){try{const j=localStorage.getItem(KEY);return j?JSON.parse(j):D;}catch(e){return D}}
  function save(){localStorage.setItem(KEY,JSON.stringify(S))}
  function id(){return Math.random().toString(36).slice(2,10)}
  // Dummy endpoints seeding (once, idempotent)
  const SEED_KEY='ms:seededDummyApi10';
  function generateDummyEndpoints(n){
    const services=['kapal','pembinaan','laporan','sinkron','signature','auth','profil','monitor','gateway','registry'];
    const groups=['PIPP','SPB','SHTI','Logbook'];
    const authTypes=['none','api_key','oauth2','jwt','basic'];
    const accessTypes=['public','private'];
    const methodsAll=[['GET'],['GET','POST'],['POST'],['PUT'],['DELETE']];
    const out=[];
    for(let i=0;i<n;i++){
      const name=('API_'+services[i%services.length]+'_'+(i+1)).toUpperCase();
      out.push({
        id:id(),
        name,
        url:`https://api.example.com/${services[i%services.length]}`,
        method:methodsAll[i%methodsAll.length],
        group:groups[i%groups.length],
        version:'v1',
        access:accessTypes[i%accessTypes.length],
        auth:authTypes[i%authTypes.length],
        authConfig:{},
        params:'',
        active:(i%3!==0)
      });
    }
    return out;
  }
  if(!localStorage.getItem(SEED_KEY)){
    S.endpoints = (S.endpoints||[]).concat(generateDummyEndpoints(10));
    localStorage.setItem(SEED_KEY,'1');
    save();
  }
  function log(msg){S.logs.unshift({t:new Date().toISOString(),msg});renderLogs();save()}
  // Tabs
  const tabsEl=document.getElementById('tabs');
  // Guard: hanya pasang listener jika elemen tabs ada
  if(tabsEl){
    tabsEl.addEventListener('click',e=>{
      const btn=e.target.closest('.tab'); if(!btn) return;
      const k=btn.dataset.tab; if(!k) return;
      activateTab(k);
    });
  }
  function activateTab(k){
    document.querySelectorAll('.tab').forEach(t=>t.classList.toggle('active', t.dataset.tab===k));
    document.querySelectorAll('.panel').forEach(p=>p.classList.toggle('active', p.id==='panel-'+k));
    try{localStorage.setItem('settings:lastTab',k)}catch(_){}
    try{history.replaceState(null,'','#'+k)}catch(_){}}
  tabsEl.addEventListener('click',e=>{
    const btn=e.target.closest('.tab'); if(!btn) return;
    const k=btn.dataset.tab; if(!k) return;
    activateTab(k);
  });
  const initialTab=(location.hash&&location.hash.slice(1))||localStorage.getItem('settings:lastTab')||document.querySelector('.tab.active')?.dataset.tab||'api';
  activateTab(initialTab);
  // Stats
  function stat(){byId('stat-endpoints').textContent=S.endpoints.length+' API';byId('stat-roles').textContent=S.roles.length+' Role';const secs=(((Date.now()-start)/1000)|0);byId('stat-uptime').textContent='Uptime: '+secs+'s';drawUptimeRing(byId('uptime-ring'), secs)}
  function byId(x){return document.getElementById(x)}
  // API Config
  byId('btn-add-api')?.addEventListener('click',()=>{
    const name=(val('api-name')||'').trim().toUpperCase(),url=val('api-url'),group=val('api-group'),version=val('api-version'),params=val('api-params');
    const methods=Array.from(document.querySelectorAll('#method-seg button.active')).map(b=>b.dataset.method);
    const method=methods.length?methods:['GET'];
    const invalid=[]; if(!name) invalid.push('api-name'); if(!url) invalid.push('api-url');
    // clear invalid and error messages
    document.querySelectorAll('.input').forEach(i=>i.classList.remove('invalid'));
    document.querySelectorAll('.error-msg').forEach(e=>e.remove());
    function setError(id,msg){const el=byId(id); if(!el) return; el.classList.add('invalid'); const f=el.closest('.field'); if(!f) return; const m=document.createElement('span'); m.className='error-msg'; m.textContent=msg; f.appendChild(m)}
    if(invalid.length){invalid.forEach(id=>{byId(id)?.classList.add('invalid'); setError(id,'Wajib diisi')}); return}
    // Minimal validation per tipe auth
    const authType=val('api-auth');
    const cfg=getAuthConfig();
    if(authType==='oauth2' && !(cfg.token||'').trim()){
      setError('auth-oauth2-token','Token OAuth2 wajib diisi');
      return;
    }
    if(authType==='jwt' && !(cfg.token||'').trim()){
      setError('auth-jwt-token','Token JWT wajib diisi');
      return;
    }
    if(authType==='basic'){
      const u=(cfg.user||'').trim(); const p=(cfg.pass||'').trim();
      if(!u || !p){ setError('auth-basic-user','Username wajib'); setError('auth-basic-pass','Password wajib'); return; }
    }
    if(authType==='api_key'){
      const nm=(cfg.name||'').trim(); const vv=(cfg.value||'').trim();
      if(!nm || !vv){ if(!nm) setError('auth-api-key-name','Nama header/param wajib'); if(!vv) setError('auth-api-key-value','Nilai wajib'); return; }
    }
    // Cek duplikasi berdasarkan kombinasi Nama + Group + Versi (case-insensitive)
    const isDup=S.endpoints.some(e=>{
      const en=(String(e.name||'').trim().toUpperCase());
      const sameGroup=(String(e.group||'').trim().toUpperCase())===String(group||'').trim().toUpperCase();
      const sameVersion=(String(e.version||'').trim().toUpperCase())===String(version||'').trim().toUpperCase();
      return en===name && sameGroup && sameVersion && (!S._edit || e.id!==S._edit);
    });
    if(isDup){ setError('api-name','Nama API sudah terdaftar untuk group dan versi yang sama'); showToast('Nama API duplikat (group & versi sama)', 'error'); return; }

    const ep=S._edit?S.endpoints.find(e=>e.id===S._edit):{id:id(),active:true};
    Object.assign(ep,{name,url,method,group,version,access:val('api-access'),auth:authType,authConfig:cfg,params});
    if(!S._edit){S.endpoints.push(ep); log('Tambah API: '+name)} else {log('Update API: '+name); S._edit=null}
    clearApiForm(); renderApi(); stat(); save(); showToast('API disimpan', 'success');
  });
  // Segmented method -> hidden select sync
  const seg=document.getElementById('method-seg'); if(seg){
    seg.addEventListener('click',e=>{const b=e.target.closest('button'); if(!b) return; b.classList.toggle('active');
      let act=Array.from(seg.querySelectorAll('button.active')).map(x=>x.dataset.method);
      if(act.length===0){const g=seg.querySelector('button[data-method="GET"]'); if(g){g.classList.add('active'); act=['GET'];}}
      const sel=byId('api-method'); if(sel) sel.value=act[0]||'GET';
    });
    const sel=byId('api-method'); if(sel) sel.value=Array.from(seg.querySelectorAll('button.active')).map(x=>x.dataset.method)[0]||'GET';
   }
  function clearApiForm(){['api-name','api-url','api-params','api-group','api-version'].forEach(k=>{const el=byId(k); if(el) el.value=''}); const acc=byId('api-access'); if(acc) acc.value='public'; const auth=byId('api-auth'); if(auth) auth.value='none';
    // reset auth config inputs
    const loc=byId('auth-api-key-loc'); if(loc) loc.value='header'; const nm=byId('auth-api-key-name'); if(nm) nm.value=''; const vv=byId('auth-api-key-value'); if(vv) vv.value='';
    const oTok=byId('auth-oauth2-token'); if(oTok) oTok.value=''; const jTok=byId('auth-jwt-token'); if(jTok) jTok.value='';
    const bu=byId('auth-basic-user'); if(bu) bu.value=''; const bp=byId('auth-basic-pass'); if(bp) bp.value='';
    showAuthSection('none');
    const ms=document.getElementById('method-seg'); if(ms){ms.querySelectorAll('button').forEach(b=>b.classList.toggle('active', b.dataset.method==='GET'))}}
   function renderApi(){
     const tb=byId('api-table'); if(!tb) return;
     const q=(byId('api-search')?.value||'').toLowerCase();
     tb.innerHTML='';
    const list=S.endpoints.filter(ep=>{
      const mArr=Array.isArray(ep.method)?ep.method:[ep.method||'GET'];
      const s=[ep.name,ep.group,mArr.join(','),ep.version].map(x=>String(x||'').toLowerCase()).join(' ');
      return !q || s.includes(q);
    });
     list.forEach(ep=>{
      const tr=document.createElement('tr');
      const mArr=Array.isArray(ep.method)?ep.method:[ep.method||'GET'];
      const chips=mArr.map(m=>`<span class="chip ${String(m).toLowerCase()}">${m}</span>`).join(' ');
      const authLabel=(ep.auth&&ep.auth!=='none')?(ep.auth==='api_key'? 'API Key' : String(ep.auth).toUpperCase()):'None';
      const authChip=`<span class="chip auth">${authLabel}</span>`;
      const accessTxt=(ep.access||'public');
      const authDetail=formatAuthDetail(ep);
       tr.innerHTML=`<td>${ep.name}</td>
         <td>${ep.group||'-'}</td>
        <td>${chips}</td>
        <td>${accessTxt}</td>
        <td>${authChip}</td>
        <td>${authDetail}</td>
         <td><span class="muted">${ep.version||'-'}</span></td>
         <td><span class="status-pill ${ep.active?'active':'inactive'}">${ep.active?'Aktif':'Nonaktif'}</span></td>
         <td>
           <button class="btn" data-act="info" data-id="${ep.id}">Info</button>
           <button class="btn" data-act="test" data-id="${ep.id}">Test</button>
           <button class="btn" data-act="edit" data-id="${ep.id}">Edit</button>
           <button class="btn danger" data-act="del" data-id="${ep.id}">Delete</button>
           <button class="btn secondary" data-act="toggle" data-id="${ep.id}">${ep.active?'Disable':'Enable'}</button>
         </td>`;
       tb.appendChild(tr)
     });
     apiOverview();
   }
   function maskVal(v){ if(!v) return '••••'; const s=String(v); return s.length<=6?('••••••'):(s.slice(0,2)+'••••'+s.slice(-2)); }
   function formatAuthDetail(ep){
     const t=ep.auth||'none'; const c=ep.authConfig||{};
     if(t==='api_key'){
       const loc=c.loc||'header'; const name=(c.name||'Authorization').trim(); const prefix=c.prefix||'ApiKey'; const v=(c.value||'').trim(); const masked=maskVal(v);
       if(loc==='header'){
         if(name.toLowerCase()==='authorization'){
           const val=(prefix&&prefix!=='None')?`${prefix} ${masked}`:masked;
           return `<span class="muted"><code>${name}: ${val}</code></span>`;
         }
         return `<span class="muted"><code>${name}: ${masked}</code></span>`;
       } else {
         const param=(name||'api_key');
         return `<span class="muted"><code>?${param}=${masked}</code></span>`;
       }
     }
     if(t==='oauth2' || t==='jwt'){
       const tok=(c.token||''); return `<span class="muted"><code>Authorization: Bearer ${maskVal(tok)}</code></span>`;
     }
     if(t==='basic'){
       return `<span class="muted"><code>Authorization: Basic ********</code></span>`;
     }
     return '<span class="muted">-</span>';
   }
   function apiOverview(){
     const total=S.endpoints.length;
     const aktif=S.endpoints.filter(e=>e.active).length;
     const inactive=total-aktif;
     const setTxt=(id,v)=>{const el=byId(id); if(el) el.textContent=v};
     setTxt('api-count', total);
     const ratioEl=byId('api-active-ratio');
     if(ratioEl){ ratioEl.innerHTML=`<span class="num-ok">${aktif}</span>/<span class="num-bad">${inactive}</span>`; }
     // Visual charts
     const eps=S.endpoints;
     const authLabels=['NONE','API_KEY','OAUTH2','JWT','BASIC'];
     const authKeys=['none','api_key','oauth2','jwt','basic'];
     const authColors=['#64748b','#0ea5e9','#a855f7','#f59e0b','#f43f5e'];
     const authCounts=authKeys.map(t=>eps.filter(e=>((e.auth||'none')===t)).length);
     const cAuth=document.getElementById('overview-auth');
     drawBars(cAuth, authLabels, authCounts, authColors);
     updateLegendAuth(authLabels, authCounts, authColors);
     const pub=eps.filter(e=>((e.access||'public')==='public')).length;
     const priv=eps.filter(e=>((e.access||'public')==='private')).length;
     const cAcc=document.getElementById('overview-access');
     drawDonut(cAcc, [pub, priv], ['#14b8a6','#f43f5e'], ['Public','Private']);
     updateLegendAccess(pub, priv);
   }
   byId('api-search')?.addEventListener('input',()=>{renderApi()});
   byId('btn-test-all')?.addEventListener('click',async()=>{
     let ok=0,err=0; for(const ep of S.endpoints){const r=await test(ep); if(r.status==='ERR') err++; else ok++;}
     log(`Test massal: OK ${ok}, ERR ${err}`); alert(`Test selesai: OK ${ok}, ERR ${err}`);
   });
   byId('btn-enable-all')?.addEventListener('click',()=>{S.endpoints.forEach(ep=>ep.active=true); log('Enable semua API'); renderApi(); save()});
   byId('btn-disable-all')?.addEventListener('click',()=>{S.endpoints.forEach(ep=>ep.active=false); log('Disable semua API'); renderApi(); save()});
   byId('api-table').addEventListener('click',async e=>{
      const b=e.target.closest('button'); if(!b) return; const id=b.dataset.id; const act=b.dataset.act; const ep=S.endpoints.find(x=>x.id===id); if(!ep) return;
      if(act==='edit'){S._edit=id;['api-name','api-url','api-group','api-version','api-params'].forEach(k=>byId(k).value=ep[k]||''); const acc=byId('api-access'); if(acc) acc.value=ep.access||'public'; const auth=byId('api-auth'); if(auth) auth.value=ep.auth||'none'; fillAuthConfig(ep); const ms=document.getElementById('method-seg'); if(ms){const chosen=Array.isArray(ep.method)?ep.method:[ep.method||'GET']; ms.querySelectorAll('button').forEach(b=>{b.classList.toggle('active', chosen.includes(b.dataset.method))});} const sel=byId('api-method'); if(sel){sel.value=(Array.isArray(ep.method)?ep.method[0]:ep.method)||'GET';}}
      if(act==='del'){S.endpoints=S.endpoints.filter(x=>x.id!==id); log('Hapus API: '+ep.name); renderApi(); renderMatrix(); stat(); save()}
      if(act==='toggle'){ep.active=!ep.active; log((ep.active?'Aktifkan ':'Nonaktifkan ')+ep.name); renderApi(); save()}
      if(act==='info'){ openInfo(ep); }
      if(act==='test'){const res=await test(ep); const mPick=Array.isArray(ep.method)?(ep.method[0]||'GET'):(ep.method||'GET'); const params=ep.params; const qs=params?('?'+(typeof params==='string'?params:new URLSearchParams(params).toString())):''; const full=(mPick.toUpperCase()==='GET')?ep.url+qs:ep.url; const info=`Akses: ${(ep.access||'public').toUpperCase()} | Auth: ${(ep.auth||'none')}`; alert(`Testing ${mPick} ${full}
 ${info}
 Status: ${res.status} • ${res.ms}ms`) }
    });
  async function test(ep){const t0=performance.now(); let status='ERR'; try{const mPick=Array.isArray(ep.method)?(ep.method[0]||'GET'):(ep.method||'GET'); const ba=buildAuth(ep); let url=ba.url; const headers=ba.headers||{}; const params=ep.params||{}; if(mPick.toUpperCase()==='GET' && params && typeof params==='object'){const u=new URL(url, location.origin); Object.entries(params).forEach(([k,v])=>u.searchParams.set(k,String(v))); url=u.toString()} const opt={method:mPick, headers}; const r=await fetch(url,opt); status=r.status; }catch(e){status='ERR'} const ms=Math.round(performance.now()-t0); S.usage.push({name:ep.name,ms,status,ts:Date.now()}); if(val('alert-th')&&ms>+val('alert-th')) log('ALERT lambat '+ep.name+' '+ms+'ms'); renderUsage(); save(); return {status,ms}}
  // Backup/Restore
  byId('btn-backup')?.addEventListener('click',()=>{const blob=new Blob([JSON.stringify(S,null,2)],{type:'application/json'}); const a=document.createElement('a'); a.href=URL.createObjectURL(blob); a.download='api-pool-config.json'; a.click(); URL.revokeObjectURL(a.href); log('Backup konfigurasi')});
  byId('restore-file')?.addEventListener('change',async e=>{const f=e.target.files[0]; if(!f) return; const txt=await f.text(); try{S=JSON.parse(txt); save(); init(); log('Restore konfigurasi berhasil'); showToast('Restore berhasil', 'success')}catch(err){alert('Restore gagal: '+err); showToast('Restore gagal', 'error')}});

  // RBAC
  byId('btn-add-role').addEventListener('click',()=>{const r=val('role-name'); if(!r) return; if(!S.roles.includes(r)) S.roles.push(r); renderRoles(); renderMatrix(); save(); log('Tambah role: '+r); byId('role-name').value=''});
  function renderRoles(){const ul=byId('role-list'); ul.innerHTML=''; S.roles.forEach(r=>{const li=document.createElement('li'); li.textContent=r; ul.appendChild(li)}); const sel=byId('matrix-role'); sel.innerHTML=S.roles.map(r=>`<option>${r}</option>`).join('')}
  byId('btn-save-matrix').addEventListener('click',()=>{const role=val('matrix-role'); const cfg={}; document.querySelectorAll('#matrix-table tr').forEach(tr=>{const id=tr.dataset.id; const ch=tr.querySelectorAll('input'); cfg[id]={r:ch[0].checked,w:ch[1].checked,u:ch[2].checked,d:ch[3].checked}}); S.rbac[role]=cfg; save(); log('Simpan akses role: '+role); alert('Tersimpan')});
  function renderMatrix(){const tb=byId('matrix-table'); tb.innerHTML=''; const role=val('matrix-role')||S.roles[0]; const map=S.rbac[role]||{}; S.endpoints.forEach(ep=>{const tr=document.createElement('tr'); tr.dataset.id=ep.id; const p=map[ep.id]||{}; tr.innerHTML=`<td>${ep.name}</td>
      <td><input type="checkbox" ${p.r?'checked':''}></td>
      <td><input type="checkbox" ${p.w?'checked':''}></td>
      <td><input type="checkbox" ${p.u?'checked':''}></td>
      <td><input type="checkbox" ${p.d?'checked':''}></td>`; tb.appendChild(tr)})}
  byId('matrix-role').addEventListener('change',renderMatrix);
  // Token Management
  byId('btn-gen-token').addEventListener('click',()=>{const t=('tok_'+Math.random().toString(36).slice(2)+Date.now().toString(36)); const label=val('token-label'); S.tokens.unshift({t,label}); renderTokens(); save(); log('Generate token')});
  function renderTokens(){const tb=byId('token-table'); tb.innerHTML=''; S.tokens.forEach((x,i)=>{const tr=document.createElement('tr'); tr.innerHTML=`<td><code>${x.t}</code></td><td>${x.label||''}</td><td><button class="btn danger" data-i="${i}">Delete</button></td>`; tb.appendChild(tr)}); tb.querySelectorAll('button').forEach(b=>b.onclick=()=>{const i=+b.dataset.i; S.tokens.splice(i,1); renderTokens(); save()})}

  // Integration & Routing
  byId('btn-add-route').addEventListener('click',()=>{const a=val('route-from'),b=val('route-to'); if(!a||!b) return; S.routes.push({a,b}); renderRoutes(); save(); log('Tambah route '+a+' -> '+b)});
  function renderRoutes(){const ul=byId('route-list'); ul.innerHTML=''; S.routes.forEach(r=>{const li=document.createElement('li'); li.textContent=`${r.a} → ${r.b}`; ul.appendChild(li)})}
  byId('btn-set-lb').addEventListener('click',()=>{const s=val('lb-service'),w=val('lb-weight'); if(!s||!w) return; S.lb[s]=+w; save(); log('Set LB '+s+' = '+w)});
  function renderServices(){const ul=byId('service-list'); ul.innerHTML=''; S.services.forEach(s=>{const li=document.createElement('li'); li.textContent=s+(S.lb[s]?` (weight ${S.lb[s]})`:'' ); ul.appendChild(li)})}

  // Security
  byId('btn-save-oauth').addEventListener('click',()=>{setEnv('OAUTH_CLIENT',val('oauth-client')); setEnv('OAUTH_SECRET',val('oauth-secret')); log('Simpan OIDC/OAuth client'); alert('Tersimpan')});
  byId('btn-gen-key').addEventListener('click',()=>{const k='api_'+Math.random().toString(36).slice(2); S.keys.unshift(k); renderKeys(); save(); log('Generate API key')});
  function renderKeys(){const ul=byId('key-list'); ul.innerHTML=''; S.keys.forEach(k=>{const li=document.createElement('li'); li.textContent=k; ul.appendChild(li)})}
  byId('btn-add-ip').addEventListener('click',()=>{const ip=val('ip-input'); if(!ip) return; S.ips.push(ip); renderIPs(); save(); log('Whitelist IP '+ip)});
  function renderIPs(){const ul=byId('ip-list'); ul.innerHTML=''; S.ips.forEach((ip,i)=>{const li=document.createElement('li'); li.innerHTML=`${ip} <button class="btn danger" data-i="${i}">Hapus</button>`; ul.appendChild(li)}); ul.querySelectorAll('button').forEach(b=>b.onclick=()=>{S.ips.splice(+b.dataset.i,1); renderIPs(); save()})}
  byId('btn-clear-logs').addEventListener('click',()=>{S.logs=[]; renderLogs(); save()});
  function renderLogs(){const ul=byId('log-list'); ul.innerHTML=''; S.logs.slice(0,60).forEach(l=>{const li=document.createElement('li'); li.textContent=`${l.t} • ${l.msg}`; ul.appendChild(li)})}

  // Monitoring
  byId('btn-health').addEventListener('click',async()=>{const ul=byId('health-list'); ul.innerHTML=''; for(const ep of S.endpoints){const t0=performance.now(); let st='ERR'; try{const r=await fetch(ep.url,{method:'HEAD'}); st=r.status}catch(e){st='ERR'} const ms=Math.round(performance.now()-t0); const li=document.createElement('li'); li.textContent=`${ep.name} • ${st} • ${ms}ms`; ul.appendChild(li)} log('Health check dijalankan')});
  function renderUsage(){const c=byId('chart-usage'); if(!c) return; const ctx=c.getContext('2d'); ctx.clearRect(0,0,c.width,c.height); const data=S.usage.slice(-18); const w=c.width/(data.length||1); data.forEach((u,i)=>{const h=Math.min(160, u.ms/2); ctx.fillStyle=u.status==='ERR'?'#f43f5e':'#22c55e'; ctx.fillRect(i*w+4, c.height-20-h, w-8, h); ctx.fillStyle='#a7b4c7'; ctx.font='10px sans-serif'; ctx.fillText(u.name.slice(0,8), i*w+6, c.height-6)})}

  // Sync
  byId('btn-sync').addEventListener('click',()=>{const t=val('sync-target')||'unknown'; S.status.unshift({sys:t,res:'OK',time:new Date().toLocaleString()}); renderStatus(); S.logs.unshift({t:new Date().toISOString(),msg:'Manual sync '+t}); renderLogs(); save()});
  byId('btn-set-cron').addEventListener('click',()=>{const s=val('sync-cron'); if(!s) return; S.schedules.unshift({s,at:new Date().toLocaleString()}); renderCron(); save(); log('Set jadwal sync: '+s)});
  function renderStatus(){const ul=byId('status-list'); ul.innerHTML=''; S.status.slice(0,20).forEach(s=>{const li=document.createElement('li'); li.textContent=`${s.time} • ${s.sys} • ${s.res}`; ul.appendChild(li)})}
  function renderCron(){const ul=byId('cron-list'); ul.innerHTML=''; S.schedules.forEach(s=>{const li=document.createElement('li'); li.textContent=`${s.at} • ${s.s}`; ul.appendChild(li)})}

  // Env & Server
  byId('btn-add-env').addEventListener('click',()=>{const k=val('env-key'),v=val('env-val'); if(!k) return; const i=S.env.findIndex(e=>e.k===k); if(i>=0) S.env[i].v=v; else S.env.push({k,v}); renderEnv(); save(); log('Set ENV '+k)});
  function setEnv(k,v){const i=S.env.findIndex(e=>e.k===k); if(i>=0) S.env[i].v=v; else S.env.push({k,v}); renderEnv(); save()}
  function renderEnv(){const ul=byId('env-list'); ul.innerHTML=''; S.env.forEach((e,i)=>{const li=document.createElement('li'); li.innerHTML=`<b>${e.k}</b>=${e.v} <button class=\"btn danger\" data-i=\"${i}\">Hapus</button>`; ul.appendChild(li)}); ul.querySelectorAll('button').forEach(b=>b.onclick=()=>{S.env.splice(+b.dataset.i,1); renderEnv(); save()})}
  byId('btn-add-srv').addEventListener('click',()=>{const n=val('srv-name'),h=val('srv-host'); if(!n||!h) return; S.servers.push({n,h}); renderSrv(); save(); log('Tambah server '+n)});
  function renderSrv(){const ul=byId('srv-list'); ul.innerHTML=''; S.servers.forEach(s=>{const li=document.createElement('li'); li.textContent=`${s.n} • ${s.h}`; ul.appendChild(li)})}

  // Webhook & Advanced
  byId('btn-add-wh').addEventListener('click',()=>{const u=val('wh-url'); if(!u) return; S.webhooks.push(u); renderWh(); save(); log('Tambah webhook')});
  function renderWh(){const ul=byId('wh-list'); ul.innerHTML=''; S.webhooks.forEach((w,i)=>{const li=document.createElement('li'); li.innerHTML=`${w} <button class=\"btn danger\" data-i=\"${i}\">Hapus</button>`; ul.appendChild(li)}); ul.querySelectorAll('button').forEach(b=>b.onclick=()=>{S.webhooks.splice(+b.dataset.i,1); renderWh(); save()})}
  function drawDeps(){const c=byId('dep-map'); if(!c) return; const ctx=c.getContext('2d'); ctx.clearRect(0,0,c.width,c.height); const N=S.services.length; const R=80; const cx=c.width/2, cy=c.height/2; ctx.fillStyle='#93c5fd'; S.services.forEach((s,i)=>{const ang=(i/N)*Math.PI*2; const x=cx+Math.cos(ang)*R*2, y=cy+Math.sin(ang)*R*2; ctx.beginPath(); ctx.arc(x,y,18,0,Math.PI*2); ctx.fill(); ctx.fillStyle='#0b1020'; ctx.font='12px sans-serif'; ctx.fillText(s,x-20,y+4); ctx.fillStyle='#93c5fd'}); ctx.strokeStyle='#22d3ee'; S.routes.forEach(r=>{const a=S.services.indexOf(r.a),b=S.services.indexOf(r.b); if(a<0||b<0)return; const ax=cx+Math.cos((a/N)*Math.PI*2)*R*2, ay=cy+Math.sin((a/N)*Math.PI*2)*R*2; const bx=cx+Math.cos((b/N)*Math.PI*2)*R*2, by=cy+Math.sin((b/N)*Math.PI*2)*R*2; ctx.beginPath(); ctx.moveTo(ax,ay); ctx.lineTo(bx,by); ctx.stroke()})}

  // Sandbox
  byId('btn-run-sb').addEventListener('click',async()=>{const u=val('sb-url'),m=val('sb-method'); if(!u) return; try{const r=await fetch(u,{method:m}); const t=await r.text(); byId('sb-out').textContent=t.slice(0,2000)}catch(e){byId('sb-out').textContent='ERR: '+e}});

  // Helpers
  function val(id){const el=byId(id); return el?el.value:''}
  // Toast ringan
  function showToast(msg, type='info'){
    const cont=document.getElementById('toast-container');
    if(!cont){ // fallback
      console[type==='error'?'error':'log'](msg);
      return;
    }
    const t=document.createElement('div'); t.className='toast '+type; t.textContent=msg;
    cont.appendChild(t);
    requestAnimationFrame(()=>{ t.classList.add('show'); });
    setTimeout(()=>{
      t.classList.remove('show');
      t.addEventListener('transitionend', ()=>{ t.remove(); }, { once:true });
    }, 2600);
  }

  // Info modal functions
  function openInfo(ep){
    const backdrop=document.getElementById('info-backdrop');
    const modal=document.getElementById('info-modal');
    const sel=document.getElementById('info-method');
    const title=document.getElementById('info-title');
    const meta=document.getElementById('info-meta');
    const statusEl=document.getElementById('info-status');
    const headersEl=document.getElementById('info-headers');
    const bodyOutEl=document.getElementById('info-body');
    const qWrap=document.getElementById('info-qparams');
    const addParamBtn=document.getElementById('info-add-param');
    const reqSection=document.getElementById('info-req-section');
    const ctypeSel=document.getElementById('info-ctype');
    const reqBodyEl=document.getElementById('info-body-input');

    if(!modal||!backdrop){ alert('Komponen Info tidak tersedia'); return; }

    // Populate method options
    sel.innerHTML='';
    const methods=Array.isArray(ep.method)?ep.method:[ep.method].filter(Boolean);
    methods.forEach(m=>{const opt=document.createElement('option'); opt.value=m; opt.textContent=m; sel.appendChild(opt)});

    title.textContent=`API Info — ${ep.name||ep.nama||'Unnamed'}`;
    meta.textContent=`URL: ${ep.url} • Auth: ${ep.auth||'none'} • Access: ${ep.access||'public'}`;

    // Helpers: query param row
    function addParamRow(k='',v=''){
      const row=document.createElement('div');
      row.className='row'; row.setAttribute('data-kv',''); row.style.gap='6px';
      row.innerHTML=`<input class="input" placeholder="key" value="${k}"> <input class="input" placeholder="value" value="${v}"> <button class="btn danger" data-del>Hapus</button>`;
      qWrap.appendChild(row);
    }
    function clearParams(){ qWrap.innerHTML=''; }
    function preloadParams(){
      clearParams();
      const p=ep.params;
      if(Array.isArray(p)){
        p.forEach(x=>{ if(x&&x.key) addParamRow(x.key, x.value||'') });
      } else if(p && typeof p==='object'){
        Object.entries(p).forEach(([k,v])=>addParamRow(k,String(v)));
      } else if(typeof p==='string'){
        const usp=new URLSearchParams(p); for(const [k,v] of usp.entries()){ addParamRow(k,v) }
      }
    }

    // Toggle body section by method
    function toggleBody(){
      const m=(sel.value||'GET').toUpperCase();
      const showBody=['POST','PUT','PATCH','DELETE'].includes(m);
      if(reqSection) reqSection.style.display=showBody?'block':'none';
    }

    function applySampleBody(){
      const ct=(ctypeSel?.value)||'application/json';
      let sample='';
      if(ct==='application/json') sample=JSON.stringify({example:"value", timestamp: Date.now()}, null, 2);
      else if(ct==='application/x-www-form-urlencoded') sample='a=1&b=2';
      else sample='Hello from SSO PIPP';
      reqBodyEl.value=sample;
    }

    // Events for params UI
    if(addParamBtn){ addParamBtn.onclick=()=>addParamRow(); }
    if(qWrap){ qWrap.addEventListener('click',e=>{ const b=e.target.closest('[data-del]'); if(!b) return; const row=b.closest('[data-kv]'); if(row) row.remove(); }) }
    if(ctypeSel){ ctypeSel.addEventListener('change',()=>{ applySampleBody(); }) }

    const doFetch=async()=>{
      statusEl.textContent='Loading...'; headersEl.textContent='{}'; bodyOutEl.textContent='';
      let method=sel.value||'GET'; const started=performance.now();
      try{
        const auth=buildAuth(ep);
        let url=auth.url||ep.url;
        // Merge stored params and UI params
        const u=new URL(url, location.origin);
        // from stored config
        const p=ep.params;
        if(Array.isArray(p)) p.forEach(x=>{ if(x&&x.key) u.searchParams.set(x.key, x.value||'') });
        else if(p && typeof p==='object') Object.entries(p).forEach(([k,v])=>u.searchParams.set(k,String(v)));
        else if(typeof p==='string'){ const usp=new URLSearchParams(p); for(const [k,v] of usp.entries()){ u.searchParams.set(k,v) } }
        // from UI
        qWrap.querySelectorAll('[data-kv]').forEach(row=>{
          const inputs=row.querySelectorAll('input'); const k=(inputs[0]?.value||'').trim(); const v=(inputs[1]?.value||'').trim();
          if(k) u.searchParams.set(k,v);
        });
        url=u.toString();

        const headers=Object.assign({}, auth.headers||{});
        const upper=method.toUpperCase();
        const opt={ method: upper, headers, mode:'cors' };
        if(['POST','PUT','PATCH','DELETE'].includes(upper)){
          const ct=(ctypeSel?.value)||'application/json';
          headers['Content-Type']=ct;
          const bodyStr=String(reqBodyEl?.value||'');
          opt.body=bodyStr;
        }
        const res=await fetch(url,opt);
        const ms=Math.round(performance.now()-started);
        statusEl.textContent=`${res.status} ${res.statusText} • ${ms} ms`;
        const hObj={}; res.headers.forEach((v,k)=>{hObj[k]=v}); headersEl.textContent=JSON.stringify(hObj,null,2);
        const ct=res.headers.get('content-type')||''; let text;
        if(ct.includes('application/json')){try{const j=await res.json(); text=JSON.stringify(j,null,2)}catch{ text=await res.text() }} else { text=await res.text() }
        bodyOutEl.textContent=(text||'').slice(0,4000) || '(tidak ada)';
      }catch(err){ statusEl.textContent='Error'; headersEl.textContent='{}'; bodyOutEl.textContent=`Terjadi kesalahan: ${err&&err.message?err.message:String(err)}` }
    };

    sel.onchange=()=>{ toggleBody(); doFetch(); };
    document.getElementById('info-close').onclick=closeInfo;
    backdrop.classList.add('open'); modal.classList.add('open');
    preloadParams(); toggleBody(); if(reqSection && reqSection.style.display!=='none'){ applySampleBody(); }
    doFetch();
  }
  function closeInfo(){ document.getElementById('info-backdrop')?.classList.remove('open'); document.getElementById('info-modal')?.classList.remove('open') }
  // Close on backdrop click and ESC
  (function(){ const bd=document.getElementById('info-backdrop'); if(bd){ bd.addEventListener('click', closeInfo) } window.addEventListener('keydown',ev=>{ if(ev.key==='Escape') closeInfo() }) })();

  function setupPopovers(){
    document.querySelectorAll('.info-tip').forEach(icon=>{
      const pop=icon.parentElement.querySelector('.popover'); if(!pop) return;
      let timer;
      const position=()=>{
        const r=icon.getBoundingClientRect();
        const top=r.top+window.scrollY-6;
        const left=r.left+window.scrollX+r.width+8;
        pop.style.top=top+'px';
        pop.style.left=left+'px';
      };
      const show=()=>{
        position();
        pop.classList.add('open');
        pop.style.opacity='1';
        pop.style.transform='translateX(0)';
        clearTimeout(timer); timer=setTimeout(hide,3000);
      };
      const hide=()=>{
        pop.style.opacity='0';
        pop.style.transform='translateX(6px)';
        setTimeout(()=>{pop.classList.remove('open');},180);
      };
      icon.addEventListener('mouseenter', show);
      icon.addEventListener('click', show);
      icon.addEventListener('mouseleave', hide);
      document.addEventListener('scroll', ()=>{ if(pop.classList.contains('open')) position(); });
      document.addEventListener('click', (e)=>{ if(!icon.contains(e.target) && !pop.contains(e.target)) hide(); });
    });
  }
  function setupAccessThresholdSwitch(){
    const el=document.getElementById('access-threshold-switch'); if(!el) return;
    const curr=ACCESS_DOMINANCE_THRESHOLD;
    el.querySelectorAll('button').forEach(btn=>{
      const thr=Number(btn.dataset.thr)||0;
      btn.classList.toggle('active', thr===curr);
      btn.addEventListener('click',()=>{
        el.querySelectorAll('button').forEach(b=>b.classList.remove('active'));
        btn.classList.add('active');
        setAccessDominanceThreshold(thr);
        if(typeof apiOverview==='function') apiOverview();
      });
    });
  }
  // Auth section initial wiring + enforce uppercase + popovers
  document.addEventListener('DOMContentLoaded', ()=>{
    const sel=document.getElementById('api-auth');
    if(sel){
      showAuthSection(sel.value);
      sel.addEventListener('change', ()=>showAuthSection(sel.value));
    }
    ['auth-api-key-prefix','auth-api-key-loc','auth-api-key-name'].forEach(id=>{
      const el=document.getElementById(id); if(el) el.addEventListener('change', updateApiKeyFormatHint);
    });
    const valEl=document.getElementById('auth-api-key-value'); if(valEl) valEl.addEventListener('input', updateApiKeyFormatHint);
    updateApiKeyFormatHint();

    bindRequired('api-name','Wajib diisi');
    bindRequired('api-url','Wajib diisi');
    bindRequired('auth-basic-user','Username wajib','basic');
    bindRequired('auth-basic-pass','Password wajib','basic');
    bindRequired('auth-api-key-name','Nama header/param wajib','api_key');
    bindRequired('auth-api-key-value','Nilai wajib','api_key');
    bindRequired('auth-oauth2-token','Token OAuth2 wajib','oauth2');
    bindRequired('auth-jwt-token','Token JWT wajib','jwt');

    // Paksa input nama API uppercase saat mengetik
    const nameEl=document.getElementById('api-name');
    if(nameEl){
      nameEl.addEventListener('input',()=>{ nameEl.value=(nameEl.value||'').toUpperCase(); });
      nameEl.addEventListener('blur',()=>{ nameEl.value=(nameEl.value||'').trim().toUpperCase(); });
    }

    setupAccessThresholdSwitch();
    setupPopovers();
  });

  function showAuthSection(t){
    const ids=['none','api_key','oauth2','jwt','basic'];
    ids.forEach(x=>{
      const el=document.getElementById('auth-'+x);
      if(el) el.style.display=((t===x)||((t==='none')&&(x==='none')))?'block':'none';
    });
  }
  function updateApiKeyFormatHint(){
    const el=document.getElementById('api-key-value-format'); if(!el) return;
    const loc=document.getElementById('auth-api-key-loc')?.value||'header';
    const name=(document.getElementById('auth-api-key-name')?.value||'').trim();
    const prefix=document.getElementById('auth-api-key-prefix')?.value||'ApiKey';
    const val=(document.getElementById('auth-api-key-value')?.value||'').trim()||'&lt;nilai&gt;';
    let msg='';
    if(loc==='header'){
      const header=name||'Authorization';
      if(header.toLowerCase()==='authorization'){
        const prefTxt=prefix==='None'?'':(prefix+' ');
        msg=`Format otomatis: <code>Authorization: ${prefTxt}${val}</code>`;
      } else {
        msg=`Header: <code>${header}: ${val}</code>`;
      }
    } else {
      const param=name||'api_key';
      msg=`Query: <code>?${param}=${val}</code>`;
    }
    el.innerHTML=msg;
  }
  function bindRequired(id,msg,onlyAuthType){
    const el=document.getElementById(id); if(!el) return;
    const run=()=>{
      const currentAuth=document.getElementById('api-auth')?.value||'none';
      const applicable=!onlyAuthType || currentAuth===onlyAuthType;
      const v=(el.value||'').trim();
      const field=el.closest('.field'); if(!field) return;
      let m=field.querySelector('.error-msg');
      if(applicable && !v){
        el.classList.add('invalid');
        if(!m){ m=document.createElement('span'); m.className='error-msg'; m.textContent=msg; field.appendChild(m); }
      } else {
        el.classList.remove('invalid');
        if(m) m.remove();
      }
    };
    el.addEventListener('input', run);
    document.getElementById('api-auth')?.addEventListener('change', run);
  }
  function getAuthConfig(){
    const t=document.getElementById('api-auth')?.value||'none';
    if(t==='api_key'){
      return {loc: document.getElementById('auth-api-key-loc')?.value||'header', name: document.getElementById('auth-api-key-name')?.value||'', value: document.getElementById('auth-api-key-value')?.value||'', prefix: document.getElementById('auth-api-key-prefix')?.value||'ApiKey'};
    }
    if(t==='oauth2'){
      return {token: document.getElementById('auth-oauth2-token')?.value||''};
    }
    if(t==='jwt'){
      return {token: document.getElementById('auth-jwt-token')?.value||''};
    }
    if(t==='basic'){
      return {user: document.getElementById('auth-basic-user')?.value||'', pass: document.getElementById('auth-basic-pass')?.value||''};
    }
    return {};
  }
  function fillAuthConfig(ep){
    const t=ep.auth||'none'; const c=ep.authConfig||{};
    const sel=document.getElementById('api-auth'); if(sel) sel.value=t;
    showAuthSection(t);
    if(t==='api_key'){
      const loc=document.getElementById('auth-api-key-loc'); if(loc) loc.value=c.loc||'header';
      const nm=document.getElementById('auth-api-key-name'); if(nm) nm.value=c.name||'';
      const pf=document.getElementById('auth-api-key-prefix'); if(pf) pf.value=c.prefix||'ApiKey';
      const vv=document.getElementById('auth-api-key-value'); if(vv) vv.value=c.value||'';
      updateApiKeyFormatHint();
    }
    if(t==='oauth2'){
      const tok=document.getElementById('auth-oauth2-token'); if(tok) tok.value=c.token||'';
    }
    if(t==='jwt'){
      const tok=document.getElementById('auth-jwt-token'); if(tok) tok.value=c.token||'';
    }
    if(t==='basic'){
      const u=document.getElementById('auth-basic-user'); if(u) u.value=c.user||'';
      const p=document.getElementById('auth-basic-pass'); if(p) p.value=c.pass||'';
    }
  }
  function buildAuth(ep){
    let url=ep.url; const headers={};
    const t=ep.auth||'none'; const c=ep.authConfig||{};
    if(t==='api_key'){
      const loc=c.loc||'header';
      const name=(c.name||'Authorization').trim();
      let v=(c.value||'').trim();
      const prefix=c.prefix||'ApiKey';
      if(loc==='header'){
        if(name){
          if(name.toLowerCase()==='authorization'){
            if(v && !/^(ApiKey|Bearer|Basic|Token)\s/i.test(v)){
              if(prefix && prefix!=='None') v=prefix+' '+v;
            }
          }
          headers[name]=v;
        }
      } else {
        if(name && v){const u=new URL(url, location.origin); u.searchParams.set(name, v); url=u.toString();}
      }
    } else if(t==='oauth2'){
      const token=(c.token||'').trim(); if(token) headers['Authorization']='Bearer '+token;
    } else if(t==='jwt'){
      const token=(c.token||'').trim(); if(token) headers['Authorization']='Bearer '+token;
    } else if(t==='basic'){
      const u=(c.user||''); const p=(c.pass||''); headers['Authorization']='Basic '+btoa(u+':'+p);
    }
    return {url, headers};
  }

  function init(){renderApi(); renderRoles(); renderMatrix(); renderTokens(); renderRoutes(); renderServices(); renderKeys(); renderIPs(); renderLogs(); renderUsage(); renderStatus(); renderCron(); renderEnv(); renderSrv(); renderWh(); drawDeps(); stat();}
  init(); setInterval(()=>{stat()},1000);
})();
function drawBars(c, labels, values, colors){
  if(!c) return; const ctx=c.getContext('2d'); const W=c.width, H=c.height; ctx.clearRect(0,0,W,H);
  const prev=Array.isArray(c._lastValues)?c._lastValues:new Array(labels.length).fill(0);
  const next=values.slice(); const dur=600; const delayPerBar=120; const t0=performance.now();
  function easeOutQuad(t){return 1-(1-t)*(1-t)}
  const pad=24; const slot=(W - pad*2)/labels.length; const barW=Math.max(16, slot-12);
  const max=Math.max(1,...next);
  function frame(now){
    ctx.clearRect(0,0,W,H);
    let allDone=true;
    for(let i=0;i<labels.length;i++){
      const tt=Math.max(0,(now - t0 - i*delayPerBar));
      const t=Math.min(1, tt/dur);
      if(t<1) allDone=false;
      const k=easeOutQuad(t);
      const val=prev[i]+(next[i]-prev[i])*k; const h=Math.round((H-40)*(val/Math.max(1,max)));
      const x=pad + i*slot + 6; const y=H-24-h; const col=colors[i%colors.length];
      const grd=ctx.createLinearGradient(0,y,0,y+h); grd.addColorStop(0,col); grd.addColorStop(1,'#1f2937');
      ctx.fillStyle=grd; ctx.fillRect(x,y,barW,h);
      ctx.fillStyle='#c9d5e8'; ctx.font='600 11px system-ui'; ctx.fillText(String(Math.round(val)), x, y-4);
      ctx.fillStyle='#dbeafe'; ctx.font='11px system-ui'; ctx.fillText(labels[i], x, H-8);
    }
    if(!allDone){ requestAnimationFrame(frame);} else { c._lastValues=next; }
  }
  requestAnimationFrame(frame);
}
function updateLegendAuth(labels, values, colors){
  const el=document.getElementById('legend-auth'); if(!el) return;
  el.innerHTML=labels.map((lab,i)=>`<span class="legend-item"><span class="legend-dot" style="background:${colors[i%colors.length]}"></span>${lab}: ${values[i]}</span>`).join(' ');
}
function updateLegendAccess(pubCount, privCount){
  const el=document.getElementById('legend-access'); if(!el) return;
  el.innerHTML=`<span class="legend-item"><span class="legend-dot" style="background:#14b8a6"></span>Public: ${pubCount}</span> <span class="legend-item"><span class="legend-dot" style="background:#f43f5e"></span>Private: ${privCount}</span>`;
}
function drawDonut(c, values, colors, labels){
  if(!c) return; const ctx=c.getContext('2d'); const W=c.width, H=c.height; const cx=W/2, cy=H/2; const r=Math.min(W,H)/2-10; const th=18;
  const badge=(c.id==='overview-access')?document.getElementById('access-badge'):null;
  const prev=Array.isArray(c._lastValues)?c._lastValues:[0,0];
  const next=values.slice();
  const sumNext=next.reduce((a,b)=>a+b,0);
  if(sumNext<=0){ ctx.clearRect(0,0,W,H); ctx.fillStyle='#a7b4c7'; ctx.font='12px system-ui'; ctx.fillText('No data', cx-26, cy+4); if(badge){badge.textContent='Public 0% • Private 0%'; badge.classList.remove('dominant-public','dominant-private','balanced');} c._lastValues=next; return; }
  const dur=600; const t0=performance.now();
  function easeOutQuad(t){return 1-(1-t)*(1-t)}
  function frame(now){
    const t=Math.min(1,(now-t0)/dur); const k=easeOutQuad(t);
    const curr=[prev[0]+(next[0]-prev[0])*k, prev[1]+(next[1]-prev[1])*k];
    const sum=curr.reduce((a,b)=>a+b,0);
    ctx.clearRect(0,0,W,H);
    let a=-Math.PI/2; for(let i=0;i<curr.length;i++){ const ang=(curr[i]/sum)*Math.PI*2; ctx.beginPath(); ctx.arc(cx,cy,r,a,a+ang); ctx.arc(cx,cy,r-th,a+ang,a,true); ctx.closePath(); ctx.fillStyle=colors[i%colors.length]; ctx.fill(); a+=ang; }
    const pubPct=Math.round((curr[0]/sum)*100); const privPct=100-pubPct;
    ctx.fillStyle='#dbeafe'; ctx.font='12px system-ui'; ctx.textAlign='center'; ctx.fillText(labels[0]+' '+pubPct+'%', cx, cy-2);
    ctx.fillStyle='#a7b4c7'; ctx.fillText(labels[1]+' '+privPct+'%', cx, cy+14);
    if(badge){
      badge.textContent=`Public ${pubPct}% • Private ${privPct}%`;
      badge.classList.add('show');
      badge.classList.remove('dominant-public','dominant-private','balanced');
      const thr=ACCESS_DOMINANCE_THRESHOLD;
      if(pubPct>=thr){ badge.classList.add('dominant-public'); }
      else if(privPct>=thr){ badge.classList.add('dominant-private'); }
      else { badge.classList.add('balanced'); }
    }
    if(t<1){ requestAnimationFrame(frame); } else { c._lastValues=next; }
  }
  requestAnimationFrame(frame);
}
function drawUptimeRing(c, seconds){
  if(!c) return; const ctx=c.getContext('2d'); const W=c.width, H=c.height; const cx=W/2, cy=H/2; const r=Math.min(W,H)/2-4; const th=4;
  const prev=typeof c._lastProgress==='number'?c._lastProgress:0;
  const next=(seconds%60)/60;
  const dur=300; const t0=performance.now();
  function easeOutQuad(t){return 1-(1-t)*(1-t)}
  function frame(now){
    const t=Math.min(1,(now-t0)/dur); const k=easeOutQuad(t); const prog=prev + (next-prev)*k;
    ctx.clearRect(0,0,W,H);
    // Track
    ctx.beginPath(); ctx.arc(cx,cy,r,0,Math.PI*2); ctx.lineWidth=th; ctx.strokeStyle='rgba(120,200,255,.18)'; ctx.stroke();
    // Progress
    ctx.beginPath(); ctx.arc(cx,cy,r,-Math.PI/2, -Math.PI/2 + prog*2*Math.PI); ctx.lineWidth=th; ctx.strokeStyle='#22c55e'; ctx.lineCap='round'; ctx.stroke();
    // Center dot
    ctx.beginPath(); ctx.arc(cx,cy,1.5,0,Math.PI*2); ctx.fillStyle='#94a3b8'; ctx.fill();
    if(t<1){ requestAnimationFrame(frame); } else { c._lastProgress=next; }
  }
  requestAnimationFrame(frame);
}

// Configurable threshold for Access dominance (percent). Default 60%. Persisted via localStorage.
let ACCESS_DOMINANCE_THRESHOLD = Number(localStorage.getItem('settings:accessDominanceThreshold'));
if(!ACCESS_DOMINANCE_THRESHOLD || isNaN(ACCESS_DOMINANCE_THRESHOLD)){
  ACCESS_DOMINANCE_THRESHOLD = 60;
}
// Expose setter for quick adjustment (e.g., setAccessDominanceThreshold(70))
window.setAccessDominanceThreshold = function(p){
  const v = Math.min(100, Math.max(0, Number(p)||0));
  ACCESS_DOMINANCE_THRESHOLD = v;
  localStorage.setItem('settings:accessDominanceThreshold', String(v));
  // Refresh visuals to reflect new threshold
  const cAcc=document.getElementById('overview-access');
  if(cAcc){
    // Force re-render by calling apiOverview which recalculates values
    const ev=new Event('input');
    // Attempt to re-run overview directly
    if(typeof apiOverview==='function') apiOverview();
  }
};
function drawUptimeRing(c, seconds){
  if(!c) return; const ctx=c.getContext('2d'); const W=c.width, H=c.height; const cx=W/2, cy=H/2; const r=Math.min(W,H)/2-4; const th=4;
  const prev=typeof c._lastProgress==='number'?c._lastProgress:0;
  const next=(seconds%60)/60;
  const dur=300; const t0=performance.now();
  function easeOutQuad(t){return 1-(1-t)*(1-t)}
  function frame(now){
    const t=Math.min(1,(now-t0)/dur); const k=easeOutQuad(t); const prog=prev + (next-prev)*k;
    ctx.clearRect(0,0,W,H);
    // Track
    ctx.beginPath(); ctx.arc(cx,cy,r,0,Math.PI*2); ctx.lineWidth=th; ctx.strokeStyle='rgba(120,200,255,.18)'; ctx.stroke();
    // Progress
    ctx.beginPath(); ctx.arc(cx,cy,r,-Math.PI/2, -Math.PI/2 + prog*2*Math.PI); ctx.lineWidth=th; ctx.strokeStyle='#22c55e'; ctx.lineCap='round'; ctx.stroke();
    // Center dot
    ctx.beginPath(); ctx.arc(cx,cy,1.5,0,Math.PI*2); ctx.fillStyle='#94a3b8'; ctx.fill();
    if(t<1){ requestAnimationFrame(frame); } else { c._lastProgress=next; }
  }
  requestAnimationFrame(frame);
}