import assert from 'node:assert/strict';
import fs from 'node:fs';
const base=process.env.CB_TEST_API, {password}=JSON.parse(fs.readFileSync('/storage/test-credentials.json'));
let count=0;
function client(){return {cookie:'',csrf:''}}
async function call(c,path,method='GET',body,expected=200,csrf=true){
 const r=await fetch(base+path,{method,headers:{...(body instanceof FormData?{}:{'Content-Type':'application/json'}),...(c.cookie?{Cookie:c.cookie}:{}),...(csrf&&c.csrf?{'X-CSRF-Token':c.csrf}:{})},body:body===undefined?undefined:body instanceof FormData?body:JSON.stringify(body)});
 const cookie=r.headers.get('set-cookie');if(cookie){assert.match(cookie,/HttpOnly/i);assert.match(cookie,/SameSite=Strict/i);c.cookie=cookie.split(';')[0]}
 const text=await r.text();let d;try{d=JSON.parse(text)}catch{d=text}
 assert.equal(r.status,expected,`${method} ${path}: ${JSON.stringify(d).slice(0,300)}`);count++;if(d.csrf)c.csrf=d.csrf;return d;
}
const anonymous=client(),admin=client(),editor=client();
await call(anonymous,'/admin/content/articles','GET',undefined,401);
for(const c of [admin,editor])await call(c,'/admin/session');
await call(admin,'/admin/login','POST',{email:'admin@example.invalid',password},403,false);
await call(admin,'/admin/login','POST',{email:'admin@example.invalid',password:'incorrect'},401);
await call(admin,'/admin/login','POST',{email:'admin@example.invalid',password});
await call(editor,'/admin/login','POST',{email:'editor@example.invalid',password});
await call(editor,'/admin/users','GET',undefined,403);
await call(editor,'/admin/evidence?case_id=1','GET',undefined,403);
let settings=await call(admin,'/admin/settings');
settings.content.heroTitle='Persistencia CMS 002';
await call(admin,'/admin/settings','PUT',settings,403,false);
await call(admin,'/admin/settings','PUT',{...settings,content:{...settings.content,facebook:'javascript:alert(1)'}},422);
await call(admin,'/admin/settings','PUT',settings);
await call(admin,'/admin/settings','PUT',settings,409);
async function upload(name,bytes,expected=201,privateCase=null){let form=new FormData();form.set('file',new Blob([bytes]),name);form.set('title','Prueba');form.set('alt','Descripción accesible');if(privateCase)form.set('case_id',privateCase);return call(admin,privateCase?'/admin/evidence':'/admin/media','POST',form,expected)}
let media;
for(const ext of ['png','jpg','webp','pdf']){const m=await upload('fixture.'+ext,fs.readFileSync('/storage/test.'+ext));if(ext==='png')media=m;}
await upload('fake.png','not an image',422);await upload('fake.jpg',fs.readFileSync('/storage/test.png'),422);await upload('bad.pdf','%PDF-1.7 broken',422);await upload('shell.php','<?php echo 1;',422);await upload('large.png',Buffer.alloc(9*1024*1024),413);
await call(admin,`/admin/media/${media.id}`,'PUT',{version:1,title:'Imagen de prueba',alt:'Texto alternativo'});
const created=[];
for(const kind of ['specialties','team','articles','multimedia']){
 let r=await call(editor,`/admin/content/${kind}`,'POST',{title:'Prueba '+kind,slug:'prueba-'+kind,content:{format:'video',body:'<script>window.pwned=true</script>',image_id:media.id},order:0,status:'draft'},201);
 await call(anonymous,`/public/content/${kind}/${r.slug}`,'GET',undefined,404);
 await call(editor,`/admin/content/${kind}/${r.id}`,'PUT',{...r,title:'Cambio',version:0},409);
 r=await call(editor,`/admin/content/${kind}/${r.id}`,'PUT',{...r,status:'published'});
 assert.equal((await call(anonymous,`/public/content/${kind}/${r.slug}`)).title,r.title);
 assert.equal((await call(editor,`/admin/content/${kind}?status=published&q=Prueba`)).total,1);
 await call(editor,`/admin/content/${kind}`,'POST',{...r,status:'draft'},409);
 created.push(r);
}
await call(admin,`/admin/media/${media.id}`,'DELETE',{version:2},409);
await call(editor,'/admin/content/multimedia','POST',{title:'URL',slug:'malicious-url',content:{format:'video',url:'javascript:alert(1)'}},422);
let c=await call(editor,'/admin/content/cases','POST',{title:'Caso sintético',slug:'caso-prueba',content:{context:'Prueba sin clientes reales'},status:'draft'},201);
await call(admin,`/admin/content/cases/${c.id}`,'PUT',{...c,status:'published'},422);
await call(editor,`/admin/content/cases/${c.id}`,'PUT',{...c,status:'approved'},403);
c=await call(editor,`/admin/content/cases/${c.id}`,'PUT',{...c,status:'review'});
await call(admin,`/admin/content/cases/${c.id}`,'PUT',{...c,status:'approved',approval:{basis:'Prueba',anonymization:'Total',consent_required:true}},422);
const evidence=await upload('consent.pdf',fs.readFileSync('/storage/test.pdf'),201,c.id);
await call(anonymous,`/public/media/${evidence.id}`,'GET',undefined,404);
await call(editor,`/admin/evidence/${evidence.id}`,'GET',undefined,403);
c=await call(admin,`/admin/content/cases/${c.id}`,'PUT',{...c,status:'approved',approval:{basis:'Evidencia sintética aislada',anonymization:'Total',consent_required:true,evidence_id:evidence.id}});
c=await call(admin,`/admin/content/cases/${c.id}`,'PUT',{...c,status:'published'});
const pub=await call(anonymous,'/public/content/cases/caso-prueba');assert.ok(!JSON.stringify(pub).includes('evidence'));
c=await call(editor,`/admin/content/cases/${c.id}`,'PUT',{...c,title:'Cambio que revoca',status:'review'});assert.equal(c.status,'review');assert.equal(c.approved_by,null);
await call(anonymous,'/public/content/cases/caso-prueba','GET',undefined,404);
await call(editor,`/admin/content/cases/${c.id}`,'DELETE',{version:c.version});
for(const r of created.slice(0,3)){await call(editor,`/admin/content/${r.kind}/${r.id}`,'PUT',{...r,status:'withdrawn'});await call(anonymous,`/public/content/${r.kind}/${r.slug}`,'GET',undefined,404);}
const accounts=(await call(admin,'/admin/users')).items, a=accounts.find(x=>x.role==='admin'), e=accounts.find(x=>x.role==='editor');
await call(admin,`/admin/users/${a.id}`,'PUT',{...a,active:false,version:Number(a.version)},422);
await call(admin,`/admin/users/${e.id}`,'PUT',{...e,active:false,version:Number(e.version)});
await call(editor,'/admin/settings','GET',undefined,401);
await call(admin,`/admin/users/${e.id}`,'PUT',{...e,active:true,version:Number(e.version)+1});
await call(editor,'/admin/login','POST',{email:e.email,password});
const reset=await call(admin,`/admin/users/${e.id}/reset`,'POST',{});
const replacement=password+'a';await call(editor,'/admin/reset-password','POST',{token:reset.token,password:replacement});
await call(editor,'/admin/settings','GET',undefined,401);
await call(editor,'/admin/reset-password','POST',{token:reset.token,password:replacement},422);
await call(admin,'/admin/logout','POST',{});await call(admin,'/admin/settings','GET',undefined,401);
// Rate limit deliberately runs last; fixtures remain isolated from local site.
const attacker=client();await call(attacker,'/admin/session');let limited=false;for(let i=0;i<12;i++){const r=await fetch(base+'/admin/login',{method:'POST',headers:{Cookie:attacker.cookie,'Content-Type':'application/json','X-CSRF-Token':attacker.csrf},body:JSON.stringify({email:'nobody@example.invalid',password:'invalid'})});if(r.status===429){limited=true;break}}assert.ok(limited);
console.log(`PASS ${count} API assertions: auth, CSRF, CRUD, drafts, conflicts, cases, evidence, uploads, roles, account revocation, reset, rate limit.`);
