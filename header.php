<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <style id="critical-css">
      /* Critical: header/nav minimal layout */
      .mobile-header { display:flex; align-items:center; justify-content:space-between; padding:8px 12px; }
      .mobile-header .mobile-logo { display:flex; align-items:center; }
      .mobile-header .mobile-controls { display:flex; align-items:center; }
      .hamburger-menu, .mobile-menu-close, .search-btn, .theme-toggle { background:transparent; border:0; padding:6px; cursor:pointer; }
      .hamburger-menu svg, .mobile-menu-close svg, .search-btn svg, .theme-toggle svg { width:20px; height:20px; fill:currentColor; }
      /* Basic hero spacing to avoid CLS */
      .hero-lead { margin:12px 0 16px; }
      .hero-image-wrapper { position:relative; }
      .hero-badge { position:absolute; left:10px; top:10px; background:rgba(0,0,0,.65); color:#fff; padding:4px 8px; border-radius:12px; font-size:12px; }
    </style>
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<!-- SVG Sprite: initial icons for header/nav -->
<svg xmlns="http://www.w3.org/2000/svg" style="display:none">
  <symbol id="icon-bars" viewBox="0 0 448 512"><path d="M16 132h416c8.8 0 16-7.2 16-16V92c0-8.8-7.2-16-16-16H16C7.2 76 0 83.2 0 92v24c0 8.8 7.2 16 16 16zm0 160h416c8.8 0 16-7.2 16-16v-24c0-8.8-7.2-16-16-16H16c-8.8 0-16 7.2-16 16v24c0 8.8 7.2 16 16 16zm0 160h416c8.8 0 16-7.2 16-16v-24c0-8.8-7.2-16-16-16H16c-8.8 0-16 7.2-16 16v24c0 8.8 7.2 16 16 16z"/></symbol>
  <symbol id="icon-times" viewBox="0 0 352 512"><path d="M242.72 256L342.63 156.09c12.28-12.28 12.28-32.19 0-44.48l-22.24-22.24c-12.28-12.28-32.19-12.28-44.48 0L176 189.28 76.09 89.37c-12.28-12.28-32.19-12.28-44.48 0L9.37 111.61c-12.28 12.28-12.28 32.19 0 44.48L109.28 256 9.37 355.91c-12.28 12.28-12.28 32.19 0 44.48l22.24 22.24c12.28 12.28 32.19 12.28 44.48 0L176 322.72l99.91 99.91c12.28 12.28 32.19 12.28 44.48 0l22.24-22.24c12.28-12.28 12.28-32.19 0-44.48L242.72 256z"/></symbol>
  <symbol id="icon-search" viewBox="0 0 512 512"><path d="M505 442.7L405.3 343a208 208 0 10-62.3 62.3L442.7 505c9.4 9.4 24.6 9.4 33.9 0l28.3-28.3c9.4-9.4 9.4-24.6 0-34zM208 336a128 128 0 110-256 128 128 0 010 256z"/></symbol>
  <symbol id="icon-moon" viewBox="0 0 512 512"><path d="M279.135 512c78.962 0 149.33-40.197 190.33-101.32 7.641-11.232-4.042-25.231-16.604-20.643C431.5 408.8 405.9 414 379 414c-106 0-192-86-192-192 0-27 5.2-52.5 14-75.9 4.6-12.6-9.4-24.2-20.6-16.6C119.2 170.6 79 241 79 320 79 428.5 170.6 512 279.135 512z"/></symbol>
  <symbol id="icon-sun" viewBox="0 0 512 512"><path d="M256 152a104 104 0 100 208 104 104 0 000-208zm246 104h-58a16 16 0 000 32h58a16 16 0 000-32zM16 256h58a16 16 0 000-32H16a16 16 0 000 32zm369.1 136.9l41 41a16 16 0 0022.6-22.6l-41-41a16 16 0 00-22.6 22.6zM63.4 78.6l41 41A16 16 0 00127 97l-41-41A16 16 0 0063.4 78.6zm0 354.8A16 16 0 0086 456l41-41a16 16 0 10-22.6-22.6l-41 41zM425 138l41-41A16 16 0 10443.4 74l-41 41A16 16 0 10425 138zM256 16a16 16 0 0016-16V16a16 16 0 00-32 0V0a16 16 0 0016 16zm0 480a16 16 0 0016-16v16a16 16 0 00-32 0v-16a16 16 0 0016 16z"/></symbol>
  <symbol id="icon-chevron-left" viewBox="0 0 320 512"><path d="M34.52 239.03L228.87 44.69c9.37-9.37 24.57-9.37 33.94 0l22.67 22.67c9.36 9.36 9.37 24.52.04 33.9L131.49 256l154.03 154.74c9.34 9.38 9.32 24.54-.04 33.9l-22.67 22.67c-9.37 9.37-24.57 9.37-33.94 0L34.52 272.97c-9.37-9.37-9.37-24.57 0-33.94z"/></symbol>
  <symbol id="icon-chevron-right" viewBox="0 0 320 512"><path d="M285.48 272.97L91.13 467.31c-9.37 9.37-24.57 9.37-33.94 0L34.52 444.64c-9.36-9.36-9.37-24.52-.04-33.9L188.51 256 34.48 101.26c-9.34-9.38-9.32-24.54.04-33.9l22.67-22.67c9.37-9.37 24.57-9.37 33.94 0L285.48 239.03c9.37 9.37 9.37 24.57 0 33.94z"/></symbol>
  <symbol id="icon-arrow-right" viewBox="0 0 448 512"><path d="M438.6 278.6l-160 160c-9.4 9.4-24.6 9.4-33.9 0l-22.6-22.6c-9.5-9.5-9.3-24.8.4-34.3L312.7 296H24c-13.3 0-24-10.7-24-24v-32c0-13.3 10.7-24 24-24h288.7l-90.2-85.7c-9.8-9.3-10-24.8-.4-34.3l22.6-22.6c9.4-9.4 24.6-9.4 33.9 0l160 160c9.5 9.5 9.5 24.9 0 34.2z"/></symbol>
  <symbol id="icon-home" viewBox="0 0 576 512"><path d="M541 229.16l-61-50.84V72a24 24 0 00-24-24h-40a24 24 0 00-24 24v24.37L314.52 43a35.37 35.37 0 00-45 0L35 229.16a12 12 0 00-1.16 17l20.4 24.53a12 12 0 0017 1.22L96 251.06V464a48 48 0 0048 48h96V368h96v144h96a48 48 0 0048-48V251.06l24.78 20.85a12 12 0 0017-1.22l20.4-24.53a12 12 0 00-1.18-17z"/></symbol>
  <symbol id="icon-bullhorn" viewBox="0 0 576 512"><path d="M480 64c-46.9 0-86.1 28.7-146.3 69.3C286.5 164.9 238.7 192 192 192H64c-35.3 0-64 28.7-64 64v64c0 35.3 28.7 64 64 64h9.6c-3.1 10-5.6 20.5-7.3 31.4-3.3 20.9 12.8 40.6 34 40.6H128c12.8 0 24.4-7.5 29.9-19.1 5.6-11.7 10.4-24 14.3-36.9l.2-.7c24.5 0 48.9 3.3 72.6 9.8l99.7 27.3c21.7 5.9 44.3 8.9 66.3 8.9H480c53 0 96-43 96-96V160c0-53-43-96-96-96zM96 320H64v-64h32v64z"/></symbol>
  <symbol id="icon-calendar" viewBox="0 0 448 512"><path d="M152 64c0-8.8-7.2-16-16-16h-16c-8.8 0-16 7.2-16 16v32H48C21.5 96 0 117.5 0 144v288c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48V144c0-26.5-21.5-48-48-48h-56V64c0-8.8-7.2-16-16-16h-16c-8.8 0-16 7.2-16 16v32H152V64zm-56 96h256v48H96v-48z"/></symbol>
  <symbol id="icon-arrow-up" viewBox="0 0 384 512"><path d="M169.4 105.4c12.5-12.5 32.8-12.5 45.3 0l160 160c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L216 197.3V464c0 17.7-14.3 32-32 32s-32-14.3-32-32V197.3L54.6 310.6c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3l160-160z"/></symbol>
</svg>
