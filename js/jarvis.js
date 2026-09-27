(()=>{
  const $=(selector,root=document)=>root.querySelector(selector);
  const KEY='hoa_jarvis_chat_v1';
  let history=[];
  let busy=false;
  const welcome='<div class="jarvis-welcome"><div class="jarvis-welcome-icon"><i class="ti ti-sparkles"></i></div><h2>J.A.R.V.I.S. listo para asistirle.</h2><p>Describa los síntomas, el vehículo y las condiciones en que ocurre la falla. Analizaré la información y le ayudaré a organizar el diagnóstico.</p></div>';

  function readHistory(){try{const value=JSON.parse(localStorage.getItem(STORE)||'[]');return Array.isArray(value)?value.filter(x=>x&&['user','assistant'].includes(x.role)&&typeof x.text==='string').slice(-50):[]}catch{return []}}
  function saveHistory(){try{localStorage.setItem(KEY,JSON.stringify(history.slice(-50)))}catch{setState('ALMACENAMIENTO NO DISPONIBLE')}}
  function setState(text){const el=$('#jarvis-state');if(el)el.textContent=text}
  function bubble(message){
    const row=document.createElement('article');row.className=`jarvis-message ${message.role==='user'?'user':''}${message.error?' jarvis-error':''}`;
    const mark=document.createElement('span');mark.className='jarvis-message-mark';mark.innerHTML=message.role==='user'?'<i class="ti ti-user"></i>':'<i class="ti ti-hexagon-letter-h"></i>';
    const body=document.createElement('div');body.className='jarvis-message-body';
    const name=document.createElement('span');name.className='jarvis-message-name';name.textContent=message.error?'SISTEMA':message.role==='user'?'TALLER':'J.A.R.V.I.S.';
    const text=document.createElement('div');text.textContent=message.text;body.append(name,text);row.append(mark,body);return row;
  }
  function render(){
    const list=$('#jarvis-messages');if(!list)return;
    list.replaceChildren();if(!history.length)list.innerHTML=welcome;
    history.forEach(item=>list.append(bubble(item)));
    list.scrollTop=list.scrollHeight;
    const suggestions=$('#jarvis-suggestions');if(suggestions)suggestions.hidden=history.length>0;
  }
  function typing(){const row=document.createElement('div');row.className='jarvis-message';row.id='jarvis-typing';row.innerHTML='<span class="jarvis-message-mark"><i class="ti ti-hexagon-letter-h"></i></span><div class="jarvis-message-body"><span class="jarvis-message-name">J.A.R.V.I.S. · PROCESANDO</span><span class="jarvis-typing"><i></i><i></i><i></i></span></div>';$('#jarvis-messages')?.append(row);const list=$('#jarvis-messages');if(list)list.scrollTop=list.scrollHeight}
  async function send(text){
    const message=text.trim();if(!message||busy)return;
    const input=$('#jarvis-input'),button=$('#jarvis-send');
    history.push({role:'user',text:message});saveHistory();render();
    if(input){input.value='';input.style.height='auto'}
    busy=true;if(button)button.disabled=true;setState('ANALIZANDO CONSULTA');typing();
    const transcript=history.filter(x=>!x.error&&['user','assistant'].includes(x.role)).slice(-16);
    while(transcript.length&&transcript[transcript.length-1].role!=='assistant')transcript.pop();
    transcript.push({role:'user',text:message});
    while(transcript.length>1&&transcript[0].role!=='user')transcript.shift();
    try{
      const response=await fetch('../api/jarvis.php',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({messages:transcript})});
      const data=await response.json().catch(()=>({}));
      if(!response.ok){const error=new Error(data.error||`El servidor respondió con código ${response.status}.`);error.status=response.status;throw error}
      if(typeof data.reply!=='string'||!data.reply.trim())throw new Error('El servicio no devolvió una respuesta. Intente de nuevo.');
      history.push({role:'assistant',text:data.reply.trim()});history=history.slice(-50);saveHistory();setState('GEMINI EN LÍNEA');
    }catch(error){
      let detail=error.message||'No se pudo completar la consulta.';
      if(location.protocol==='file:')detail='Abra el sitio desde Apache de XAMPP (http://localhost/...). PHP debe ejecutar el intermediario de Gemini.';
      else if(error instanceof TypeError)detail='No se pudo contactar al servidor. Confirme que XAMPP/Apache esté activo y vuelva a intentarlo.';
      history.push({role:'assistant',text:detail,error:true});history=history.slice(-50);saveHistory();setState(error.status===401?'SESIÓN REQUERIDA':'REVISAR CONEXIÓN');
    }finally{$('#jarvis-typing')?.remove();busy=false;if(button)button.disabled=false;render();input?.focus()}
  }
  function init(){
    if(document.body.dataset.page!=='jarvis'||!$('#jarvis-form'))return;
    history=readHistory();render();
    const clock=$('#jarvis-clock');if(clock){const update=()=>{clock.textContent=`INTERFAZ J.A.R.V.I.S. · ${new Date().toLocaleTimeString('es-BO',{hour:'2-digit',minute:'2-digit',second:'2-digit',hour12:false})}`};update();setInterval(update,1000)}
    $('#jarvis-form').addEventListener('submit',event=>{event.preventDefault();send($('#jarvis-input')?.value||'')});
    $('#jarvis-input').addEventListener('keydown',event=>{if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();$('#jarvis-form').requestSubmit()}});
    $('#jarvis-input').addEventListener('input',event=>{event.currentTarget.style.height='auto';event.currentTarget.style.height=`${Math.min(event.currentTarget.scrollHeight,125)}px`});
    $('#jarvis-suggestions').addEventListener('click',event=>{const button=event.target.closest('[data-prompt]');if(button)send(button.dataset.prompt)});
    $('#jarvis-clear').addEventListener('click',()=>{if(!history.length)return;history=[];saveHistory();render();setState('ESPERANDO MENSAJE');$('#jarvis-input').focus()});
    window.addEventListener('storage',event=>{if(event.key===KEY){history=readHistory();render()}});
  }
  window.addEventListener('hall:ready',init);
})();
