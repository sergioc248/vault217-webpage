'use strict';

const $ = (id) => document.getElementById(id);
const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
const isMissionPage = document.body.dataset.page === 'missions';
let motionPaused = false;
try {
  motionPaused = localStorage.getItem('vault217-motion') === 'paused';
} catch { /* La consola funciona también sin almacenamiento local. */ }
let state = null;
let busy = false;
let typingTimer = null;
let requestVersion = 0;
let lastTitle = '';
let toastTimer = null;
let hintIsOpen = false;

function motionAllowed() {
  return !motionPaused && !reduceMotion.matches;
}

function syncMotion() {
  document.documentElement.dataset.motion = motionAllowed() ? 'on' : 'off';
  const toggle = $('motion-toggle');
  toggle.disabled = reduceMotion.matches;
  toggle.setAttribute('aria-pressed', String(!motionAllowed()));
  toggle.textContent = reduceMotion.matches ? 'Movimiento reducido' : motionPaused ? 'Reanudar animaciones' : 'Pausar animaciones';
  if (!motionAllowed() && lastTitle) typeTitle(lastTitle);
}

$('motion-toggle').addEventListener('click', () => {
  motionPaused = !motionPaused;
  try {
    localStorage.setItem('vault217-motion', motionPaused ? 'paused' : 'running');
  } catch { /* La preferencia sigue activa durante esta visita. */ }
  syncMotion();
});
reduceMotion.addEventListener('change', syncMotion);
syncMotion();

function logActivity(message) {
  const log = $('terminal-log');
  if (!log) return;
  const entry = document.createElement('li');
  const stamp = document.createElement('time');
  stamp.textContent = new Date().toLocaleTimeString('es-CO', { hour12: false });
  const text = document.createElement('span');
  text.textContent = message;
  entry.append(stamp, text);
  log.append(entry);
  while (log.children.length > 3) log.firstElementChild.remove();
}

function receiveMission() {
  const article = $('mission-article');
  article.classList.remove('is-receiving');
  if (motionAllowed()) {
    void article.offsetWidth;
    article.classList.add('is-receiving');
  }
}

class ChannelError extends Error {
  constructor(message, status = 0) {
    super(message);
    this.status = status;
  }
}

async function request(action = null, fields = {}) {
  const url = new URL('/api.php', window.location.origin);
  const options = { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' } };
  if (action) {
    options.method = 'POST';
    options.headers['Content-Type'] = 'application/json';
    options.body = JSON.stringify({ ...fields, action, csrf: state?.csrf });
  } else if (fields.mission) {
    url.searchParams.set('mission', fields.mission);
  }
  let response;
  try {
    response = await fetch(url, options);
  } catch {
    throw new ChannelError('El enlace se ha interrumpido. Comprueba la conexión y vuelve a intentarlo.');
  }
  let result;
  try {
    result = await response.json();
  } catch {
    throw new ChannelError('La consola no recibió una respuesta válida. Recarga la página o avisa al instructor.');
  }
  if (!response.ok) throw new ChannelError(result.error || 'No se pudo registrar la operación.', response.status);
  $('connection-text').textContent = result.completed === 5 ? 'Operación completada' : 'Enlace establecido';
  $('connection-dot').parentElement.classList.remove('is-error');
  return result;
}

function connectionError() {
  $('connection-text').textContent = 'Enlace interrumpido';
  $('connection-dot').parentElement.classList.add('is-error');
}

function toast(message) {
  clearTimeout(toastTimer);
  $('global-feedback').textContent = message;
  $('global-feedback').hidden = false;
  toastTimer = setTimeout(() => { $('global-feedback').hidden = true; }, 6000);
}

function show(view) {
  for (const id of ['intro', 'operation', 'ending', 'loading']) {
    if ($(id)) $(id).hidden = id !== view;
  }
}

function scrollToHeading(heading) {
  heading.focus({ preventScroll: true });
  heading.scrollIntoView({ behavior: motionAllowed() ? 'smooth' : 'instant', block: 'start' });
}

function typeTitle(title) {
  clearTimeout(typingTimer);
  $('accessible-title').textContent = title;
  $('title-sizing').textContent = title;
  const output = $('typed-title');
  const cursor = document.querySelector('.type-cursor');
  cursor.className = 'type-cursor';
  if (title === lastTitle || !motionAllowed()) {
    output.textContent = title;
    cursor.classList.add('is-done');
    lastTitle = title;
    return;
  }
  lastTitle = title;
  const letters = Array.from(title);
  output.textContent = '';
  cursor.classList.add('is-typing');
  let position = 0;
  const tick = () => {
    if (!motionAllowed()) position = letters.length;
    output.textContent = letters.slice(0, ++position).join('');
    if (position < letters.length) {
      typingTimer = setTimeout(tick, 34);
    } else {
      cursor.className = 'type-cursor is-done';
    }
  };
  tick();
}

function renderRoute() {
  const list = $('mission-list');
  list.replaceChildren();
  for (const mission of state.missions) {
    const li = document.createElement('li');
    const button = document.createElement('button');
    button.type = 'button';
    button.className = `mission-step${mission.completed ? ' is-complete' : ''}`;
    button.disabled = !mission.unlocked;
    button.setAttribute('aria-label', `Misión ${mission.id}. ${mission.title || 'Sin descifrar'}. ${mission.completed ? 'Completada' : mission.unlocked ? 'Disponible' : 'Bloqueada'}`);
    if (mission.id === state.mission?.id) button.setAttribute('aria-current', 'step');
    const number = document.createElement('span');
    number.className = 'step-number';
    number.setAttribute('aria-hidden', 'true');
    number.textContent = mission.completed ? '✓' : String(mission.id).padStart(2, '0');
    const copy = document.createElement('span');
    copy.className = 'step-copy';
    const title = document.createElement('span');
    title.textContent = mission.title || 'Sin descifrar';
    const status = document.createElement('span');
    status.className = 'step-state';
    status.textContent = mission.completed ? 'Evidencia confirmada' : mission.unlocked ? 'En investigación' : 'Enlace bloqueado';
    copy.append(title, status);
    button.append(number, copy);
    button.addEventListener('click', () => loadMission(mission.id));
    li.append(button);
    list.append(li);
  }
  $('progress-label').textContent = `${state.completed} de ${state.missionCount} evidencias`;
  $('progress-bar').setAttribute('aria-valuenow', state.completed);
  $('progress-fill').style.width = `${state.completed / state.missionCount * 100}%`;
  for (const node of document.querySelectorAll('[data-node]')) {
    const id = Number(node.dataset.node);
    node.classList.toggle('node-complete', id <= state.completed);
    node.classList.toggle('node-current', id === state.mission?.id);
  }
}

function renderMission(focus = false) {
  const mission = state.mission;
  show('operation');
  renderRoute();
  $('mission-number').textContent = `Misión ${String(mission.id).padStart(2, '0')} / 05`;
  $('mission-location').textContent = mission.location;
  typeTitle(mission.title);
  document.title = `${mission.title} · Vault 217`;
  $('mission-transmission').textContent = mission.transmission;
  $('mission-situation').textContent = mission.situation;
  $('mission-objective').textContent = mission.objective;
  $('briefing-status').textContent = mission.completed ? 'Evidencia confirmada' : 'Investigación abierta';
  $('hint-text').textContent = mission.hint || '';
  $('hint-text').hidden = !mission.hint || !hintIsOpen;
  $('hint-button').setAttribute('aria-expanded', String(Boolean(mission.hint && hintIsOpen)));
  $('hint-button').lastChild.textContent = mission.hint && hintIsOpen ? 'Ocultar pista' : mission.hint ? 'Volver a ver la pista' : 'Solicitar una pista';
  $('hint-error').textContent = '';
  $('flag-feedback').textContent = '';
  $('flag-input').removeAttribute('aria-invalid');
  $('flag-input').value = '';
  $('evidence-section').hidden = mission.completed;
  $('mission-complete').hidden = !mission.completed;
  $('mission-debrief').textContent = mission.debrief || '';
  $('complete-title').textContent = mission.id === 5 ? 'La baliza ya puede guardar silencio.' : 'El siguiente enlace está abierto.';
  $('next-button').textContent = mission.id === 5 ? 'Cerrar la operación' : `Recibir misión ${String(mission.id + 1).padStart(2, '0')}`;
  receiveMission();
  if (focus) scrollToHeading($('mission-title'));
}

function renderEnding(focus = false) {
  show('ending');
  $('ending-title').textContent = state.ending.title;
  $('ending-copy').textContent = state.ending.body;
  document.title = 'Operación completada · Vault 217';
  if (focus) scrollToHeading($('ending-title'));
}

async function loadMission(id) {
  if (busy) return;
  const version = ++requestVersion;
  $('mission-article').setAttribute('aria-busy', 'true');
  try {
    const nextState = await request(null, { mission: id });
    if (version !== requestVersion) return;
    state = nextState;
    hintIsOpen = false;
    renderMission(true);
    logActivity(`Directiva recibida: misión ${String(id).padStart(2, '0')}.`);
  } catch (error) {
    if (version === requestVersion) toast(error.message);
  } finally {
    if (version === requestVersion) $('mission-article').removeAttribute('aria-busy');
  }
}

$('flag-form')?.addEventListener('submit', async (event) => {
  event.preventDefault();
  if (busy) return;
  const input = $('flag-input');
  if (!input.value.trim()) {
    $('flag-feedback').textContent = 'Pega la flag recuperada en la máquina antes de enviarla.';
    input.setAttribute('aria-invalid', 'true');
    input.focus();
    return;
  }
  busy = true;
  ++requestVersion;
  $('validate-button').disabled = true;
  $('validate-button').textContent = 'Validando…';
  $('flag-feedback').textContent = '';
  try {
    state = await request('flag', { mission: state.mission.id, flag: input.value });
    renderMission();
    logActivity(`Evidencia ${String(state.mission.id).padStart(2, '0')} confirmada.`);
    toast('Evidencia confirmada. Tu avance ha quedado registrado.');
    $('next-button').focus({ preventScroll: true });
    $('mission-complete').scrollIntoView({ behavior: motionAllowed() ? 'smooth' : 'instant', block: 'nearest' });
  } catch (error) {
    $('flag-feedback').textContent = error.message;
    input.setAttribute('aria-invalid', 'true');
    logActivity(error.status === 422 ? 'Evidencia rechazada. Revisa el hallazgo.' : 'No se pudo registrar la evidencia.');
    if (!error.status) connectionError();
    input.focus({ preventScroll: true });
  } finally {
    busy = false;
    $('validate-button').disabled = false;
    $('validate-button').textContent = 'Validar flag';
  }
});

$('flag-input')?.addEventListener('input', () => {
  $('flag-input').removeAttribute('aria-invalid');
  $('flag-feedback').textContent = '';
});

$('hint-button')?.addEventListener('click', async () => {
  if (busy) return;
  if (state.mission.hint) {
    hintIsOpen = !hintIsOpen;
    $('hint-text').hidden = !hintIsOpen;
    $('hint-button').setAttribute('aria-expanded', String(hintIsOpen));
    $('hint-button').lastChild.textContent = hintIsOpen ? 'Ocultar pista' : 'Volver a ver la pista';
    return;
  }
  busy = true;
  ++requestVersion;
  $('hint-button').disabled = true;
  try {
    state = await request('hint', { mission: state.mission.id });
    hintIsOpen = true;
    $('hint-text').textContent = state.mission.hint;
    $('hint-text').hidden = false;
    $('hint-button').setAttribute('aria-expanded', 'true');
    $('hint-button').lastChild.textContent = 'Ocultar pista';
    $('hint-error').textContent = '';
    logActivity('Orientación recibida por el canal auxiliar.');
    toast('Pista recibida por el canal de operaciones.');
  } catch (error) {
    $('hint-error').textContent = error.message;
  } finally {
    busy = false;
    $('hint-button').disabled = false;
  }
});

$('next-button')?.addEventListener('click', () => {
  if (state.mission.id === 5 && state.ending) renderEnding(true);
  else loadMission(state.mission.id + 1);
});
$('review-button')?.addEventListener('click', () => loadMission(1));

function openReset() {
  if (busy) return;
  $('reset-error').textContent = '';
  $('reset-dialog').returnValue = 'cancel';
  $('reset-dialog').showModal();
}
$('reset-button')?.addEventListener('click', openReset);
$('ending-reset')?.addEventListener('click', openReset);
$('reset-dialog')?.addEventListener('close', async () => {
  if ($('reset-dialog').returnValue !== 'reset' || busy) return;
  busy = true;
  ++requestVersion;
  try {
    state = await request('reset');
    clearTimeout(typingTimer);
    lastTitle = '';
    hintIsOpen = false;
    window.location.assign('/');
  } catch (error) {
    toast(error.message);
  } finally {
    busy = false;
  }
});

async function connect() {
  const errorOutput = isMissionPage ? $('loading-error') : $('intro-error');
  errorOutput.textContent = '';
  if ($('reconnect-button')) $('reconnect-button').hidden = true;
  try {
    state = await request();
    if (isMissionPage) {
      if (!state.started) state = await request('begin');
      logActivity('Canal exterior conectado. Registro recuperado.');
      logActivity(`${state.completed} de ${state.missionCount} evidencias registradas.`);
      if (state.completed === state.missionCount) renderEnding();
      else {
        renderMission();
        logActivity(`Directiva recibida: misión ${String(state.mission.id).padStart(2, '0')}.`);
      }
    } else {
      show('intro');
      $('begin-link').firstChild.textContent = state.completed === state.missionCount ? 'Ver registro de operación ' : state.started ? 'Retomar operación ' : 'Abrir canal de misión ';
      $('begin-note').textContent = state.started ? `${state.completed} de ${state.missionCount} evidencias registradas. Tu avance está guardado.` : 'Cinco misiones. Una señal que seguir.';
      $('home-register').textContent = state.started ? `Registro recuperado: ${state.completed}/05 evidencias. Especialista en línea.` : 'Terminal auxiliar en línea. Esperando al especialista.';
    }
  } catch (error) {
    connectionError();
    errorOutput.textContent = error.message;
    if ($('reconnect-button')) $('reconnect-button').hidden = false;
  }
}
$('reconnect-button')?.addEventListener('click', connect);
window.addEventListener('online', () => { if (!state || (isMissionPage && !$('loading').hidden)) connect(); });
window.addEventListener('pageshow', (event) => { if (event.persisted) connect(); });
connect();
