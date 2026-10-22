<?php
declare(strict_types=1);

// Material de cada misión. Solo el servidor entrega el briefing desbloqueado.
return [
    1 => [
        'title' => 'Señal desde el Yermo',
        'location' => 'Perímetro exterior',
        'transmission' => 'La puerta no cede. Al otro lado del acero, un terminal sigue esperando una conexión. Encuentra la señal antes de que vuelva a perderse.',
        'situation' => 'Antes de iniciar la operación, el Red Team solo dispone de la orden transmitida por la Brotherhood of Steel: una baliza automática con el mensaje «VAULT-TEC EMERGENCY BEACON — AURORA PROTOCOL ACTIVE» fue detectada procedente de las ruinas de Vault 217. No se conoce la dirección IP de la víctima, los puertos abiertos, los usuarios del sistema, las contraseñas ni el sistema operativo. Lo único seguro es que me encuentro conectado a la misma red virtual de laboratorio (interfaz host-only) desde la que se originó la señal.',
        'objective' => 'Identificar el equipo correspondiente a Vault 217 dentro de la red del laboratorio, enumerar los servicios que continúan activos y obtener acceso al terminal remoto de mantenimiento.',
        'hint' => 'Lo que una terminal deja a la vista no siempre aparece en su pantalla principal.',
        'debrief' => 'El terminal de mantenimiento responde. Entre sus registros hay una orden pendiente y un nombre. La señal ya tiene un origen; ahora necesitas a alguien que pudiera autorizar el siguiente acceso.',
        'page' => 8,
    ],
    2 => [
        'title' => 'El Supervisor',
        'location' => 'Terminal de mantenimiento',
        'transmission' => 'El refugio sigue obedeciendo una jerarquía que nadie ha actualizado. Nathan Cole tenía más acceso que mantenimiento. Sus rastros todavía están aquí.',
        'situation' => 'Ya dispongo de acceso de mantenimiento (maint217) y conozco el nombre del Supervisor del Vault, Nathan Cole, su Personnel ID (OVR-001), la convención de nombres de usuario y la pista de que construye sus contraseñas a partir de intereses personales. Todavía no conozco la contraseña real de ncole ni tengo confirmación directa de que esa cuenta exista en el sistema.',
        'objective' => 'Obtener acceso a la cuenta del Supervisor (ncole) mediante SSH, aprovechando la información interna recopilada en la Misión 1.',
        'hint' => 'Los registros hablan de un cargo. Los detalles personales hablan de quien lo ocupaba.',
        'debrief' => 'La cuenta del Supervisor conserva una transmisión que no llegó a destino. Marcus Reed intentó dejar constancia de lo ocurrido. Recupera su voz.',
        'page' => 21,
    ],
    3 => [
        'title' => 'Ecos del Vault',
        'location' => 'Archivo del Supervisor',
        'transmission' => 'Una transmisión interceptada. Un incidente sin cerrar. Antes de confiar en lo que escuches, comprueba que el mensaje sigue entero.',
        'situation' => 'Dispongo de acceso SSH como ncole y de dos archivos relacionados con el Incidente 184: transmission.b64 y transmission.sha256. Sé, por el propio incidente, que Marcus Reed intentó transmitir evidencia sobre lo ocurrido en Research Level B y que esa transmisión fue interceptada por el sistema de seguridad del Vault.',
        'objective' => 'Reconstruir y validar la integridad de la última transmisión de Seguridad, y utilizar la información que contiene para localizar y analizar la evidencia de red relacionada con el Incidente 184.',
        'hint' => 'Una evidencia cuenta lo que ocurrió. Otra permite saber si el relato ha cambiado.',
        'debrief' => 'La cámara 04 conserva un instante del Laboratorio B-04. Lo que muestra es solo el comienzo de la siguiente búsqueda.',
        'page' => 29,
    ],
    4 => [
        'title' => 'Sombras de AURORA',
        'location' => 'Laboratorio B-04',
        'transmission' => 'Una fotografía ha cruzado el bloqueo de seguridad. Detente en ella. Alguien pudo haber elegido este instante por algo más que lo que se ve.',
        'situation' => 'Tengo cam04.jpg, la fotografía de la cámara de seguridad del Laboratorio B-04 recuperada al final de la Misión 3. Todavía no sé si la imagen contiene algo más que una fotografía, ni qué relación tiene exactamente con el Proyecto AURORA.',
        'objective' => 'Analizar la evidencia visual, identificar cualquier información oculta o adicional que contenga, y recuperar el material del Proyecto AURORA protegido dentro de ella.',
        'hint' => 'Una imagen también conserva información que no necesita mostrarse como píxeles.',
        'debrief' => 'El archivo de AURORA está abierto. Entre sus fragmentos aparece una identidad que sigue teniendo autoridad sobre el sistema: JANUS. El último acceso exige reconstruir lo que falta.',
        'page' => 40,
    ],
    5 => [
        'title' => 'El Último Protocolo',
        'location' => 'Núcleo de AURORA',
        'transmission' => 'JANUS todavía responde. Tienes los fragmentos públicos de su identidad y la ubicación del informe final. Falta una pieza. Lo que hagas con ella cerrará esta operación.',
        'situation' => 'Dispongo de los parámetros públicos de un intercambio Diffie-Hellman (p=467, g=2, A=132, B=363), la identidad pública OpenPGP de JANUS, el procedimiento para derivar una passphrase a partir del secreto compartido, y la ubicación de los dos archivos finales (janus_private.asc.enc y AURORA_FINAL_REPORT.gpg). Me falta el exponente privado efímero de JANUS (a), sin el cual no puedo calcular el secreto compartido.',
        'objective' => 'Reconstruir criptográficamente el secreto compartido y la clave privada de JANUS, descifrar el informe final del Proyecto AURORA y ejecutar el protocolo de terminación del sistema.',
        'hint' => 'Incluso un sistema que protege sus secretos puede haber dejado un registro de su propio inicio.',
        'debrief' => 'El informe final ha sido recuperado. El protocolo de terminación se ejecutó en el refugio y has entregado su evidencia. Por primera vez desde que llegaste, la baliza deja de pedir ayuda.',
        'page' => 52,
    ],
];
