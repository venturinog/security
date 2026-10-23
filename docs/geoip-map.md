# `sec_geoip_map`: por qué se retiró amCharts

Estado: 2026-10-22. Decisión aplicada en `issabel-security` 6.0.0-11.

La página **Security → Firewall → GeoIP Map** usaba amCharts 4 vendorizado
(`themes/default/vendor/amcharts/`, 1,6 MB) para dibujar un globo terráqueo y
animar los ataques de fail2ban. Se sustituyó por un mapa SVG estático y una
gráfica Chart.js del tema. Los motivos, en orden de peso:

## 1. Norma de estilo: una sola librería de gráficas

`STYLE.md` §10 manda usar el **Chart.js del tema** (`themes/modern/js/`) y no
introducir otra librería de charts sin un acuerdo explícito. amCharts era la
única excepción del workspace y obligaba a mantener dos soluciones para lo
mismo. La regla también prohíbe copiar la librería dentro del módulo y cargarla
por CDN; el mapa la tenía copiada.

## 2. Licencia

amCharts 4 se distribuye con licencia *linkware*: gratuita sólo si se muestra
la atribución visible, o con licencia comercial. El bundle vendorizado no
incluye cabecera de licencia y la página no mostraba atribución. Redistribuir
ese código dentro de un RPM GPLv2 es un riesgo de cumplimiento que no se podía
dejar abierto. El mapa SVG no incorpora código de amCharts.

## 3. Traducciones: 7 idiomas frente a 25

`geodata/lang/` sólo traía **EN, ES, RU, PT, FR, FA y DE**. Con los 25 idiomas
del framework, cualquier otro (por ejemplo `uk`) pedía `UK.js`, recibía un 404
y **el mapa no se dibujaba**. La nueva página usa `Intl.DisplayNames` del
navegador para traducir los nombres de país, así que funciona en los 25
idiomas sin archivos por idioma.

## 4. Modo claro/oscuro y tokens del tema

El globo y sus etiquetas llevaban colores fijos (`rgb(211,93,110)`,
`rgb(90,164,105)`, `#000`, `#927459`, `#f9e3fe`, `#006994`, `#e2c9b0`…) y el
fondo era un starfield negro. En modo claro/oscuro no había forma de mantener
el contraste. El SVG nuevo se colorea con variables del tema
(`--iss-sev-critical`, `--iss-sev-ok`, `--iss-surface-2`, `--iss-border`,
`--iss-primary`) y la gráfica usa `IssabelCharts.palette()`.

## 5. Privacidad y dependencia de red

Para situar la sede, el mapa consultaba `//ipwhois.app/json/<ip pública>`
desde el navegador. Eso envía la IP pública del PBX a un tercero y falla en
instalaciones sin salida a Internet. La nueva página usa el helper local
`fwconfig --homecountry` y ya no hace peticiones externas.

## 6. Peso, mantenimiento y accesibilidad

- amCharts 4 + geodata + sonidos + imágenes: ~2,1 MB por visita; el módulo
  queda en ~230 KB, casi todo el SVG.
- amCharts 4 está en mantenimiento frente a v5: era una librería minificada
  más que auditar y actualizar.
- El globo en canvas no era accesible por teclado ni tenía texto alternativo,
  y la rotación/animación ignoraba `prefers-reduced-motion`.
- Las “misiles” y los sonidos eran un *easter egg* (código Konami) desactivado
  por defecto; no eran funcionalidad de seguridad.

## Qué se puso en su lugar

| Antes | Ahora |
|---|---|
| Globo amCharts en canvas | SVG estático `themes/default/img/world.svg` (Natural Earth, dominio público; ~174 KB, 257 países) |
| Colores fijos en JS | Clases `.is-blocked`, `.is-pass`, `.is-home` con tokens del tema |
| Nombres de país en 7 JS | `Intl.DisplayNames` con el idioma activo (25 idiomas) |
| Clic → GET sin token | Clic → **POST** `block` / `unblock` / `noneblock` con token CSRF |
| Ataques animados | Gráfica Chart.js “Attacks by country (last 24 hours)” + listas accesibles |
| `ipwhois.app` | `fwconfig --homecountry` (local) |
| `attacks` marcados como `done` | `getAttackCounts()` lee la tabla sin consumirla |

El SVG se generó offline a partir de datos de Natural Earth (dominio público)
y se guarda ya proyectado (equirectangular); no se genera en runtime. Los
cambios de estado son POST y las consultas (`blocked`, `pass`, `homecountry`,
`attacks`) están declaradas de sólo lectura en `issabelCsrf`.

## Disponibilidad de GeoIP

La página comprueba `fwconfig --isactivegeoip` al cargar. Si el match no está
disponible, muestra un aviso y desactiva los clics del mapa: no se modifica la
BD ni se intenta aplicar una regla que iptables no puede cargar. `fwconfig`
comprueba las tres piezas (librería `libxt_geoip.so`, módulo `xt_geoip` y datos
en `/usr/share/xt_geoip`) y, al aplicar reglas, aborta con un mensaje claro si
hay reglas GeoIP configuradas y falta alguna. Para repararlo:

```sh
dnf install xtables-addons dkms kernel-devel-$(uname -r) \
    perl-NetAddr-IP perl-Text-CSV_XS
dkms autoinstall -k $(uname -r) && depmod -a && modprobe xt_geoip
/etc/cron.daily/geoip_update.sh
ls /usr/share/xt_geoip/BE | head
```

`xtables-addons` 3.18 no soporta el kernel 6.12; hace falta 3.27 o posterior.

## Verificación

```sh
# No queda nada de amCharts ni llamadas externas
grep -rn "amcharts\|am4core\|ipwhois" security/modules/sec_geoip_map || true

# El SVG existe y tiene un path por país
grep -c 'class="geo-country"' security/modules/sec_geoip_map/themes/default/img/world.svg

# Sintaxis
php -l security/modules/sec_geoip_map/index.php
```

Pendiente: revisión por nativos de las traducciones generadas para los 18
idiomas que no tenían archivo previo. La paridad de claves es completa; la
calidad de las cadenas largas (aviso legal de Let's Encrypt y pasos de uso) es
la que más puede necesitar ajuste.
