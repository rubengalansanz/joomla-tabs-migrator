# Joomla Tabs Migrator

**Joomla Tabs Migrator** es un plugin para WordPress diseñado para facilitar la migración de contenido desde Joomla, específicamente el que utiliza la sintaxis del popular componente **"Tabs & Sliders"** (pestañas y acordeones basados en marcadores de texto como `{tab Título|color}` o `{slider Título|close}`).

Cuando un sitio se traslada de Joomla a WordPress, el contenido de los artículos suele conservar estas etiquetas tal cual, ya que WordPress no las reconoce de forma nativa y las muestra como texto plano en lugar de interpretarlas como pestañas o paneles desplegables. Este plugin resuelve ese problema interceptando el contenido antes de mostrarlo, detectando los bloques `{tabs}...{/tabs}` y `{sliders}...{/sliders}`, y convirtiéndolos automáticamente en HTML accesible y funcional, sin necesidad de editar manualmente cada artículo.

## ¿Qué problema resuelve?

- Evita tener que reescribir a mano cientos de artículos migrados que contienen sintaxis de Joomla.
- Mantiene la apariencia funcional del contenido original (pestañas, colores, paneles plegables) sin depender de plugins de Joomla que ya no existen en el nuevo entorno.
- Permite una transición más rápida y económica, ideal para agencias o desarrolladores que gestionan migraciones de sitios corporativos, portales de noticias o documentación técnica.

## ¿Cómo funciona?

1. **Detección**: el plugin analiza el contenido de cada entrada o página en busca de patrones `{tab...}`, `{slider...}`, `{tabs}` y `{sliders}` (también sin contenedor de apertura, solo con los items y el cierre).
2. **Interpretación**: extrae el título, color y estado (abierto/cerrado) de cada pestaña o panel, respetando los parámetros originales separados por `|`.
3. **Renderizado**: genera una estructura HTML semántica con roles ARIA (`role="tablist"`, `role="tab"`, `aria-selected`, `aria-expanded`, `aria-controls`) para que el resultado sea accesible y compatible con lectores de pantalla.
4. **Interactividad**: un script JavaScript ligero (sin dependencias de jQuery) gestiona el cambio entre pestañas —con clic y con teclado (flechas, Home, End, patrón WAI-APG con roving tabindex)— y el despliegue de paneles tipo acordeón. Expone `window.jtmInit()` para contenido cargado por AJAX.
5. **Optimización de carga**: los estilos y scripts solo se cargan donde realmente hay este contenido (entradas, páginas, archivos y shortcodes), evitando peso innecesario en el resto del sitio.
6. **Enlace profundo**: cada panel recibe un ID legible a partir de su alias (o del título) y lleva `data-alias`; al activar una pestaña o abrir un slider se publica el hash, por lo que se puede enlazar directamente a una pestaña o panel concretos.

## Sintaxis soportada

- `{tabs}` … `{/tabs}` (contenedor de pestañas; acepta parámetros tras el nombre, p. ej. `{tabs alias=x}`)
- `{tab Título}`, `{tab Título|color}`, `{tab Título|color|alias}` (también `{tab}` sin título)
- `{sliders}` … `{/sliders}` (contenedor de acordeón; también con parámetros)
- `{slider Título|color}`, `{slider Título|close}`, `{slider Título|open}` (también `{slider}` sin título; cerrado por defecto)
- Colores admitidos: `red`, `orange`, `yellow`, `green`, `blue`, `purple`, `gray` (`grey` se normaliza a `gray`), `black`, `white` o hex de 3, 6 u 8 dígitos (`#fff`, `#rrggbb`, `#rrggbbaa`). Los hex se aplican como estilo inline (`border-top-color`); el resto como clase `jtm-color-{nombre}`.
- **Contenedores de apertura opcionales**: Joomla permite omitir `{tabs}`/`{sliders}` y dejar solo los items y el cierre (`{/tabs}`/`{/sliders}`); el parser detecta el grupo igualmente.
- **Grupos anidados**: un `{slider}...{/sliders}` puede aparecer dentro del cuerpo de un `{tab}` y se procesa de forma recursiva; los anidados del mismo tipo (`tabs` en `tabs`) se equilibran por contador para no cerrar el grupo exterior.
- **Grupo vacío o sin cierre**: `{tabs}{/tabs}` se conserva como texto original; un grupo sin cierre también queda intacto en lugar de romperse.
- **Alias y enlace profundo**: el tercer parámetro (`|alias`) —o el slug del título si no hay alias— forma parte del ID del panel y se expone como `data-alias` en botón y panel.
- **Negrita**: `**texto**` en títulos y cuerpo se convierte en `<strong>` (también dentro del texto de enlaces).
- **Enlaces e imágenes estilo Markdown** en el cuerpo: `[texto](url)` (texto escapado) y `![alt](url)` (incluida la combinación `[![alt](img)](url)`) se convierten a HTML.

## Estructura del plugin

```
joomla-tabs-migrator/
├── assets/
│   ├── css/
│   │   └── tabs.css       # Estilos base y variantes de color (borde superior)
│   └── js/
│       └── tabs.js        # Interactividad: clic, teclado WAI-APG, hash, jtmInit
├── includes/
│   ├── parser.php         # Detecta y estructura las etiquetas Joomla (recursivo)
│   └── renderer.php       # Convierte el árbol de nodos en HTML accesible
├── tests/
│   ├── test-1.php         # Arnés visual standalone (sin necesidad de WordPress)
│   ├── run-tests.php      # Runner de la suite automatizada (sin dependencias)
│   ├── bootstrap.php      # Stubs de WordPress para los tests
│   ├── parser-test.php    # Tests del parser
│   ├── renderer-test.php  # Tests del renderer
│   └── bootstrap-test.php # Tests del bootstrap (shortcode, assets, render)
├── joomla-tabs-migrator.php  # Bootstrap del plugin
└── README.md
```

## Shortcode para patrones de bloques

Además del procesado automático de `the_content`, el plugin registra `[jtm_pattern]` para reutilizar patrones de bloques que contengan sintaxis Joomla:

```
[jtm_pattern slug="mi-patron"]
[jtm_pattern "mi-patron"]
```

El patrón se renderiza con `do_blocks()` y luego se convierten sus etiquetas Joomla, igual que en el contenido normal.

## Probar en local sin WordPress

### Suite automatizada

```bash
php tests/run-tests.php
```

Ejecuta 36 tests (parser, renderer y bootstrap) con stubs de WordPress, sin necesidad de WordPress, Composer ni PHPUnit. Las aserciones (`jtm_assert_*`) están diseñadas para portarse 1:1 a PHPUnit cuando haya red disponible.

### Arnés visual

El archivo [tests/test-1.php](tests/test-1.php) define stubs mínimos de las funciones de WordPress usadas (`esc_html`, `esc_attr`, `esc_url`, `sanitize_title`, `wpautop`) para poder ejecutar el parser y el renderer con PHP puro:

```bash
php -S localhost:8000
```

Abre `http://localhost:8000/tests/test-1.php` y sustituye la variable `$content` por cualquier fragmento de contenido migrado que quieras validar.

## A quién va dirigido

Este tipo de plugin es especialmente útil para:
- Desarrolladores que realizan **migraciones de Joomla a WordPress**.
- Agencias que necesitan preservar el formato visual de contenido legado sin reescribirlo manualmente.
- Sitios con gran volumen de artículos antiguos que usaban componentes de pestañas/acordeones en Joomla.

## Ventajas frente a alternativas

- **Cero shortcodes manuales**: no requiere que el redactor aprenda una nueva sintaxis; el contenido migrado funciona "tal cual".
- **Ligero y sin dependencias**: JavaScript vanilla y CSS mínimo, sin cargar librerías externas.
- **Accesible por diseño**: soporte de atributos ARIA desde la base, no como añadido posterior.
- **Extensible**: la lógica de parseo está separada del renderizado, por lo que es sencillo añadir soporte a nuevas variantes de sintaxis si aparecen en el contenido migrado.

## Notas de diseño

- El color asignado a cada pestaña/slider (`|green`, `|blue`, etc.) se representa como un **borde superior de 3px**, no como relleno de fondo, para evitar conflictos visuales con el CSS del tema activo de WordPress. Los colores hex usan la clase `jtm-color-custom` más un `style` inline; `white` usa un gris visible (`#adb5bd`) como equivalente.
- La clase `jtm-active` marca tanto el elemento de navegación seleccionado como el panel visible; se actualiza dinámicamente al hacer clic o con el teclado (ver [assets/js/tabs.js](assets/js/tabs.js)).
- El filtro `the_content` corre después del `wpautop` nativo, así que el renderer solo aplica `wpautop` a fragmentos sin bloques HTML (evita `<p>` anidados).
- El CSS incluye guard `[hidden]{display:none!important}` (por si el tema lo anula), `:focus-visible` en botones, `flex-wrap` en la navegación y `scroll-margin-top` para los saltos por hash.
