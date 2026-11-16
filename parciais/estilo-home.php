<?php defined('RAIZ') || exit; ?>
<style>/* latin-ext */
@font-face {
  font-family: 'Marcellus';
  font-style: normal;
  font-weight: 400;
  font-display: swap;
  src: url("assets/fonts/d030719e.woff2") format('woff2');
  unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF;
}
/* latin */
@font-face {
  font-family: 'Marcellus';
  font-style: normal;
  font-weight: 400;
  font-display: swap;
  src: url("assets/fonts/4c2bca52.woff2") format('woff2');
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}
/* cyrillic-ext */
@font-face {
  font-family: 'Mulish';
  font-style: normal;
  font-weight: 400;
  font-display: swap;
  src: url("assets/fonts/a1b14c80.woff2") format('woff2');
  unicode-range: U+0460-052F, U+1C80-1C8A, U+20B4, U+2DE0-2DFF, U+A640-A69F, U+FE2E-FE2F;
}
/* cyrillic */
@font-face {
  font-family: 'Mulish';
  font-style: normal;
  font-weight: 400;
  font-display: swap;
  src: url("assets/fonts/319abc76.woff2") format('woff2');
  unicode-range: U+0301, U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116;
}
/* vietnamese */
@font-face {
  font-family: 'Mulish';
  font-style: normal;
  font-weight: 400;
  font-display: swap;
  src: url("assets/fonts/9c31d2a0.woff2") format('woff2');
  unicode-range: U+0102-0103, U+0110-0111, U+0128-0129, U+0168-0169, U+01A0-01A1, U+01AF-01B0, U+0300-0301, U+0303-0304, U+0308-0309, U+0323, U+0329, U+1EA0-1EF9, U+20AB;
}
/* latin-ext */
@font-face {
  font-family: 'Mulish';
  font-style: normal;
  font-weight: 400;
  font-display: swap;
  src: url("assets/fonts/3dec0203.woff2") format('woff2');
  unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF;
}
/* latin */
@font-face {
  font-family: 'Mulish';
  font-style: normal;
  font-weight: 400;
  font-display: swap;
  src: url("assets/fonts/23e9ba1c.woff2") format('woff2');
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}
/* cyrillic-ext */
@font-face {
  font-family: 'Mulish';
  font-style: normal;
  font-weight: 500;
  font-display: swap;
  src: url("assets/fonts/a1b14c80.woff2") format('woff2');
  unicode-range: U+0460-052F, U+1C80-1C8A, U+20B4, U+2DE0-2DFF, U+A640-A69F, U+FE2E-FE2F;
}
/* cyrillic */
@font-face {
  font-family: 'Mulish';
  font-style: normal;
  font-weight: 500;
  font-display: swap;
  src: url("assets/fonts/319abc76.woff2") format('woff2');
  unicode-range: U+0301, U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116;
}
/* vietnamese */
@font-face {
  font-family: 'Mulish';
  font-style: normal;
  font-weight: 500;
  font-display: swap;
  src: url("assets/fonts/9c31d2a0.woff2") format('woff2');
  unicode-range: U+0102-0103, U+0110-0111, U+0128-0129, U+0168-0169, U+01A0-01A1, U+01AF-01B0, U+0300-0301, U+0303-0304, U+0308-0309, U+0323, U+0329, U+1EA0-1EF9, U+20AB;
}
/* latin-ext */
@font-face {
  font-family: 'Mulish';
  font-style: normal;
  font-weight: 500;
  font-display: swap;
  src: url("assets/fonts/3dec0203.woff2") format('woff2');
  unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF;
}
/* latin */
@font-face {
  font-family: 'Mulish';
  font-style: normal;
  font-weight: 500;
  font-display: swap;
  src: url("assets/fonts/23e9ba1c.woff2") format('woff2');
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}
/* cyrillic-ext */
@font-face {
  font-family: 'Mulish';
  font-style: normal;
  font-weight: 600;
  font-display: swap;
  src: url("assets/fonts/a1b14c80.woff2") format('woff2');
  unicode-range: U+0460-052F, U+1C80-1C8A, U+20B4, U+2DE0-2DFF, U+A640-A69F, U+FE2E-FE2F;
}
/* cyrillic */
@font-face {
  font-family: 'Mulish';
  font-style: normal;
  font-weight: 600;
  font-display: swap;
  src: url("assets/fonts/319abc76.woff2") format('woff2');
  unicode-range: U+0301, U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116;
}
/* vietnamese */
@font-face {
  font-family: 'Mulish';
  font-style: normal;
  font-weight: 600;
  font-display: swap;
  src: url("assets/fonts/9c31d2a0.woff2") format('woff2');
  unicode-range: U+0102-0103, U+0110-0111, U+0128-0129, U+0168-0169, U+01A0-01A1, U+01AF-01B0, U+0300-0301, U+0303-0304, U+0308-0309, U+0323, U+0329, U+1EA0-1EF9, U+20AB;
}
/* latin-ext */
@font-face {
  font-family: 'Mulish';
  font-style: normal;
  font-weight: 600;
  font-display: swap;
  src: url("assets/fonts/3dec0203.woff2") format('woff2');
  unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF;
}
/* latin */
@font-face {
  font-family: 'Mulish';
  font-style: normal;
  font-weight: 600;
  font-display: swap;
  src: url("assets/fonts/23e9ba1c.woff2") format('woff2');
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}
/* cyrillic-ext */
@font-face {
  font-family: 'Mulish';
  font-style: normal;
  font-weight: 700;
  font-display: swap;
  src: url("assets/fonts/a1b14c80.woff2") format('woff2');
  unicode-range: U+0460-052F, U+1C80-1C8A, U+20B4, U+2DE0-2DFF, U+A640-A69F, U+FE2E-FE2F;
}
/* cyrillic */
@font-face {
  font-family: 'Mulish';
  font-style: normal;
  font-weight: 700;
  font-display: swap;
  src: url("assets/fonts/319abc76.woff2") format('woff2');
  unicode-range: U+0301, U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116;
}
/* vietnamese */
@font-face {
  font-family: 'Mulish';
  font-style: normal;
  font-weight: 700;
  font-display: swap;
  src: url("assets/fonts/9c31d2a0.woff2") format('woff2');
  unicode-range: U+0102-0103, U+0110-0111, U+0128-0129, U+0168-0169, U+01A0-01A1, U+01AF-01B0, U+0300-0301, U+0303-0304, U+0308-0309, U+0323, U+0329, U+1EA0-1EF9, U+20AB;
}
/* latin-ext */
@font-face {
  font-family: 'Mulish';
  font-style: normal;
  font-weight: 700;
  font-display: swap;
  src: url("assets/fonts/3dec0203.woff2") format('woff2');
  unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF;
}
/* latin */
@font-face {
  font-family: 'Mulish';
  font-style: normal;
  font-weight: 700;
  font-display: swap;
  src: url("assets/fonts/23e9ba1c.woff2") format('woff2');
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}
</style>
<style>
  html { scroll-behavior: smooth; }
  body { margin: 0; background: #FBF3EE; }
  a { color: #996C55; }
  a:hover { color: #8A5340; }
  ::selection { background: #E7CDBE; }
  #nav-toggle { display: none; }
  header a, header button, #mobile-nav-wrap a { -webkit-tap-highlight-color: transparent; }
  :focus-visible { outline: 2px solid #7D5843; outline-offset: 3px; }
  header :focus-visible, footer :focus-visible, #mobile-nav-wrap :focus-visible, #procedimentos :focus-visible, #home :focus-visible { outline-color: #F3E4DA; }
  #home, #sobre, #procedimentos, #resultados, #cursos, #depoimentos, #contato { scroll-margin-top: 40px; }
  @media (max-width: 1100px) {
    #home, #sobre, #procedimentos, #resultados, #cursos, #depoimentos, #contato { scroll-margin-top: 30px; }
    #desktop-nav { display: none !important; }
    #nav-toggle { display: flex !important; }
    #site-header { grid-template-columns: 1fr !important; }
    #site-logo-wrap { justify-self: center !important; }
    #header-right { position: absolute !important; right: 16px; top: 50%; transform: translateY(-50%); }
    #desktop-nav { position: absolute; }
  }
  @media (max-width: 900px) {
    #home { align-items: flex-start !important; padding-top: 24px !important; }
    #sobre { grid-template-columns: 1fr !important; padding-top: 0px !important; text-align: center; }
    #sobre-foto { display: none !important; }
    #sobre-photo-mobile { display: block !important; }
    #sobre > div { align-items: center; }
    #sobre > div > div { justify-content: center; }
    #sobre-stats { margin-top: 32px !important; margin-bottom: 32px !important; justify-content: center; }
    #sobre-video-block { grid-template-columns: 1fr !important; padding-top: 0px !important; }
    #saiba-arrow { display: none !important; }
    #desktop-cta { display: none !important; }
    #contato-grid { grid-template-columns: 1fr !important; gap: 26px !important; }
    #contato-grid > div:first-child { margin-left: 0 !important; text-align: left; }
    #avaliacao-online-link { align-self: center !important; }
    #footer-grid { grid-template-columns: 1fr !important; text-align: center; }
    #footer-grid > div:first-child { display: flex; justify-content: center; }
    #footer-grid > div:first-child img { height: 130px !important; }
    #footer-menus, #footer-cursos { display: none !important; }
    .mobile-scroll-arrow { display: flex !important; }
    #resultados-grid { display: flex !important; overflow-x: auto; scroll-snap-type: x proximity; -webkit-overflow-scrolling: touch; }
    #resultados-grid > * { flex: 0 0 78%; scroll-snap-align: start; }
    #ver-mais-cell { display: none !important; }
    #ver-mais-mobile { display: flex !important; }
    #procedimentos-arrow-left { left: 4px !important; }
    #procedimentos-arrow-right { right: 4px !important; }
    #procedimentos-grid { padding-left: 10%; padding-right: 10%; }
    #procedimentos-grid > * { flex: 0 0 78% !important; scroll-snap-align: center; }
    #depoimentos-arrow-left { left: 4px !important; }
    #depoimentos-arrow-right { right: 4px !important; }
    #depoimentos-grid { padding-left: 6%; padding-right: 6%; }
    #depoimentos-grid > * { flex: 0 0 84% !important; scroll-snap-align: center; }
    #hero-content { padding-top: 0px !important; padding-bottom: 40px !important; }
    #hero-bg-img { width: 130% !important; right: -30% !important; top: auto !important; bottom: -2% !important; height: 55% !important; }
  }
  #procedimentos-grid::-webkit-scrollbar, #resultados-grid::-webkit-scrollbar, #depoimentos-grid::-webkit-scrollbar { display: none; }
  #procedimentos-grid, #resultados-grid, #depoimentos-grid { scrollbar-width: none; }
  #site-header { transition: padding 0.25s ease; }
  #logo-full, #logo-gs { transition: height 0.25s ease, width 0.25s ease, opacity 0.2s ease; }
  #site-header.scrolled { padding: 8px 24px !important; }
  #site-header.scrolled #logo-full, #site-header.scrolled #logo-gs { height: 70px !important; }
  #site-header.scrolled #logo-full { opacity: 0 !important; }
  #site-header.scrolled #logo-gs { opacity: 1 !important; }
  .nav-link:hover { color: #E7CDBE !important; }
  .btn-fill:hover { background: #6B4A39 !important; }
  .btn-outline:hover { background: rgba(243,228,218,0.18) !important; }
  .btn-outline-brown:hover { background: #F3E4DA !important; }
  .btn-lift:hover { transform: translateY(-2px); }
  .curso-outline:hover { background: #F3E4DA !important; }
  .ba-alca { position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); width:40px; height:40px; border-radius:50%; background:#FBF3EE; display:flex; align-items:center; justify-content:center; box-shadow:0 2px 10px rgba(58,42,34,0.35); }
  .video-play { position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); width:64px; height:64px; border-radius:50%; border:none; padding:0 0 0 4px; box-sizing:border-box; background:rgba(58,42,34,0.62); display:flex; align-items:center; justify-content:center; cursor:pointer; z-index:2; box-shadow:0 2px 14px rgba(0,0,0,0.3); }
  .video-play[hidden] { display:none; }
  .video-play:hover { background:rgba(58,42,34,0.8); }
  .reveal { opacity: 0; transform: translateY(28px); transition: opacity 0.8s ease, transform 0.8s ease; }
  .reveal.visible { opacity: 1; transform: translateY(0); }
</style>
