# Consola narrativa de Vault 217

Web de acompañamiento en español, alojada aparte de la máquina víctima. HTML, CSS y JavaScript sin framework; PHP para validar flags y guardar una partida por navegador. No necesita npm, Composer ni base de datos.

## Probar en tu equipo

La versión fijada para este proyecto es PHP 8.5.12, publicada como [actualización de seguridad](https://www.php.net/releases/8_5_12.php). `.php-version` declara la versión y `Dockerfile` la ejecuta con Apache.

Con Docker y Docker Compose:

```sh
cd ~/unab/vault217-webpage
docker compose up --build
```

Abre [la consola local](http://127.0.0.1:8217). Las sesiones se conservan en un volumen privado de Docker. Para detenerla, ejecuta `docker compose down`.

Si ya tienes PHP 8.5.12 o una actualización posterior instalada en tu equipo, también puedes ejecutarla directamente:

```sh
cd ~/unab/vault217-webpage
php -v
php -S 127.0.0.1:8217 -t public
```

El servidor integrado de PHP es para desarrollo y demostraciones locales; para compartir con la clase usa Apache o Nginx con PHP, conforme al [manual del servidor integrado de PHP](https://www.php.net/manual/en/features.commandline.webserver.php).

La instalación Herd Lite del equipo seguía ofreciendo PHP 8.5.11 al preparar esta actualización. Docker usa su propia versión 8.5.12; no actualiza el ejecutable `php` de tu terminal. No uses un runtime antiguo para servir la aplicación. El [aviso de PHP](https://www.php.net/archive/2026.php#2026-10-21-5) identifica las versiones que corrigen CVE-2026-109618 y CVE-2026-109619.

## Compartir con la clase

1. Copia este repositorio al servidor elegido, fuera de la máquina vulnerable. Conserva juntos `app/` y `public/`.
2. Configura el document root del sitio en `public/`. No sirvas la raíz del repositorio, `app/`, el PDF ni el tracker `.scratch/`.
3. Permite al usuario de PHP crear y escribir `var/sessions/`. También puedes definir `VAULT217_SESSION_PATH` con una ruta privada escribible fuera del document root. El resto del código puede permanecer de solo lectura.
4. Usa HTTPS en el sitio compartido; la cookie activa `Secure` cuando PHP recibe HTTPS. Si hay un proxy inverso, configura el servidor para transmitir ese estado a PHP.

El servidor compartido debe ejecutar PHP 8.5.12 o una actualización posterior. Actualizar este contenedor no actualiza el runtime de otro alojamiento ni la máquina víctima. El contenedor incluido publica el puerto solo en `127.0.0.1`; el acceso para la clase se configura en el servidor o proxy del curso.

Ejemplo orientativo de VirtualHost de Apache, una vez habilitada la integración PHP del servidor:

```apache
<VirtualHost *:80>
    ServerName consola.vault217.local
    DocumentRoot /srv/vault217-webpage/public
    <Directory /srv/vault217-webpage/public>
        DirectoryIndex index.php
        Options -Indexes
        AllowOverride None
        Require all granted
    </Directory>
</VirtualHost>
```

Configura el certificado y el VirtualHost HTTPS según el alojamiento del curso. No apuntes `DocumentRoot` al repositorio completo. La consola no contacta con la VM, no ejecuta sus scripts y no cambia sus endpoints ni su estado.

## Qué incluye

- Introducción como especialista de la Brotherhood of Steel y plano técnico original en SVG.
- Portada en `/` y consola de misiones en `/misiones/`, con navegación entre ambas. Volver al inicio no borra el avance ni fuerza a entrar de nuevo en la consola.
- Cinco misiones en el orden del PDF, con «Situación inicial» y «Objetivo» fieles a las páginas 8, 21, 29, 40 y 52.
- Títulos con efecto de máquina de escribir; texto inmediato cuando el navegador solicita movimiento reducido.
- Estética CRT inspirada en el portal de la víctima: fósforo verde, scanlines, paneles de terminal y enlaces entre corchetes. Barrido de radar, pulsos de baliza, onda de radio y recorrido animado de la señal.
- Control «Pausar animaciones» disponible en ambas rutas. La preferencia se conserva en el navegador y el movimiento reducido del sistema tiene prioridad.
- Registro de actividad vinculado a la conexión, las misiones, las pistas y la validación de evidencias. No muestra flags ni comandos de solución.
- Una pista opcional y breve por misión. Las siguientes misiones y sus pistas se bloquean también en el servidor.
- Flags contrastadas con los cinco valores canónicos de la máquina. `app/flag-hashes.php` guarda sus SHA-256 fuera de la raíz pública. No hay flags ni hashes esperados en el cliente.
- Comparación sensible a mayúsculas, ignorando únicamente espacios al principio y al final. Validación limitada a 12 envíos por minuto y partida; no ofrece una prueba de que el participante haya ejecutado realmente el reto.
- Revisión de misiones completadas, epílogo al cerrar la quinta y reinicio confirmado de la consola.
- Fuentes locales con sus licencias SIL OFL. No hay peticiones a fuentes, librerías o servicios de terceros.

Las sesiones conservan el avance durante un máximo de 30 días de inactividad, sujeto a la configuración y conservación de archivos del servidor. Al borrar cookies, cambiar de navegador o perder esos archivos, se pierde la partida. No hay sincronización entre dispositivos. Pestañas del mismo perfil comparten partida. «Reiniciar mi partida» afecta solo a esta consola; el reset de la VM corresponde al instructor. El override final de la máquina sigue teniendo su estado global original.

## Archivos

- `public/`: única raíz servida, entrada, API y recursos.
- `app/`: briefings, validación y hashes privados.
- `app/page.php`: plantilla compartida; `public/index.php` sirve el inicio y `public/misiones/index.php` la consola.
- `var/`: sesiones, generadas al ejecutar e ignoradas por Git.
- `tests/`: pruebas de integración que leen las flags desde la máquina fuente sin imprimirlas.
- `Dockerfile` y `compose.yaml`: runtime PHP 8.5.12 con Apache y sesiones persistentes. La imagen solo contiene `app/` y `public/`.

Las fuentes del recorrido y el mapa wayfinder están en [Una señal desde Vault 217](.scratch/vault217-narrativa/map.md). Son material de preparación para el instructor.

## Verificación

Con PHP 8.5.12 o una actualización posterior en el PATH:

```sh
python3 -m unittest discover -s tests -v
```

Ejecuta desde la raíz de `vault217-webpage`. La prueba inicia un servidor PHP temporal y verifica el recorrido completo, bloqueo de misiones futuras, privacidad de respuestas, CSRF, entradas inválidas, pistas, reanudación, aislamiento de navegadores, reinicio y límite de envíos. No necesita encender la VM ni ejecutar sus scripts administrativos.

También verifica que las dos rutas sean diferentes y que navegar entre ellas conserve la partida. La revisión en navegador cubre la navegación de ida y vuelta, reanudación, reinicio, pausa persistente de animaciones y ausencia de desbordamiento en móvil.

Para probar el runtime Docker sin usar el PHP del equipo, inicia el contenedor y dirige las pruebas a su URL:

```sh
docker compose up --build -d
docker compose exec web php -v
VAULT217_TEST_URL=http://127.0.0.1:8217 python3 -m unittest discover -s tests -v
docker compose down
```

La aplicación no necesita el repositorio de la máquina para funcionar. Las pruebas contrastan las respuestas con los artefactos originales, que buscan por defecto en `~/Documents/Vault_Setup`. Si están en otra carpeta:

```sh
VAULT217_SOURCE_REPO=/ruta/a/Vault_Setup python3 -m unittest discover -s tests -v
```

Los briefings se conservan literalmente; la introducción, los mensajes de transición y las pistas son adaptación narrativa. La cronología no añade un año actual porque el PDF y el final de la máquina discrepan sobre el tiempo transcurrido desde la guerra.
