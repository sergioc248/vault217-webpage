<?php
declare(strict_types=1);
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; font-src 'self'; connect-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'self'; form-action 'self'");
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');
if (!empty($_SERVER['PATH_INFO'])) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'No existe ese enlace en la consola.';
    exit;
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#081f2b">
  <meta name="description" content="Sigue la señal de Vault 217. Una operación narrativa de cinco misiones para el laboratorio CyberQuest.">
  <title>Vault 217 · Ecos desde el Yermo</title>
  <link rel="icon" href="/assets/beacon.svg" type="image/svg+xml">
  <link rel="preload" href="/assets/fonts/BarlowCondensed-SemiBold.ttf" as="font" type="font/ttf" crossorigin>
  <link rel="stylesheet" href="/assets/style.css">
  <script src="/assets/app.js" defer></script>
</head>
<body data-page="<?= $missionPage ? 'missions' : 'home' ?>">
  <a class="skip-link" href="#main">Saltar al contenido</a>
  <div class="page-shell">
    <header class="masthead">
      <a href="/" class="brand" aria-label="Vault 217, inicio de la consola">
        <svg class="brand-mark" viewBox="0 0 48 48" fill="none" aria-hidden="true"><path d="m24 2 19 11v22L24 46 5 35V13Z"/><path d="M14 17h20L24 35Zm0 0 10-8 10 8M24 9v26"/></svg>
        <span><strong>Vault 217</strong><span>Canal de operaciones</span></span>
      </a>
      <nav class="site-nav" aria-label="Navegación principal">
        <a href="/"<?= !$missionPage ? ' aria-current="page"' : '' ?>>Inicio</a>
        <a href="/misiones/"<?= $missionPage ? ' aria-current="page"' : '' ?>>Misiones</a>
      </nav>
      <div class="masthead-tools">
        <div class="connection"><span class="signal-dot" id="connection-dot"></span><span id="connection-text" role="status">Estableciendo enlace</span></div>
        <button id="motion-toggle" class="motion-toggle" type="button" aria-pressed="false">Pausar animaciones</button>
      </div>
    </header>

    <main id="main" tabindex="-1">
      <?php if (!$missionPage): ?>
      <section id="intro" class="intro" aria-labelledby="intro-title">
        <div class="intro-grid">
          <div class="intro-copy">
            <p class="operation-name">Operación Echoes of Vault 217</p>
            <h1 id="intro-title">Algo sigue<br>despierto.</h1>
            <p class="intro-lead">La puerta lleva años cerrada.<br>La señal acaba de llegar.</p>
            <p class="intro-description">La Brotherhood of Steel ha interceptado una baliza en las ruinas de Vault 217. El equipo de reconocimiento encontró la entrada sellada. Un terminal auxiliar todavía responde.</p>
            <a class="button primary" id="begin-link" href="/misiones/">Abrir canal de misión <span aria-hidden="true">↵</span></a>
            <p class="begin-note" id="begin-note">Cinco misiones. Una señal que seguir.</p>
            <p id="intro-error" class="form-message" role="alert"></p>
          </div>

          <figure class="vault-figure" aria-label="Plano del acceso a Vault 217. La puerta principal está sellada y el terminal auxiliar responde.">
            <div class="figure-heading"><span><span class="terminal-indicator" aria-hidden="true"></span> Reconocimiento exterior</span><span>Ref. 217</span></div>
            <svg class="vault-drawing" viewBox="0 0 580 540" fill="none" aria-hidden="true">
              <defs>
                <linearGradient id="radar-fade" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#72ff72" stop-opacity=".18"/><stop offset="1" stop-color="#72ff72" stop-opacity="0"/></linearGradient>
                <pattern id="grid" width="28" height="28" patternUnits="userSpaceOnUse"><path d="M28 0H0v28" stroke="currentColor" stroke-opacity=".12" stroke-width=".6"/></pattern>
                <pattern id="hatch" width="9" height="9" patternUnits="userSpaceOnUse" patternTransform="rotate(45)"><path d="M0 0v9" stroke="currentColor" stroke-opacity=".25" stroke-width="2"/></pattern>
              </defs>
              <path d="M20 20h540v500H20Z" fill="url(#grid)"/>
              <g stroke="currentColor" stroke-width="1">
                <path d="M44 397V151l42-31h338l58 51v226M30 397h493M58 412h437M44 151h57m322 0h59M58 117v-22h380v22"/>
                <path d="M140 171 126 147l37-23 14 24m61-15V105h45v28m59 15 15-24 37 23-14 24m24 52 27-6 10 43-26 7m-13 60 23 15-25 36-24-16m-62 35 3 25-45 3-2-25m-60-22-15 23-37-25 15-23m-35-58-25 4-7-43 27-4" fill="url(#hatch)"/>
                <circle cx="260" cy="263" r="140"/><circle cx="260" cy="263" r="128"/><circle cx="260" cy="263" r="111"/><circle cx="260" cy="263" r="104" stroke-opacity=".5"/>
                <path d="M260 156v46m0 121v46M154 263h53m108 0h51M187 190l36 36m74 74 36 36m-146 0 36-36m74-74 36-36" stroke-width="3"/>
                <circle cx="260" cy="263" r="65" fill="#071b1b"/><circle cx="260" cy="263" r="57"/>
                <path d="M87 263h44m258 0h41M260 90v38m0 275v30" stroke-dasharray="3 5"/>
                <path d="M99 370H69v-41m353 41h30v-41M94 176H69v22m353-22h30v22"/>
                <path d="M423 218h77v-68h-35m-78 230h94v78H328M179 387v71H55" stroke-opacity=".7"/>
                <circle cx="423" cy="218" r="3" fill="currentColor"/><circle cx="179" cy="387" r="3" fill="currentColor"/>
                <rect x="467" y="277" width="48" height="63" rx="3" fill="#071b1b"/><path d="M475 286h31v24h-31zm0 34h31m-31 7h18"/>
              </g>
              <g class="radar-sweep"><path d="M260 263V119a144 144 0 0 1 102 42Z" fill="url(#radar-fade)"/><circle cx="260" cy="263" r="149" stroke="#72ff72" stroke-width="1.5" stroke-dasharray="65 872"/></g>
              <text x="260" y="281" class="door-number" text-anchor="middle">217</text>
              <g class="drawing-label"><text x="448" y="133">Puerta principal</text><text x="448" y="149" class="label-dim">Sellada</text><text x="54" y="480">Estructura estable</text><text x="328" y="480">Acceso sin confirmar</text></g>
              <g class="beacon-signal" stroke="#72ff72"><circle class="beacon-ring" cx="491" cy="299" r="23"/><circle class="beacon-ring beacon-ring-delayed" cx="491" cy="299" r="23"/><circle cx="491" cy="299" r="30" stroke-dasharray="3 6"/><path d="m511 260 12-16m-5 31 20-9m-14 24h21"/></g>
              <g class="drawing-label signal-label"><text x="378" y="353">Terminal auxiliar</text><text x="378" y="370">Responde</text></g>
              <path d="M20 37V20h17m506 0h17v17M20 503v17h17m506 0h17v-17" stroke="currentColor"/>
            </svg>
            <figcaption><span class="signal-dot"></span> Baliza de emergencia activa<span class="figure-coordinate">Enlace exterior</span></figcaption>
          </figure>
        </div>
        <div class="dispatch">
          <div class="dispatch-identifier"><span class="tiny-wave" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></span>Transmisión interceptada</div>
          <blockquote lang="en">“Vault-Tec emergency beacon.<br class="mobile-break"> Aurora protocol active.”</blockquote>
          <p>No hay respuesta humana.</p>
        </div>
        <div class="terminal-readout home-readout" aria-label="Estado del enlace">
          <span class="terminal-prompt" aria-hidden="true">&gt;_</span>
          <div><p>Baliza recibida. Puerta principal sellada.</p><p id="home-register">Esperando al especialista.</p></div>
          <span class="terminal-caret" aria-hidden="true"></span>
        </div>
        <div class="field-notes">
          <h2>Tu encargo</h2>
          <p>Eres especialista en sistemas prebélicos. Tu equipo puede vigilar la entrada, pero necesita que tú encuentres un camino hacia el servidor central. Reconstruye qué ocurrió con sus habitantes y recupera el informe final de AURORA.</p>
          <p>Esta consola recibe tus hallazgos. La investigación ocurre en la máquina del laboratorio; cada flag recuperada abre el siguiente tramo de la historia.</p>
        </div>
      </section>

      <?php else: ?>
      <section id="loading" class="terminal-loading" aria-labelledby="loading-title">
        <p class="terminal-prompt" aria-hidden="true">&gt;_</p>
        <h1 id="loading-title">Abriendo el canal de misión.</h1>
        <p>Recuperando el registro de tu operación<span class="terminal-caret" aria-hidden="true"></span></p>
        <p id="loading-error" class="form-message" role="alert"></p>
        <button id="reconnect-button" class="button primary" hidden>Reintentar conexión</button>
      </section>
      <section id="operation" class="operation" aria-label="Consola de misiones" hidden>
        <div class="operation-bar"><p>Echoes of Vault 217</p><div class="progress-summary"><span id="progress-label">0 de 5 evidencias</span><div class="progress-track" role="progressbar" aria-label="Misiones completadas" aria-valuemin="0" aria-valuemax="5" aria-valuenow="0" id="progress-bar"><span id="progress-fill"></span></div></div></div>
        <div class="operation-grid">
          <aside class="mission-sidebar" aria-label="Ruta de la operación">
            <p class="sidebar-heading">Ruta de la operación</p>
            <nav aria-label="Misiones"><ol id="mission-list" class="mission-list"></ol></nav>
            <div class="route-diagram" aria-hidden="true"><svg viewBox="0 0 220 145" fill="none"><path d="M10 110h37V75h41V39h38v36h39v35h45M47 110h118M88 75h38" stroke="currentColor"/><path class="route-trace" pathLength="100" d="M10 110h37V75h41V39h38v36h39v35h45"/><circle data-node="1" cx="47" cy="110" r="5"/><circle data-node="2" cx="88" cy="75" r="5"/><circle data-node="3" cx="126" cy="39" r="5"/><circle data-node="4" cx="165" cy="75" r="5"/><circle data-node="5" cx="210" cy="110" r="5"/></svg><span>Plano de enlace · Vault 217</span></div>
            <a href="/" class="return-home">[ Volver al inicio ]</a>
            <button id="reset-button" class="quiet-button">Reiniciar mi partida</button>
            <p class="sidebar-note">Tu avance se conserva en este navegador.</p>
          </aside>

          <article id="mission-article" class="mission-article" aria-labelledby="mission-title">
            <div class="mission-location"><span id="mission-number"></span><span id="mission-location"></span></div>
            <h1 id="mission-title" tabindex="-1"><span id="title-sizing" class="title-sizing" aria-hidden="true"></span><span class="typed-line" aria-hidden="true"><span id="typed-title"></span><span class="type-cursor"></span></span><span id="accessible-title" class="sr-only"></span></h1>
            <p id="mission-transmission" class="mission-transmission"></p>
            <div class="console-log" aria-label="Registro de la terminal"><div class="console-log-header"><span><span class="terminal-indicator" aria-hidden="true"></span> Registro del canal</span><span aria-hidden="true">217://enlace</span></div><ol id="terminal-log"></ol></div>
            <div class="briefing">
              <div class="briefing-header"><h2>Informe de misión</h2><span id="briefing-status"></span></div>
              <section class="situation"><h3>Situación inicial</h3><p id="mission-situation"></p></section>
              <section class="objective"><span class="objective-marker" aria-hidden="true">⌖</span><div><h3>Tu objetivo</h3><p id="mission-objective"></p></div></section>
            </div>
            <div class="hint-area"><button id="hint-button" class="hint-button" aria-expanded="false" aria-controls="hint-text"><span aria-hidden="true">?</span>Solicitar una pista</button><p id="hint-text" class="hint-text" hidden></p><p id="hint-error" class="form-message" role="alert"></p></div>
            <section class="evidence-section" id="evidence-section" aria-labelledby="evidence-title">
              <div class="evidence-heading"><span class="evidence-icon" aria-hidden="true">◇</span><div><h2 id="evidence-title">Confirma tu hallazgo</h2><p>Entrega la flag que recuperaste en la máquina.</p></div></div>
              <form id="flag-form" novalidate><label for="flag-input">Flag de esta misión</label><div class="flag-controls"><input id="flag-input" name="flag" type="text" placeholder="Pega aquí la flag completa" maxlength="256" required autocomplete="off" spellcheck="false" autocapitalize="off" aria-describedby="flag-feedback"><button id="validate-button" class="button primary" type="submit">Validar flag</button></div><p id="flag-feedback" class="form-message" role="alert"></p></form>
            </section>
            <section id="mission-complete" class="mission-complete" aria-labelledby="complete-title" hidden><p class="receipt"><span aria-hidden="true">✓</span> Evidencia confirmada</p><h2 id="complete-title">El siguiente enlace está abierto.</h2><p id="mission-debrief"></p><button id="next-button" class="button primary">Continuar la operación</button></section>
          </article>
        </div>
      </section>

      <section id="ending" class="ending" aria-labelledby="ending-title" hidden><div class="ending-seal" aria-hidden="true">217<span>Enlace cerrado</span></div><p class="receipt">Operación completada · 5 de 5 evidencias</p><h1 id="ending-title" tabindex="-1"></h1><p id="ending-copy"></p><div class="ending-actions"><button id="review-button" class="button primary">Revisar misiones</button><button id="ending-reset" class="quiet-button">Iniciar otra partida</button></div><p class="ending-note">La historia del refugio ya tiene testigos.</p></section>
      <?php endif; ?>
    </main>

    <footer class="site-footer"><span>CyberQuest · Vault 217</span><span>Laboratorio de ciberseguridad<span class="footer-separator"> / </span>Red host-only del curso</span></footer>
    <p id="global-feedback" class="global-feedback" role="status" hidden></p>
    <noscript><p class="noscript">Activa JavaScript para recibir las misiones y entregar tus evidencias.</p></noscript>
  </div>
  <?php if ($missionPage): ?>
  <dialog id="reset-dialog" aria-labelledby="reset-title"><form method="dialog"><p class="dialog-label">Registro de operaciones</p><h2 id="reset-title">¿Cerrar esta partida?</h2><p>Se borrarán tu avance y las pistas consultadas en esta consola. La máquina del laboratorio conserva su estado.</p><div class="dialog-actions"><button value="cancel" class="button secondary" autofocus>Conservar partida</button><button value="reset" class="button danger">Reiniciar partida</button></div></form><p id="reset-error" class="form-message" role="alert"></p></dialog>
  <?php endif; ?>
</body>
</html>
