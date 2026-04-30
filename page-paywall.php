<?php
/*
Template Name: Paywall / Suscripción
*/
?>
<?php get_header(); ?>

<main class="main-content">
  <div class="content-area">
    <article class="post">
      <header class="post-header">
        <h1 class="post-title">Suscripción</h1>
        <p class="post-excerpt">Accedé a publicar y a todo nuestro contenido premium.</p>
      </header>

      <div class="post-content">
        <!-- Subscribe with Google (Basic) -->
        <script async type="application/javascript"
                src="https://news.google.com/swg/js/v1/swg-basic.js"></script>
        <script>
          (self.SWG_BASIC = self.SWG_BASIC || []).push(basicSubscriptions => {
            basicSubscriptions.init({
              type: "NewsArticle",
              isPartOfType: ["Product"],
              isPartOfProductId: "CAoiEGy6YkUcqDvzWHARFduvqcQ:openaccess",
              clientOptions: { theme: "light", lang: "es-419" },
            });
          });
        </script>

        <p>Si ya estás suscripto, iniciá sesión con tu cuenta de Google cuando se te solicite.</p>
      </div>
    </article>
  </div>
</main>

<?php get_footer(); ?>
