# Joomla Tabs Migrator

**Joomla Tabs Migrator** es un plugin para WordPress diseñado para facilitar la migración de contenido desde Joomla, específicamente el que utiliza la sintaxis del popular componente **"Tabs & Sliders"** (pestañas y acordeones basados en marcadores de texto como `{tab Título|color}` o `{slider Título|close}`).

Cuando un sitio se traslada de Joomla a WordPress, el contenido de los artículos suele conservar estas etiquetas tal cual, ya que WordPress no las reconoce de forma nativa y las muestra como texto plano en lugar de interpretarlas como pestañas o paneles desplegables. Este plugin resuelve ese problema interceptando el contenido antes de mostrarlo, detectando los bloques `{tabs}...{/tabs}` y `{sliders}...{/sliders}`, y convirtiéndolos automáticamente en HTML accesible y funcional, sin necesidad de editar manualmente cada artículo.

## ¿Qué problema resuelve?

- Evita tener que reescribir a mano cientos de artículos migrados que contienen sintaxis de Joomla.
- Mantiene la apariencia funcional del contenido original (pestañas, colores, paneles plegables) sin depender de plugins de Joomla que ya no existen en el nuevo entorno.
- Permite una transición más rápida y económica, ideal para agencias o desarrolladores que gestionan migraciones de sitios corporativos, portales de noticias o documentación técnica.

## ¿Cómo funciona?

1. **Detección**: el plugin analiza el contenido de cada entrada o página en busca de patrones `{tab...}`, `{slider...}`, `{tabs}` y `{sliders}`.
2. **Interpretación**: extrae el título, color y estado (abierto/cerrado) de cada pestaña o panel, respetando los parámetros originales separados por `|`.
3. **Renderizado**: genera una estructura HTML semántica con roles ARIA (`role="tablist"`, `aria-selected`, `aria-expanded`) para que el resultado sea accesible y compatible con lectores de pantalla.
4. **Interactividad**: un script JavaScript ligero (sin dependencias de jQuery) gestiona el cambio entre pestañas y el despliegue de paneles tipo acordeón.
5. **Optimización de carga**: los estilos y scripts solo se cargan en las páginas que realmente contienen este tipo de contenido, evitando peso innecesario en el resto del sitio.

## Sintaxis soportada

- `{tabs}` … `{/tabs}` (contenedor de pestañas)
- `{tab Título}`, `{tab Título|color}`, `{tab Título|color|alias}`
- `{sliders}` … `{/sliders}` (contenedor de acordeón)
- `{slider Título|color}`, `{slider Título|close}`, `{slider Título|open}`
- Colores admitidos: `red`, `orange`, `yellow`, `green`, `blue`, `purple`, `gray`/`grey`, `black`, `white` o valores hexadecimales (`#rrggbb`)
- **Contenedores de apertura opcionales**: Joomla permite omitir `{tabs}`/`{sliders}` y dejar solo los items y el cierre (`{/tabs}`/`{/sliders}`); el parser detecta el grupo igualmente.
- **Grupos anidados**: un `{slider}...{/sliders}` puede aparecer dentro del cuerpo de un `{tab}`, y se procesa de forma recursiva.
- **Negrita**: `**texto**` dentro de títulos o cuerpo se convierte en `<strong>`.
- **Enlaces e imágenes estilo Markdown** en el cuerpo: `[texto](url)` y `![alt](url)` (incluida la combinación `[![alt](img)](url)`) se convierten a HTML.

## Estructura del plugin

```
joomla-tabs-migrator/
├── assets/
│   ├── css/
│   │   └── tabs.css       # Estilos base y variantes de color (borde superior)
│   └── js/
│       └── tabs.js        # Interactividad de pestañas y sliders
├── includes/
│   ├── parser.php         # Detecta y estructura las etiquetas Joomla (recursivo)
│   └── renderer.php       # Convierte el árbol de nodos en HTML
├── joomla-tabs-migrator.php  # Bootstrap del plugin
├── test-local.php         # Arnés de prueba standalone (sin necesidad de WordPress)
└── README.md
```

## Probar en local sin WordPress

El archivo [test-local.php](test-local.php) define stubs mínimos de las funciones de WordPress usadas (`esc_html`, `esc_attr`, `esc_url`, `sanitize_title`, `wpautop`) para poder ejecutar el parser y el renderer con PHP puro:

```bash
php -S localhost:8000
```

Abre `http://localhost:8000/test-local.php` y sustituye la variable `$content` por cualquier fragmento de contenido migrado que quieras validar.

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

- El color asignado a cada pestaña/slider (`|green`, `|blue`, etc.) se representa como un **borde superior de 3px**, no como relleno de fondo, para evitar conflictos visuales con el CSS del tema activo de WordPress.
- La clase `jtm-active` marca tanto el elemento de navegación seleccionado como el panel visible; se actualiza dinámicamente al hacer clic (ver [assets/js/tabs.js](assets/js/tabs.js)).
