/* Local settings preview and optional native changelog dialog. */
(() => {
 'use strict';
 const form=document.getElementById('ddw-gpe-form'); if(!form || form.dataset.bound)return;form.dataset.bound='1';
 document.querySelectorAll('[data-pattern]').forEach(button=>button.addEventListener('click',()=>{document.getElementById('ddw-pattern').value=button.dataset.pattern;form.dispatchEvent(new Event('input'));}));
 const trigger=document.getElementById('ddw-preview-button'), status=document.getElementById('ddw-preview-status'), list=document.getElementById('ddw-preview-results');
 const ready=!trigger.disabled;
 let request=0;
 trigger.addEventListener('click',async()=>{
  if(!ready)return;
  const id=++request, data=new FormData(form);
  data.set('action','ddw_gpe_preview');data.set('_ajax_nonce',ddwGpe.nonce);data.set('project_path',document.getElementById('ddw-preview-path').value);
  trigger.disabled=true;list.replaceChildren();status.textContent='';
  try {const response=await fetch(ddwGpe.url,{method:'POST',credentials:'same-origin',body:data});const json=await response.json();if(id!==request)return;
   if(!json.success)throw new Error(json.data?.message || ddwGpe.error);
   status.textContent=json.data.message;
   json.data.rows.forEach(row=>{const li=document.createElement('li'),code=document.createElement('code');li.append(document.createTextNode((row.set_label || row.set)+' → '));code.textContent=row.name;li.append(code);const explanation=document.createElement('p');explanation.className='description';explanation.textContent=row.explanation;li.append(explanation);list.append(li);});
  }catch(error){if(id===request)status.textContent=error.message || ddwGpe.error;}finally{if(id===request)trigger.disabled=false;}
 });
 const invalidate=()=>{request++;trigger.disabled=!ready;status.textContent='';list.replaceChildren();};
 form.addEventListener('input',invalidate);
 document.getElementById('ddw-preview-path').addEventListener('input',invalidate);
 // Search is explicit and paginated; typing never queries every project.
 const search=document.getElementById('ddw-project-search'),searchButton=document.getElementById('ddw-project-search-button'),table=document.getElementById('ddw-project-table'),projectStatus=document.getElementById('ddw-project-status'),missing=document.getElementById('ddw-project-missing'),prev=document.getElementById('ddw-project-prev'),next=document.getElementById('ddw-project-next');
 let projectRequest=0,currentPage=1;
 const loadProjects=async(pageNumber=1)=>{
  if(!ready)return;
  const id=++projectRequest,data=new FormData(form);
  data.set('action','ddw_gpe_projects');data.set('_ajax_nonce',ddwGpe.nonce);data.set('search',search.value);data.set('project_page',pageNumber);
  searchButton.disabled=true;prev.hidden=true;next.hidden=true;table.hidden=true;table.tBodies[0].replaceChildren();missing.replaceChildren();projectStatus.textContent='';
  try{
   const response=await fetch(ddwGpe.url,{method:'POST',credentials:'same-origin',body:data}),json=await response.json();
   if(id!==projectRequest)return;if(!json.success)throw Error(json.data?.message || ddwGpe.error);
   const result=json.data;currentPage=result.page;projectStatus.textContent=result.controller+(result.rows.length ? '' : ' '+ddwGpe.labels.empty);
   if(result.missing.length){const p=document.createElement('p');p.textContent=ddwGpe.labels.missing+' '+result.missing.join(', ');missing.append(p);}
   result.rows.forEach(row=>{
    const tr=document.createElement('tr'),path=document.createElement('td'),base=document.createElement('td'),checks=document.createElement('td'),actions=document.createElement('td'),code=document.createElement('code'),input=document.createElement('input');
    path.textContent=row.path;input.type='text';input.value=row.base;input.maxLength=160;input.className='ddw-gpe-base';input.setAttribute('aria-label',ddwGpe.labels.base+' '+row.path);base.append(input);code.textContent=row.example || '—';checks.append(code);
    row.issues.forEach(issue=>{const p=document.createElement('p');p.textContent=issue;checks.append(p);});
    const use=document.createElement('button');use.type='button';use.className='button';use.textContent=ddwGpe.labels.map;
    use.addEventListener('click',()=>{
     if(!/^[A-Za-z0-9][A-Za-z0-9_-]{0,159}$/.test(input.value)){projectStatus.textContent=ddwGpe.labels.invalid;input.focus();return;}
     const field=document.getElementById('ddw-projects'),lines=field.value.split(/\r\n|\r|\n/);let found=false;
     const matching=lines.filter(line=>line.split('=',1)[0].trim()===row.path);
     if(matching.some(line=>line.split('=').slice(1).join('=').trim()!==input.value) && !window.confirm(ddwGpe.labels.replace))return;
     const updated=lines.map(line=>{if(line.split('=',1)[0].trim()!==row.path)return line;if(found)return '';found=true;return row.path+' = '+input.value;});
     if(!found)updated.push(row.path+' = '+input.value);field.value=updated.filter(line=>line.trim()).join('\n');field.dispatchEvent(new Event('input',{bubbles:true}));field.focus();
    });
    const preview=document.createElement('button');preview.type='button';preview.className='button';preview.textContent=ddwGpe.labels.preview;preview.addEventListener('click',()=>{const field=document.getElementById('ddw-preview-path');field.value=row.path;field.dispatchEvent(new Event('input'));trigger.click();document.getElementById('ddw-preview-title').scrollIntoView({block:'start'});});
    actions.append(use,document.createTextNode(' '),preview);tr.append(path,base,checks,actions);table.tBodies[0].append(tr);
   });
   table.hidden=!result.rows.length;prev.hidden=currentPage<=1;next.hidden=!result.more;
  }catch(error){if(id===projectRequest)projectStatus.textContent=error.message || ddwGpe.error;}finally{if(id===projectRequest)searchButton.disabled=false;}
 };
 searchButton.addEventListener('click',()=>loadProjects());prev.addEventListener('click',()=>loadProjects(currentPage-1));next.addEventListener('click',()=>loadProjects(currentPage+1));
 const invalidateProjects=()=>{projectRequest++;searchButton.disabled=!ready;prev.hidden=true;next.hidden=true;table.hidden=true;missing.replaceChildren();projectStatus.textContent=ddwGpe.labels.changed;};
 search.addEventListener('input',invalidateProjects);search.addEventListener('keydown',event=>{if(event.key==='Enter'){event.preventDefault();loadProjects();}});form.addEventListener('input',invalidateProjects);
 const file=document.getElementById('ddw-import-file');let fileRequest=0;
 file.addEventListener('change',async()=>{const id=++fileRequest,json=document.getElementById('ddw-import-json'),message=document.getElementById('ddw-import-status');message.textContent='';json.value='';const selected=file.files[0];if(!selected)return;
  if(selected.size>65536){message.textContent=ddwGpe.labels.file;return;}try{const text=await selected.text();if(id===fileRequest)json.value=text;}catch(error){if(id===fileRequest)message.textContent=ddwGpe.labels.file;}
 });
 // WordPress moves notices after .wp-header-end. Also repair late foreign
 // insertions into this header only, preserving the original notice nodes.
 const header=document.getElementById('ddw-gpe-header'),marker=document.getElementById('ddw-gpe-header-end');
 const protectHeader=()=>{
  const notices=header.querySelectorAll('.notice,.updated,.error');
  if(notices.length){const fragment=document.createDocumentFragment();notices.forEach(notice=>fragment.append(notice));marker.after(fragment);}
 };
 protectHeader();
 new MutationObserver(protectHeader).observe(header,{childList:true,subtree:true});
 // Both dialogs use the native close form and real documentation links as fallback.
 const nativeOpen=window.HTMLDialogElement?.prototype.showModal,nativeClose=window.HTMLDialogElement?.prototype.close;
 document.querySelectorAll('dialog.ddw-gpe-history').forEach(dialog=>{
  let opener=null,jump=null;
  const close=()=>{if(dialog.open && nativeClose)nativeClose.call(dialog);};
  document.querySelectorAll('[data-ddw-dialog="'+dialog.id+'"]').forEach(link=>{
   link.addEventListener('click',event=>{
    if(!nativeOpen || event.button!==0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey)return;
    try{
     opener=link;jump=null;dialog.hidden=false;nativeOpen.call(dialog);
     const content=dialog.querySelector('.ddw-gpe-history-content');if(content)content.scrollTop=0;
     event.preventDefault();
    }catch(error){if(dialog.open && nativeClose)nativeClose.call(dialog);dialog.hidden=true;}
   });
  });
  dialog.addEventListener('close',()=>{
   dialog.hidden=true;
   const target=jump || opener;jump=null;
   if(target){target.focus({preventScroll:true});if(target!==opener)target.scrollIntoView({block:'center'});}
  });
  dialog.addEventListener('keydown',event=>{
   if(!dialog.open)return;
   if(event.key==='Escape'){close();event.preventDefault();return;}
   if(event.key==='Tab'){
    const items=[...dialog.querySelectorAll('a[href],button:not([disabled]),input:not([disabled]),[tabindex]:not([tabindex="-1"])')].filter(item=>!item.hidden && item.getClientRects().length);
    const first=items[0],last=items[items.length-1],active=document.activeElement;
    if(first && ((!event.shiftKey && active===last) || (event.shiftKey && (active===first || !items.includes(active))))){event.preventDefault();(event.shiftKey?last:first).focus();}
   }
  });
  dialog.addEventListener('click',event=>{
   const link=event.target.closest('[data-ddw-guide-target]');
   if(link && dialog.contains(link)){
    const target=document.getElementById(link.dataset.ddwGuideTarget);
    if(target){jump=target;event.preventDefault();close();}
    return;
   }
   if(event.target===dialog){const r=dialog.getBoundingClientRect();if(event.clientX<r.left||event.clientX>r.right||event.clientY<r.top||event.clientY>r.bottom)close();}
  });
 });
})();
