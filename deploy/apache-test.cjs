const {chromium}=require('playwright');
const fs=require('fs'),assert=require('assert/strict');
(async()=>{
 const browser=await chromium.launch({headless:true,args:['--no-sandbox']});
 // Self-signed certificate ONLY in this isolated Docker rehearsal.
 const context=await browser.newContext({ignoreHTTPSErrors:true});
 const request=context.request,base='https://carlosburgosh.com';
 for(const url of ['http://carlosburgosh.com/admin','https://www.carlosburgosh.com/admin']){
  const r=await request.get(url,{maxRedirects:0});assert.equal(r.status(),301);assert.equal(r.headers().location,base+'/admin');
 }
 for(const path of ['/','/admin','/articles/not-existing','/api/public/settings']){
  assert.equal((await request.get(base+path)).status(),200,path);
 }
 for(const path of ['/assets/missing.js','/missing.css','/api/not-found'])assert.equal((await request.get(base+path)).status(),404,path);
 for(const path of ['/.env','/.git/config','/private/api/config.local.php','/database/001_foundation.sql','/storage/admin-activation.html','/backups/database.sql'])assert.ok([403,404].includes((await request.get(base+path)).status()),path);
 assert.equal((await request.get(base+'/api/admin/evidence/1')).status(),401);
 const session=await request.get(base+'/api/admin/session');
 const cookie=(await context.cookies(base+'/api')).find(c=>c.name==='cb_session');
 assert.ok(cookie);assert.equal(cookie.secure,true);assert.equal(cookie.httpOnly,true);assert.equal(cookie.sameSite,'Strict');
 const csrf=(await session.json()).csrf,{password}=JSON.parse(fs.readFileSync('/storage/test-credentials.json'));
 assert.equal((await request.post(base+'/api/admin/login',{data:{email:'admin@example.invalid',password}})).status(),403);
 assert.equal((await request.post(base+'/api/admin/login',{headers:{'X-CSRF-Token':csrf},data:{email:'admin@example.invalid',password}})).status(),200);
 assert.ok((await (await request.get(base+'/api/admin/session')).json()).user);
 const fresh=(await (await request.get(base+'/api/admin/session')).json()).csrf;
 const upload=await request.post(base+'/api/admin/media',{headers:{'X-CSRF-Token':fresh},multipart:{file:{name:'test.png',mimeType:'image/png',buffer:fs.readFileSync('/storage/test.png')},title:'Disposable Apache check'}});
 assert.equal(upload.status(),201);const media=await upload.json();assert.equal((await request.get(base+'/api/public/media/'+media.id)).status(),200);
 const pdf=await request.post(base+'/api/admin/media',{headers:{'X-CSRF-Token':fresh},multipart:{file:{name:'test.pdf',mimeType:'application/pdf',buffer:fs.readFileSync('/storage/test.pdf')}}});
 assert.equal(pdf.status(),process.env.CB_EXPECT_PDF==='1'?201:422);
 if(process.env.CB_EXPECT_PDF!=='1')assert.match((await pdf.json()).error,/deshabilitada/);
 const invalid=await request.post(base+'/api/admin/media',{headers:{'X-CSRF-Token':fresh},multipart:{file:{name:'broken.pdf',mimeType:'application/pdf',buffer:Buffer.from('%PDF-1.4\ninvalid')}}});
 assert.equal(invalid.status(),422);
 assert.equal((await request.post(base+'/api/admin/logout',{headers:{'X-CSRF-Token':fresh},data:{}})).status(),200);
 assert.equal((await request.get(base+'/api/admin/settings')).status(),401);
 const page=await context.newPage(),errors=[];page.on('pageerror',e=>errors.push(e.message));
 for(const viewport of [{width:1440,height:1000},{width:390,height:844}]){
  await page.setViewportSize(viewport);await page.goto(base);await page.locator('main').waitFor();
  assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1));
  await page.goto(base+'/admin');await page.getByRole('button',{name:'Ingresar al panel'}).waitFor();
 }
 assert.deepEqual(errors,[]);await browser.close();
 console.log('PASS Apache TLS: redirects, SPA, API, assets, private paths, secure cookies, login, CSRF, logout, image, PDF policy, desktop/mobile.');
})().catch(e=>{console.error(e);process.exit(1)});
