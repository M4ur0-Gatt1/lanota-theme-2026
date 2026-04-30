# Lanota Theme 2026 Theme

Un tema de WordPress moderno y responsivo diseñado específicamente para medios de comunicación, con un diseño similar a Twitter de tres columnas.

## Características

- **Diseño de 3 columnas** similar a Twitter
- **Toggle de tema oscuro/claro** en la esquina superior derecha
- **Scroll infinito** para el feed de publicaciones
- **Buscador funcional** con sugerencias en tiempo real
- **Sección de noticias destacadas** antes del feed principal
- **Completamente responsivo** para móviles y escritorio
- **Soporte para logo personalizado** desde el customizador
- **Optimizado para rendimiento** y SEO

## Instalación

1. **Descargar el tema:**
   - Descarga todos los archivos del tema
   - Comprime la carpeta `lanota-2026-theme` en un archivo ZIP

2. **Instalar en WordPress:**
   - Ve a `Apariencia > Temas` en tu panel de WordPress
   - Haz clic en "Añadir nuevo" > "Subir tema"
   - Selecciona el archivo ZIP del tema
   - Haz clic en "Instalar ahora"

3. **Activar el tema:**
   - Una vez instalado, haz clic en "Activar"

## Configuración

### Logo Personalizado
1. Ve a `Apariencia > Personalizar > Identidad del sitio`
2. Sube tu logo en "Logo del sitio"
3. Ajusta el tamaño según sea necesario

### Menús de Navegación
1. Ve a `Apariencia > Menús`
2. Crea un nuevo menú o edita uno existente
3. Asigna el menú a la ubicación "Primary Menu"

### Noticias Destacadas
Para marcar publicaciones como destacadas:
1. Edita cualquier publicación
2. En el panel lateral derecho, busca "Noticia Destacada"
3. Marca la casilla para destacar la publicación

### Colores del Tema
1. Ve a `Apariencia > Personalizar > Theme Colors`
2. Ajusta el color principal según tu marca

## Estructura de Archivos

```
lanota-2026-theme/
├── style.css                 # Estilos principales
├── index.php                 # Plantilla principal
├── header.php               # Cabecera del sitio
├── footer.php               # Pie de página
├── functions.php            # Funciones del tema
├── single.php               # Plantilla de publicación individual
├── search.php               # Plantilla de resultados de búsqueda
├── category.php             # Plantilla de categorías
├── comments.php             # Plantilla de comentarios
├── js/
│   └── main.js             # JavaScript principal
└── template-parts/
    └── content-post.php    # Plantilla de publicación
```

## Funcionalidades Principales

### Toggle Tema Oscuro/Claro
- Ubicado en la esquina superior derecha del sidebar
- Guarda la preferencia del usuario en localStorage
- Transición suave entre temas

### Scroll Infinito
- Carga automática de más publicaciones al hacer scroll
- Indicador de carga visual
- Optimizado para rendimiento

### Búsqueda Inteligente
- Sugerencias en tiempo real mientras escribes
- Búsqueda solo en publicaciones
- Resaltado de términos de búsqueda en resultados

### Diseño Responsivo
- **Escritorio:** 3 columnas (sidebar izquierdo, contenido, sidebar derecho)
- **Tablet:** 3 columnas compactas con iconos
- **Móvil:** 1 columna con navegación inferior fija

## Personalización

### Colores CSS
El tema utiliza variables CSS para fácil personalización:

```css
:root {
  --primary-color: #1da1f2;
  --background-color: #ffffff;
  --surface-color: #f8f9fa;
  --text-color: #14171a;
  --text-secondary: #657786;
  --border-color: #e1e8ed;
}
```

### Añadir Nuevos Widgets
El tema incluye áreas de widgets personalizables en el sidebar derecho.

## Optimizaciones

### Rendimiento
- Minificación de recursos
- Lazy loading de imágenes
- Eliminación de scripts innecesarios de WordPress
- Debounce en eventos de scroll

### SEO
- Estructura semántica HTML5
- Meta tags optimizados
- Soporte para Open Graph
- Breadcrumbs automáticos

### Seguridad
- Headers de seguridad HTTP
- Sanitización de datos
- Protección CSRF en formularios

## Soporte para Navegadores

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+
- Internet Explorer 11 (funcionalidad básica)

## Requisitos del Sistema

- WordPress 5.0 o superior
- PHP 7.4 o superior
- MySQL 5.6 o superior

## Categorías Recomendadas

Para obtener el mejor rendimiento del tema, se recomienda crear estas categorías:

- Noticias
- Deportes
- Tecnología
- Entretenimiento
- Política
- Economía

## Troubleshooting

### El scroll infinito no funciona
- Verifica que JavaScript esté habilitado
- Comprueba la consola del navegador por errores
- Asegúrate de que AJAX esté funcionando correctamente

### El toggle de tema no guarda la preferencia
- Verifica que localStorage esté disponible en el navegador
- Comprueba que no haya errores de JavaScript

### Las imágenes no se cargan
- Verifica los permisos de archivos
- Comprueba que las imágenes existan en el servidor
- Revisa la configuración de medios en WordPress

## Contribuir

Si encuentras bugs o tienes sugerencias de mejora:

1. Reporta el issue con detalles específicos
2. Incluye información del navegador y versión de WordPress
3. Proporciona pasos para reproducir el problema

## Licencia

Este tema está licenciado bajo GPL v2 o posterior.

## Créditos

Desarrollado con las mejores prácticas de WordPress y optimizado para medios de comunicación modernos.

---

**Versión:** 1.0  
**Última actualización:** Agosto 2025  
**Compatibilidad:** WordPress 5.0+
