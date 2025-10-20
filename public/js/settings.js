// Microservices Settings UI Logic (API Pool System)
(function(){
  const KEY='ms:state';
  const D={endpoints:[],roles:['Admin','Developer','Viewer','Operator'],rbac:{},tokens:[],logs:[],services:['auth','laporan','kapal','signature'],routes:[],lb:{},keys:[],ips:[],env:[],servers:[],versions:[],webhooks:[],schedules:[],status:[],usage:[]};
  let S=load(); let start=Date.now();
  function load(){try{const j=localStorage.getItem(KEY);return j?JSON.parse(j):D;}catch(e){return D}}
  function save(){localStorage.setItem(KEY,JSON.stringify(S))}
  function id(){return Math.random().toString(36).slice(2,10)}
  function log(msg){S.logs.unshift({t:new Date().toISOString(),msg});renderLogs();save()}
  // Tabs
  document.getElementById('tabs').addEventListener('click',e=>{if(e.target.classList.contains('tab')){document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));e.target.classList.add('active');const k=e.target.dataset.tab;document.querySelectorAll('.panel').forEach(p=>p.classList.remove('active'));document.getElementById('panel-'+k).classList.add('active')}});
  // Stats
  function stat(){byId('stat-endpoints').textContent=S.endpoints.length+' API';byId('stat-roles').textContent=S.roles.length+' Role';byId('stat-uptime').textContent='Uptime: '+(((Date.now()-start)/1000)|0)+'s'}
  function byId(x){return document.getElementById(x)}
  // API Config
  byId('btn-add-api').addEventListener('click',()=>{
    const name=val('api-name'),url=val('api-url'),method=val('api-method'),group=val('api-group'),version=val('api-version'),params=val('api-params');
    if(!name||!url){alert('Nama & URL wajib');return}
    const ep=S._edit?S.endpoints.find(e=>e.id===S._edit):{id:id(),active:true};
    Object.assign(ep,{name,url,method,group,version,params});
    if(!S._edit){S.endpoints.push(ep); log('Tambah API: '+name)} else {log('Update API: '+name); S._edit=null}
    clearApiForm(); renderApi(); stat(); save()
  });
  function clearApiForm(){['api-name','api-url','api-params','api-group','api-version'].forEach(k=>byId(k).value='');}
  function renderApi(){const tb=byId('api-table');tb.innerHTML='';S.endpoints.forEach(ep=>{
    const tr=document.createElement('tr');
    tr.innerHTML=`<td>${ep.name}</td><td>${ep.group||'-'}</td><td>${ep.method}</td><td>${ep.version||'-'}</td><td>${ep.active?'aktif':'nonaktif'}</td>
    <td>
      <button class="btn" data-act="test" data-id="${ep.id}">Test</button>
      <button class="btn" data-act="edit" data-id="${ep.id}">Edit</button>
      <button class="btn danger" data-act="del" data-id="${ep.id}">Delete</button>
      <button class="btn secondary" data-act="toggle" data-id="${ep.id}">${ep.active?'Disable':'Enable'}</button>
    </td>`;tb.appendChild(tr)
  })}
  byId('api-table').addEventListener('click',async e=>{
    const b=e.target.closest('button'); if(!b) return; const id=b.dataset.id; const act=b.dataset.act; const ep=S.endpoints.find(x=>x.id===id); if(!ep) return;
    if(act==='edit'){S._edit=id;['api-name','api-url','api-group','api-version','api-params'].forEach(k=>byId(k).value=ep[k]||'');byId('api-method').value=ep.method;}
    if(act==='del'){S.endpoints=S.endpoints.filter(x=>x.id!==id); log('Hapus API: '+ep.name); renderApi(); renderMatrix(); stat(); save()}
    if(act==='toggle'){ep.active=!ep.active; log((ep.active?'Aktifkan ':'Nonaktifkan ')+ep.name); renderApi(); save()}
    if(act==='test'){const res=await test(ep); alert(`Status: ${res.status} • ${res.ms}ms`)}
  });
  async function test(ep){const t0=performance.now(); let status='ERR'; try{const opt={method:ep.method||'GET'}; const r=await fetch(ep.url,opt); status=r.status; }catch(e){status='ERR'} const ms=Math.round(performance.now()-t0); S.usage.push({name:ep.name,ms,status,ts:Date.now()}); if(val('alert-th')&&ms>+val('alert-th')) log('ALERT lambat '+ep.name+' '+ms+'ms'); renderUsage(); save(); return {status,ms}}
  // Backup/Restore
  byId('btn-backup').addEventListener('click',()=>{const blob=new Blob([JSON.stringify(S,null,2)],{type:'application/json'}); const a=document.createElement('a'); a.href=URL.createObjectURL(blob); a.download='api-pool-config.json'; a.click(); URL.revokeObjectURL(a.href); log('Backup konfigurasi')});
  byId('restore-file').addEventListener('change',async e=>{const f=e.target.files[0]; if(!f) return; const txt=await f.text(); try{S=JSON.parse(txt); save(); init(); log('Restore konfigurasi berhasil')}catch(err){alert('Restore gagal: '+err)}});

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

  // Init
  function init(){renderApi(); renderRoles(); renderMatrix(); renderTokens(); renderRoutes(); renderServices(); renderKeys(); renderIPs(); renderLogs(); renderUsage(); renderStatus(); renderCron(); renderEnv(); renderSrv(); renderWh(); drawDeps(); stat()}
  init(); setInterval(()=>{stat()},1000);
})();