(() => {
 const read=(key)=>{try{return JSON.parse(localStorage.getItem(key)||'[]')}catch{return []}};
 const clientes=read('cx_clientes'), feedbacks=read('cx_feedbacks'), acoes=read('cx_acoes');
 const set=(id,value)=>{const el=document.getElementById(id);if(el)el.textContent=value};
 set('dashboardPeriod',new Intl.DateTimeFormat('pt-BR',{day:'2-digit',month:'short'}).format(new Date()));
 set('metricClients',clientes.length);set('metricFeedbacks',feedbacks.length);
 const notas=feedbacks.map(f=>Number(f.nota)).filter(n=>Number.isFinite(n)&&n>0);
 set('metricSatisfaction',notas.length?(notas.reduce((a,b)=>a+b,0)/notas.length).toFixed(1).replace('.',','):'—');
 set('metricComplaints',feedbacks.filter(f=>String(f.categoria).toLowerCase().includes('reclama')&&!['resolvido','concluído','concluido'].includes(String(f.status).toLowerCase())).length);
 const recentes=document.getElementById('recentFeedbacks');
 if(feedbacks.length&&recentes){recentes.innerHTML=feedbacks.slice(-5).reverse().map(f=>`<div class="feedback-row"><span class="client-avatar">${escapeHtml((f.cliente||'C').slice(0,1).toUpperCase())}</span><span class="feedback-info"><strong>${escapeHtml(f.cliente||'Cliente')}</strong><small>${escapeHtml(f.comentario||'Feedback registrado')}</small></span><span class="feedback-rating">${escapeHtml(f.nota||'—')} ★</span><span class="category-pill">${escapeHtml(f.categoria||'')}</span></div>`).join('')}
 const alertas=document.getElementById('dashboardAlerts');
 const pendentes=acoes.filter(a=>a.prazo&&a.prazo<new Date().toISOString().slice(0,10)&&a.status!=='Concluído');
 set('alertCount',`${pendentes.length} alertas`);
 if(pendentes.length&&alertas)alertas.innerHTML=pendentes.map(a=>`<div class="attention-item"><span class="attention-icon warning">!</span><div><strong>${escapeHtml(a.titulo)}</strong><p>Ação com prazo vencido.</p><a href="index.php?page=melhorias">Ver ação</a></div></div>`).join('');
 if(feedbacks.length){const cat=document.getElementById('categoryEmpty');if(cat)cat.textContent=[...new Set(feedbacks.map(f=>f.categoria).filter(Boolean))].join(' · ')||'Sem categorias';}
 function escapeHtml(value){return String(value).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));}
})();
