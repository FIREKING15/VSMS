document.querySelectorAll('.alert').forEach(el=>{
  setTimeout(()=>{el.style.transition='opacity .4s';el.style.opacity='0';setTimeout(()=>el.remove(),400)},4000);
});
document.querySelectorAll('.confirm-delete').forEach(btn=>{
  btn.addEventListener('click',e=>{if(!confirm('Delete this record?'))e.preventDefault();});
});
