document.addEventListener('DOMContentLoaded', () => {
  const list   = document.getElementById('project-list');
  const loadEl = document.getElementById('loading');
  const btnNew = document.getElementById('create-project');
  const role   = document.body.dataset.userRole;

  let page=1, perPage=20, hasMore=true, busy=false;

  function card(p){
    const c=document.createElement('div');
    c.className='template-card';   // reuse card styling
    c.innerHTML=`
      <div class="card-header">
        <h3>${p.name}</h3>
        <span class="badge">${p.status}</span>
      </div>
      <p>${p.client_name}</p>
      <p>${p.engagement_start} → ${p.engagement_end}</p>
      <div class="card-actions">
        <button data-id="${p.id}" data-act="open">Open</button>
        ${role!=='client'?`<button data-id="${p.id}" data-act="edit">Edit</button>`:''}
      </div>`;
    return c;
  }

  function load(){
    if(!hasMore||busy) return; busy=true; loadEl.style.display='block';
    fetch(`projects_api.php?page=${page}&per_page=${perPage}`)
      .then(r=>r.json()).then(d=>{
        d.projects.forEach(p=>list.appendChild(card(p)));
        hasMore=d.has_more; page++;
      }).finally(()=>{busy=false;loadEl.style.display=hasMore?'block':'none';});
  }

  new IntersectionObserver(e=>{if(e[0].isIntersecting)load();},
    {rootMargin:'200px'}).observe(loadEl);

  list.addEventListener('click',e=>{
    const b=e.target.closest('button'); if(!b)return;
    const id=b.dataset.id;
    if(b.dataset.act==='open') window.location=`project_builder.php?id=${id}&view=1`;
    if(b.dataset.act==='edit') window.location=`project_builder.php?id=${id}`;
  });

  btnNew?.addEventListener('click',()=>location='project_builder.php');
  load();
});
