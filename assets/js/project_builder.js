document.addEventListener('DOMContentLoaded',()=>{
  if(window.SimpleMDE){
    new SimpleMDE({ element: document.getElementById('exec-summary'), spellChecker:false, status:false });
  }
});
