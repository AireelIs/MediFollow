/* Network-only app: no patient pages, IC values, credentials or audio are cached. */
'use strict';
self.addEventListener('install',()=>self.skipWaiting());
self.addEventListener('activate',event=>event.waitUntil(self.clients.claim()));
self.addEventListener('fetch',event=>{
  if(event.request.mode!=='navigate' || event.request.method!=='GET')return;
  event.respondWith(fetch(event.request).catch(()=>new Response(`<!doctype html><html lang="en"><meta name="viewport" content="width=device-width,initial-scale=1"><title>MediFollow | Offline</title><style>body{font:16px system-ui;color:#233653;background:#f5f7ff;padding:50px 24px;line-height:1.6}main{max-width:420px;margin:auto}button{background:#2463db;color:white;border:0;border-radius:10px;padding:13px 24px;font:inherit}</style><main><h1>You're offline</h1><p>Reconnect to the internet to access MediFollow.</p><button onclick="location.reload()">Try again</button></main></html>`,{status:503,headers:{'Content-Type':'text/html; charset=utf-8','Cache-Control':'no-store'}})));
});
