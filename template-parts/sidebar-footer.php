<?php
/*
 * Sidebar footer: institutional information links
 * Usage: get_template_part('template-parts/sidebar-footer');
 */
?>
<div class="sidebar-footer" aria-label="Información institucional">
    <nav class="sidebar-footer-links" aria-label="Enlaces institucionales">
        <span class="copyright">La Nota Tucumán 2025 · Todos los derechos reservados · San Miguel de Tucumán, Argentina</span>
        <a href="<?php echo esc_url( home_url('/quienes-somos/') ); ?>">Sobre nosotros</a>
        <span class="sep" aria-hidden="true">·</span>
        <a href="<?php echo esc_url( home_url('/mediakit/') ); ?>">MediaKit</a>
    </nav>
</div>
