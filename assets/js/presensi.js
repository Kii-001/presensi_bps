// Presensi: validasi lokasi (Geolocation API) + selfie kamera (WebRTC getUserMedia)
(function(){
  var root=document.getElementById('presensi');if(!root)return;
  var office={lat:+root.dataset.lat,lng:+root.dataset.lng,radius:+root.dataset.radius};
  var $=function(id){return document.getElementById(id)};
  var video=$('video'),preview=$('preview'),ph=$('camPh'),btnShot=$('btnShot'),btnSubmit=$('btnSubmit'),
      chkLoc=$('chkLoc'),chkFoto=$('chkFoto'),distEl=$('distVal'),meter=$('meter'),msg=$('locMsg'),result=$('result');
  var pos=null,locOk=false,photo=null,stream=null;

  function haversine(a,b,c,d){var R=6371000,r=Math.PI/180,dp=(c-a)*r,dl=(d-b)*r,
    x=Math.sin(dp/2)*Math.sin(dp/2)+Math.cos(a*r)*Math.cos(c*r)*Math.sin(dl/2)*Math.sin(dl/2);
    return 2*R*Math.atan2(Math.sqrt(x),Math.sqrt(1-x))}
  function refresh(){chkLoc.classList.toggle('ok',locOk);chkFoto.classList.toggle('ok',!!photo);btnSubmit.disabled=!(locOk&&photo)}
  function setMsg(t,c){msg.textContent=t;msg.className='locmsg '+c}

  function onPos(p){
    pos={lat:p.coords.latitude,lng:p.coords.longitude,acc:p.coords.accuracy};
    var d=haversine(pos.lat,pos.lng,office.lat,office.lng);locOk=d<=office.radius;
    distEl.textContent=d<1000?Math.round(d):(d/1000).toFixed(1);distEl.nextElementSibling.textContent=(d<1000?'meter':'km')+' dari kantor';
    meter.style.display='block';meter.firstElementChild.style.width=Math.max(4,Math.min(100,100-(d/(office.radius*3))*100))+'%';meter.classList.toggle('out',!locOk);
    setMsg(locOk?'Lokasi sesuai. Anda berada dalam radius '+office.radius+' m.':'Lokasi tidak sesuai. Dekati kantor hingga dalam radius '+office.radius+' m.',locOk?'ok':'bad');
    refresh();
  }
  function onErr(er){locOk=false;refresh();
    setMsg(er.code===1?'Izin lokasi ditolak. Aktifkan izin lokasi di browser lalu muat ulang halaman.':'Lokasi belum terbaca. Pastikan GPS aktif.','bad')}
  if(!navigator.geolocation){setMsg('Browser ini tidak mendukung Geolocation.','bad')}
  else{setMsg('Membaca lokasi Anda...','wait');navigator.geolocation.watchPosition(onPos,onErr,{enableHighAccuracy:true,maximumAge:3000,timeout:20000})}

  function startCam(){
    if(!navigator.mediaDevices||!navigator.mediaDevices.getUserMedia){ph.textContent='Kamera tidak tersedia. Buka lewat localhost atau HTTPS.';return}
    navigator.mediaDevices.getUserMedia({video:{facingMode:'user',width:{ideal:960},height:{ideal:720}},audio:false}).then(function(s){
      stream=s;video.srcObject=s;video.style.display='block';preview.style.display='none';ph.style.display='none';btnShot.disabled=false;
    }).catch(function(){ph.style.display='grid';ph.textContent='Gagal mengakses kamera. Izinkan kamera di browser lalu muat ulang.'});
  }
  btnShot.disabled=true;startCam();

  btnShot.addEventListener('click',function(){
    if(photo){photo=null;preview.style.display='none';video.style.display='block';btnShot.textContent='Ambil foto';refresh();return}
    if(!video.videoWidth)return;
    var c=document.createElement('canvas'),w=640,h=Math.round(w*video.videoHeight/video.videoWidth);c.width=w;c.height=h;
    var x=c.getContext('2d');x.translate(w,0);x.scale(-1,1);x.drawImage(video,0,0,w,h);x.setTransform(1,0,0,1,0,0);
    var d=new Date(),t=d.toLocaleString('id-ID',{dateStyle:'medium',timeStyle:'medium'}),
        j=pos?Math.round(haversine(pos.lat,pos.lng,office.lat,office.lng))+' m dari kantor':'';
    x.fillStyle='rgba(8,24,48,.62)';x.fillRect(0,h-30,w,30);x.fillStyle='#fff';x.font='600 14px sans-serif';x.fillText(t+(j?'  |  '+j:''),10,h-10);
    photo=c.toDataURL('image/jpeg',0.82);preview.src=photo;preview.style.display='block';video.style.display='none';btnShot.textContent='Foto ulang';refresh();
  });

  btnSubmit.addEventListener('click',function(){
    if(!(locOk&&photo&&pos))return;
    btnSubmit.disabled=true;btnSubmit.textContent='Menyimpan...';
    var f=new FormData();f.append('csrf',root.dataset.csrf);f.append('aksi',root.dataset.aksi);
    f.append('lat',pos.lat);f.append('lng',pos.lng);f.append('foto',photo);
    fetch(root.dataset.endpoint,{method:'POST',body:f,credentials:'same-origin'}).then(function(r){return r.json()}).then(function(j){
      if(j.ok){if(stream)stream.getTracks().forEach(function(t){t.stop()});location.href=root.dataset.back}
      else{result.className='alert a-err';result.textContent=j.msg;result.style.display='block';btnSubmit.textContent=root.dataset.label;refresh()}
    }).catch(function(){result.className='alert a-err';result.textContent='Gagal terhubung ke server.';result.style.display='block';btnSubmit.textContent=root.dataset.label;refresh()});
  });
  refresh();
})();
