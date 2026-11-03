(function(){
  var side=document.getElementById('side'),scrim=document.getElementById('scrim'),b=document.getElementById('burger');
  function tg(){side.classList.toggle('open');scrim.classList.toggle('open')}
  if(b){b.addEventListener('click',tg);scrim.addEventListener('click',tg)}
  var lb=document.getElementById('lightbox');
  document.addEventListener('click',function(ev){
    var t=ev.target;
    if(t.classList&&t.classList.contains('zoomable')){lb.querySelector('img').src=t.src;lb.classList.add('on')}
    else if(lb&&lb.classList.contains('on')){lb.classList.remove('on')}
    var c=t.closest&&t.closest('[data-confirm]');
    if(c&&!confirm(c.getAttribute('data-confirm'))){ev.preventDefault()}
  });
  document.querySelectorAll('[data-clock]').forEach(function(el){
    function t(){var d=new Date(),p=function(n){return String(n).padStart(2,'0')};el.textContent=p(d.getHours())+':'+p(d.getMinutes())+':'+p(d.getSeconds())}
    t();setInterval(t,1000);
  });
})();
