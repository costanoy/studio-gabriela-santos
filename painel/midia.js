/*
 * Envio de fotos e vídeos do painel (Lote 2B). JavaScript puro, sem dependências.
 *
 * INTERFACE (para o painel.js do Lote 2A)
 *
 *   window.PainelMidia = {
 *     escolherFoto(opcoes)  -> Promise<{arquivo, largura, altura}>
 *     escolherVideo(opcoes) -> Promise<{arquivo, poster, largura, altura}>
 *     mensagemErro(codigo, dados) -> string em português simples (sem termos técnicos)
 *   }
 *
 *   opcoes (todas opcionais, menos csrf):
 *     csrf         texto do token; vai no cabeçalho X-CSRF de todo envio
 *     onProgresso  função({etapa, enviado, total, percentual, texto}) chamada durante o envio
 *     maximo       só fotos: maior lado final em pixels, 1600 (padrão) ou 800 (comparador)
 *     apiUrl       endereço da API (padrão "api-midia.php", relativo à página do painel)
 *     arquivo      File já escolhido; sem ele abre o seletor do celular (precisa de um toque da pessoa)
 *
 *   As Promises:
 *     - resolvem com o caminho (arquivo, poster) já gravado em conteudo/midia/, pronto para o JSON do site;
 *     - rejeitam com Error cujo .message já é a frase em português para mostrar à pessoa,
 *       .codigo é o código da lista abaixo e .dados traz números úteis (ex.: tamanho do vídeo).
 *       Cancelar não é erro técnico: .codigo === 'cancelado' (o painel pode só fechar a janela).
 *
 *   A janela de progresso (barra, "Cancelar", "Tentar de novo", capa do vídeo) é desenhada aqui dentro.
 *   Nada é mudado no site: o arquivo só entra no site quando o painel manda o JSON para "Salvar".
 *
 *   Códigos de erro: tipo, heic, foto_ilegivel, foto_pesada, foto_enorme, hevc, grande_demais, video_pesado,
 *   video_formato, video_codec, video_ilegivel, rede, cancelado, poster_falhou, envio_nao_encontrado,
 *   parte_invalida, partes_faltando, sem_espaco, gravacao, limite_servidor, sessao_expirada, csrf,
 *   nao_configurado. Qualquer outro código cai numa frase genérica.
 *
 * Segurança: a página do painel tem CSP sem script ou estilo inline; aqui o DOM é montado só com
 * createElement/textContent e o CSS entra por folha de estilo construída (adoptedStyleSheets).
 */
(function () {
  'use strict';

  var API_PADRAO = 'api-midia.php';
  var LADO_CANVAS_FOTO = 2400;          // abaixo do limite de canvas do iOS (~16,7 milhões de pixels)
  var LIMITE_VIDEO_MB = 250;

  /* ------------------------------------------------------------------ frases (P4) */

  var FRASES = {
    tipo: 'Este arquivo não é foto nem vídeo. Escolha uma foto ou um vídeo da sua galeria.',
    heic: 'Esta foto está num formato que o site não consegue abrir. Escolha a foto de novo pela galeria. Para as próximas, vá em Ajustes → Câmera → Formatos → Mais compatível.',
    foto_ilegivel: 'Não consegui abrir esta foto. Escolha outra foto da galeria.',
    foto_pesada: 'Esta foto é grande demais para enviar. Escolha outra ou tire um print dela.',
    foto_enorme: 'Esta foto é grande demais para o site. Escolha outra foto ou use uma versão menor dela.',
    hevc: 'Este vídeo não abre em alguns celulares Android e computadores, por isso o site não aceita. Para gravar de novo: Ajustes → Câmera → Formatos → Mais compatível.',
    grande_demais: 'Este vídeo é maior que Full HD (1080p). No iPhone, vá em Ajustes → Câmera → Gravar Vídeo, escolha 1080p HD e grave de novo.',
    video_pesado: function (d) {
      return 'Este vídeo tem ' + (d && d.tamanho_mb ? d.tamanho_mb + ' MB' : 'muitos MB') + ' e o limite é ' +
        ((d && d.limite_mb) || LIMITE_VIDEO_MB) + ' MB. No app Fotos, toque em Editar e corte o começo ou o fim. Depois tente de novo.';
    },
    video_formato: 'Este vídeo não está num formato que o site aceita. Envie um vídeo MP4 ou MOV gravado pela câmera do celular.',
    video_codec: 'Este vídeo está num formato que o site não toca. Grave de novo com a câmera do celular, em Ajustes → Câmera → Formatos → Mais compatível.',
    video_ilegivel: 'Não consegui ler este vídeo. Ele pode estar estragado. Escolha outro ou grave de novo.',
    rede: 'A internet caiu no meio do envio. Nada foi mudado no site. Toque em "Tentar de novo".',
    cancelado: 'Envio cancelado. Nada foi mudado no site.',
    poster_falhou: 'Não consegui tirar a capa deste vídeo. Tente de novo ou escolha outro vídeo.',
    envio_nao_encontrado: 'Este envio ficou parado por muito tempo e não pode continuar. Escolha o vídeo de novo.',
    parte_invalida: 'Um pedaço do vídeo não chegou direito. Toque em "Tentar de novo".',
    partes_faltando: 'Faltou um pedaço do vídeo. Toque em "Tentar de novo".',
    sem_espaco: 'Não há espaço para guardar este vídeo agora. Fale com o Vinicius.',
    gravacao: 'Não consegui guardar o arquivo. O site continua como estava. Tente de novo.',
    limite_servidor: 'Este arquivo é maior do que o site aceita de uma vez. Tente de novo. Se continuar, fale com o Vinicius.',
    sessao_expirada: 'Por segurança, entre de novo. Suas mudanças foram guardadas.',
    csrf: 'Por segurança, entre de novo. Suas mudanças foram guardadas.',
    nao_configurado: 'O painel ainda não foi configurado. Fale com o Vinicius.'
  };
  var FRASE_GENERICA = 'Algo deu errado. Nada foi mudado no site. Tente de novo.';

  function mensagemErro(codigo, dados) {
    var f = FRASES[codigo];
    if (typeof f === 'function') { return f(dados || {}); }
    return f || FRASE_GENERICA;
  }

  function erro(codigo, dados) {
    var e = new Error(mensagemErro(codigo, dados));
    e.codigo = codigo;
    e.dados = dados || {};
    return e;
  }

  function mb(bytes) { return Math.max(1, Math.round(bytes / 1048576)); }

  /* ------------------------------------------------------------------ janela de progresso */

  var CSS = [
    '.pm-fundo{position:fixed;inset:0;z-index:2147483000;background:rgba(40,28,22,.62);display:flex;align-items:flex-end;justify-content:center;padding:16px;font:17px/1.4 system-ui,-apple-system,"Segoe UI",sans-serif}',
    '@media (min-width:600px){.pm-fundo{align-items:center}}',
    '.pm-caixa{background:#fff;color:#2b211c;width:100%;max-width:440px;border-radius:16px;padding:20px;box-shadow:0 10px 40px rgba(0,0,0,.35);padding-bottom:calc(20px + env(safe-area-inset-bottom))}',
    '.pm-titulo{margin:0 0 8px;font-size:19px;font-weight:700}',
    '.pm-texto{margin:0 0 12px;font-size:17px}',
    '.pm-aviso{margin:0 0 12px;padding:10px 12px;border-radius:10px;background:#fff4e0;color:#5a3b00;font-size:16px}',
    '.pm-erro{margin:0 0 12px;padding:10px 12px;border-radius:10px;background:#fde8e6;color:#7a1410;font-size:16px}',
    '.pm-barra{width:100%;height:14px;margin:0 0 8px;accent-color:#7d5843}',
    '.pm-capa{display:block;max-width:100%;max-height:260px;margin:0 auto 12px;border-radius:10px;background:#eee}',
    '.pm-botoes{display:flex;gap:10px;flex-wrap:wrap;margin-top:8px}',
    '.pm-botao{flex:1 1 140px;min-height:48px;padding:0 16px;border-radius:12px;border:2px solid #7d5843;background:#fff;color:#7d5843;font:inherit;font-weight:700;cursor:pointer}',
    '.pm-botao.pm-principal{background:#7d5843;color:#fff}'
  ].join('\n');

  var folha = null;
  function garantirCss() {
    if (folha !== null) { return; }
    folha = false;
    try {
      if (typeof CSSStyleSheet === 'function' && 'adoptedStyleSheets' in document) {
        var s = new CSSStyleSheet();
        s.replaceSync(CSS);
        document.adoptedStyleSheets = Array.prototype.concat.call(document.adoptedStyleSheets || [], [s]);
        folha = true;
      }
    } catch (e) { folha = false; }
  }

  function el(tag, classe, texto) {
    var n = document.createElement(tag);
    if (classe) { n.className = classe; }
    if (texto != null) { n.textContent = texto; }
    return n;
  }

  /** Cria a janela. Tudo por textContent. */
  function abrirJanela(titulo) {
    garantirCss();
    var anterior = document.activeElement;
    var fundo = el('div', 'pm-fundo');
    var caixa = el('div', 'pm-caixa');
    caixa.setAttribute('role', 'dialog');
    caixa.setAttribute('aria-modal', 'true');
    var h = el('h2', 'pm-titulo', titulo);
    h.id = 'pm-titulo-' + Math.random().toString(36).slice(2, 8);
    caixa.setAttribute('aria-labelledby', h.id);
    var aviso = el('p', 'pm-aviso');
    aviso.hidden = true;
    var msg = el('p', 'pm-erro');
    msg.hidden = true;
    msg.setAttribute('role', 'alert');
    var texto = el('p', 'pm-texto');
    texto.setAttribute('aria-live', 'polite');
    var barra = document.createElement('progress');
    barra.className = 'pm-barra';
    barra.max = 100;
    barra.value = 0;
    barra.hidden = true;
    var capa = el('img', 'pm-capa');
    capa.alt = 'Capa do vídeo';
    capa.hidden = true;
    var botoes = el('div', 'pm-botoes');
    caixa.appendChild(h);
    caixa.appendChild(capa);
    caixa.appendChild(aviso);
    caixa.appendChild(msg);
    caixa.appendChild(texto);
    caixa.appendChild(barra);
    caixa.appendChild(botoes);
    fundo.appendChild(caixa);
    document.body.appendChild(fundo);

    var j = {
      raiz: fundo,
      texto: function (t) { texto.textContent = t || ''; texto.hidden = !t; },
      aviso: function (t) { aviso.textContent = t || ''; aviso.hidden = !t; },
      erro: function (t) { msg.textContent = t || ''; msg.hidden = !t; },
      barra: function (v) {
        if (v == null) { barra.hidden = true; return; }
        barra.hidden = false;
        barra.value = Math.max(0, Math.min(100, v));
      },
      capa: function (url) { if (url) { capa.src = url; capa.hidden = false; } else { capa.hidden = true; } },
      botoes: function (lista) {
        while (botoes.firstChild) { botoes.removeChild(botoes.firstChild); }
        var primeiro = null;
        (lista || []).forEach(function (b) {
          var x = el('button', 'pm-botao' + (b.principal ? ' pm-principal' : ''), b.rotulo);
          x.type = 'button';
          x.addEventListener('click', b.acao);
          botoes.appendChild(x);
          if (!primeiro) { primeiro = x; }
        });
        if (primeiro) { primeiro.focus(); }
      },
      fechar: function () {
        if (fundo.parentNode) { fundo.parentNode.removeChild(fundo); }
        if (anterior && anterior.focus) { try { anterior.focus(); } catch (e) { /* sem foco */ } }
      }
    };
    j.texto('');
    return j;
  }

  /* ------------------------------------------------------------------ seletor de arquivo */

  function pedirArquivo(accept, op) {
    return new Promise(function (resolve, reject) {
      if (op && op.arquivo) { resolve(op.arquivo); return; }
      var inp = document.createElement('input');
      inp.type = 'file';
      inp.accept = accept;
      inp.hidden = true;
      inp.setAttribute('aria-hidden', 'true');
      document.body.appendChild(inp);
      var limpar = function () { if (inp.parentNode) { inp.parentNode.removeChild(inp); } };
      inp.addEventListener('change', function () {
        var f = inp.files && inp.files[0];
        limpar();
        if (f) { resolve(f); } else { reject(erro('cancelado')); }
      });
      inp.addEventListener('cancel', function () { limpar(); reject(erro('cancelado')); });
      inp.click();
    });
  }

  /* ------------------------------------------------------------------ envio (XHR, por causa do progresso) */

  /**
   * Manda um FormData para a API. Resolve com o JSON ({ok:true...}); rejeita com erro().
   * `ctl` guarda o pedido em andamento para poder cancelar.
   */
  function chamar(op, campos, ctl, onBytes) {
    return new Promise(function (resolve, reject) {
      var fd = new FormData();
      Object.keys(campos).forEach(function (k) {
        var v = campos[k];
        if (v && v.blob) { fd.append(k, v.blob, v.nome); } else { fd.append(k, v); }
      });
      var x = new XMLHttpRequest();
      x.open('POST', op.apiUrl || API_PADRAO);
      x.setRequestHeader('X-CSRF', op.csrf || '');
      x.setRequestHeader('Accept', 'application/json');
      x.responseType = 'text';
      if (onBytes && x.upload) {
        x.upload.onprogress = function (ev) { if (ev.lengthComputable) { onBytes(ev.loaded, ev.total); } };
      }
      if (ctl) { ctl.xhr = x; }
      x.onload = function () {
        if (ctl) { ctl.xhr = null; }
        var j = null;
        try { j = JSON.parse(x.responseText); } catch (e) { j = null; }
        if (j && j.ok) { resolve(j); return; }
        if (j && j.erro) { reject(erro(j.erro, j)); return; }
        if (x.status === 413) { reject(erro('limite_servidor')); return; }
        if (x.status === 401) { reject(erro('sessao_expirada')); return; }
        if (x.status === 403) { reject(erro('csrf')); return; }
        reject(erro(x.status >= 500 || x.status === 0 ? 'rede' : 'gravacao'));
      };
      x.onerror = function () { if (ctl) { ctl.xhr = null; } reject(erro('rede')); };
      x.ontimeout = x.onerror;
      x.onabort = function () { if (ctl) { ctl.xhr = null; } reject(erro('cancelado')); };
      x.send(fd);
    });
  }

  function notificar(op, info) {
    if (typeof op.onProgresso === 'function') {
      try { op.onProgresso(info); } catch (e) { /* o painel não pode derrubar o envio */ }
    }
  }

  /* ------------------------------------------------------------------ fotos */

  function ehHeic(f) {
    return /image\/hei[cf]/i.test(f.type || '') || /\.hei[cf]$/i.test(f.name || '');
  }

  /**
   * Reduz a foto no navegador: lado maior até 2400 px, já em pé (o navegador aplica a orientação),
   * JPEG 0,9 e sem EXIF. Se não der, devolve a original e o servidor decide.
   */
  function reduzirFoto(file) {
    return new Promise(function (resolve, reject) {
      var url = URL.createObjectURL(file);
      var img = new Image();
      img.onload = function () {
        try {
          var w = img.naturalWidth, h = img.naturalHeight;
          if (!w || !h) { throw new Error('vazia'); }
          var esc = Math.min(1, LADO_CANVAS_FOTO / Math.max(w, h));
          var tw = Math.max(1, Math.round(w * esc)), th = Math.max(1, Math.round(h * esc));
          var c = document.createElement('canvas');
          c.width = tw; c.height = th;
          var ctx = c.getContext('2d');
          ctx.fillStyle = '#fff';
          ctx.fillRect(0, 0, tw, th);
          ctx.drawImage(img, 0, 0, tw, th);
          c.toBlob(function (b) {
            URL.revokeObjectURL(url);
            if (b) { resolve({ blob: b, largura: tw, altura: th }); } else { reject(new Error('canvas')); }
          }, 'image/jpeg', 0.9);
        } catch (e) { URL.revokeObjectURL(url); reject(e); }
      };
      img.onerror = function () { URL.revokeObjectURL(url); reject(new Error('abrir')); };
      img.src = url;
    });
  }

  function escolherFoto(op) {
    op = op || {};
    return pedirArquivo('image/jpeg,image/png,image/webp', op).then(function (file) {
      if (!/^image\//i.test(file.type || '') && !/\.(jpe?g|png|webp|hei[cf])$/i.test(file.name || '')) {
        throw erro('tipo');
      }
      var janela = abrirJanela('Enviando a foto');
      var ctl = { xhr: null, cancelado: false };
      janela.texto('Preparando a foto…');
      janela.barra(0);
      janela.botoes([{ rotulo: 'Cancelar', acao: function () { ctl.cancelado = true; if (ctl.xhr) { ctl.xhr.abort(); } } }]);

      var maximo = op.maximo === 800 ? 800 : 1600;
      var preparar = reduzirFoto(file).then(function (r) { return r.blob; }, function () {
        // O navegador não abriu a foto (HEIC no Chrome, por exemplo). HEIC cru o servidor também recusa.
        if (ehHeic(file)) { throw erro('heic'); }
        return file;
      });

      return preparar.then(function (blob) {
        if (ctl.cancelado) { throw erro('cancelado'); }
        janela.texto('Enviando a foto…');
        return chamar(op, { acao: 'foto', maximo: String(maximo), arquivo: { blob: blob, nome: 'foto.jpg' } }, ctl, function (env, tot) {
          var p = tot ? Math.round(env * 100 / tot) : 0;
          janela.barra(p);
          notificar(op, { etapa: 'foto', enviado: env, total: tot, percentual: p, texto: 'Enviando a foto…' });
        });
      }).then(function (j) {
        janela.fechar();
        return { arquivo: j.arquivo, largura: j.largura, altura: j.altura };
      }, function (e) {
        janela.fechar();
        throw e;
      });
    });
  }

  function escolherVideo() {
    return Promise.reject(erro('gravacao'));
  }

  window.PainelMidia = {
    escolherFoto: escolherFoto,
    escolherVideo: escolherVideo,
    mensagemErro: mensagemErro
  };
})();
