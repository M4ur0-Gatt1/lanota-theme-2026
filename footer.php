    <!-- Scroll To Top (desktop) -->
    <button id="scroll-to-top" class="scroll-to-top" aria-label="Volver arriba">
        <i class="fas fa-arrow-up" aria-hidden="true"></i>
    </button>

    <!-- Global Search Modal -->
    <div class="search-modal" id="search-modal">
        <div class="search-modal-content">
            <div class="search-modal-header">
                <h3>Buscar Noticias</h3>
                <button class="close-modal" id="close-search-modal" aria-label="Cerrar búsqueda">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>
            <div class="search-modal-body">
                <form role="search" method="get" action="<?php echo home_url('/'); ?>">
                    <div class="search-input-container">
                        <input type="search" 
                               class="search-input modal-search-input" 
                               placeholder="¿Qué estás buscando?" 
                               name="s"
                               id="modal-search-input">
                        <button type="submit" class="search-submit-btn">
                            <i class="fas fa-search" aria-hidden="true"></i>
                            Buscar
                        </button>
                    </div>
                </form>
                <div class="search-suggestions" id="search-suggestions"></div>
            </div>
        </div>
    </div>

    <?php wp_footer(); ?>
</body>
</html>
