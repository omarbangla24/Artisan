</div><!-- .content -->
</div><!-- .main -->
</div><!-- .admin-wrap -->
<script>
// Mobile menu
var mb=document.getElementById('menu-btn');
if(window.innerWidth<=768 && mb) mb.style.display='flex';
window.addEventListener('resize',function(){if(mb)mb.style.display=window.innerWidth<=768?'flex':'none';});
// Confirm deletes
document.querySelectorAll('[data-confirm]').forEach(function(el){
  el.addEventListener('click',function(e){
    if(!confirm(el.dataset.confirm||'Are you sure?')){e.preventDefault();}
  });
});
// Auto-dismiss alerts
setTimeout(function(){document.querySelectorAll('.alert-success').forEach(function(a){a.style.opacity='0';a.style.transition='opacity .5s';setTimeout(function(){a.remove();},500);});},3500);
</script>
</body></html>
