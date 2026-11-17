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
    video_formato: 'Este vídeo não está num formato que o site aceita. Envie um vídeo gravado pela câmera do celular.',
    video_codec: 'Este vídeo está num formato que o site não toca. Grave de novo com a câmera do celular, em Ajustes → Câmera → Formatos → Mais compatível.',
    video_ilegivel: 'Não consegui ler este vídeo. Ele pode estar estragado. Escolha outro ou grave de novo.',
    rede: 'A internet caiu no meio do envio. Nada foi mudado no site. Toque em "Tentar de novo".',
    cancelado: 'Envio cancelado. Nada foi mudado no site.',
    poster_falhou: 'Não consegui tirar a capa deste vídeo, então ele não foi enviado. Escolha outro vídeo ou grave de novo pela câmera do celular, com a opção Mais compatível.',
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

  /* ------------------------------------------------------------------ vídeo */

  var LADO_CAPA = 1280;
  var PRETO_LUMINANCIA = 6;       // media abaixo disto (0 a 255) numa amostra de 16x16 = quadro praticamente preto
  var ESPERA_QUADRO_MS = 1500;    // seguranca: se o navegador nao avisar que o quadro chegou, desenha mesmo assim

  /** Vai para o quadro 0 (0,001 s) e avisa quando ele esta pronto para desenhar. No iPhone, desenhar direto no loadeddata sai preto. */
  function irAoQuadroZero(v, depois) {
    var acabou = false;
    var t = null;
    var pronto = function () {
      if (acabou) { return; }
      acabou = true;
      clearTimeout(t);
      v.removeEventListener('seeked', aoBuscar);
      depois();
    };
    var aoBuscar = function () {
      v.removeEventListener('seeked', aoBuscar);
      if (typeof v.requestVideoFrameCallback === 'function') {
        try { v.requestVideoFrameCallback(function () { pronto(); }); } catch (e) { pronto(); }
      } else {
        pronto();
      }
    };
    t = setTimeout(pronto, ESPERA_QUADRO_MS);
    v.addEventListener('seeked', aoBuscar);
    try { v.currentTime = 0.001; } catch (e) { pronto(); }
  }

  /** true se o quadro desenhado e praticamente todo preto (media de luminancia muito baixa numa amostra pequena). */
  function quadroPreto(canvas) {
    try {
      var p = document.createElement('canvas');
      p.width = 16;
      p.height = 16;
      var x = p.getContext('2d');
      x.drawImage(canvas, 0, 0, 16, 16);
      var d = x.getImageData(0, 0, 16, 16).data;
      var soma = 0;
      for (var i = 0; i < d.length; i += 4) { soma += 0.2126 * d[i] + 0.7152 * d[i + 1] + 0.0722 * d[i + 2]; }
      return soma / (d.length / 4) < PRETO_LUMINANCIA;
    } catch (e) { return false; }
  }

  /**
   * Capa do video = o PRIMEIRO quadro (tempo 0). Isso evita a tela preta do iPhone antes do play.
   * Depois do loadeddata busca 0,001 s, espera o seeked (e o quadro, quando o navegador avisa) e so entao desenha.
   * Se saiu preto, tenta mais uma vez depois de um play/pause mudo. Se mesmo assim sair preto, o video e escuro de verdade.
   * Devolve {blob, largura, altura, duracao}. Se o navegador nao consegue decodificar o video, rejeita.
   */
  function capaDoVideo(file) {
    return new Promise(function (resolve, reject) {
      var url = URL.createObjectURL(file);
      var v = document.createElement('video');
      var acabou = false;
      var limite = null;
      var fim = function (fn, valor) {
        if (acabou) { return; }
        acabou = true;
        clearTimeout(limite);
        v.removeAttribute('src');
        try { v.load(); } catch (e) { /* sem problema */ }
        URL.revokeObjectURL(url);
        fn(valor);
      };
      limite = setTimeout(function () { fim(reject, new Error('tempo')); }, 20000);
      v.muted = true;
      v.defaultMuted = true;
      v.playsInline = true;
      v.setAttribute('playsinline', '');
      v.preload = 'auto';
      v.addEventListener('error', function () { fim(reject, new Error('decodificar')); });

      var desenhar = function () {
        var w = v.videoWidth, h = v.videoHeight;
        if (!w || !h) { throw new Error('vazio'); }
        var esc = Math.min(1, LADO_CAPA / Math.max(w, h));
        var c = document.createElement('canvas');
        c.width = Math.max(1, Math.round(w * esc));
        c.height = Math.max(1, Math.round(h * esc));
        c.getContext('2d').drawImage(v, 0, 0, c.width, c.height);
        return c;
      };
      var entregar = function (c) {
        var w = v.videoWidth, h = v.videoHeight;
        var duracao = isFinite(v.duration) ? v.duration : 0;
        c.toBlob(function (b) {
          if (b) { fim(resolve, { blob: b, largura: w, altura: h, duracao: duracao }); } else { fim(reject, new Error('canvas')); }
        }, 'image/jpeg', 0.88);
      };

      v.addEventListener('loadeddata', function () {
        var tentouDeNovo = false;
        var tentar = function () {
          irAoQuadroZero(v, function () {
            try {
              var c = desenhar();
              if (!tentouDeNovo && quadroPreto(c)) {
                tentouDeNovo = true;
                // play/pause mudo faz o iOS decodificar um quadro de verdade; depois volta ao quadro 0
                var voltar = function () {
                  try { v.pause(); } catch (e) { /* ok */ }
                  tentar();
                };
                var p = null;
                try { p = v.play(); } catch (e) { p = null; }
                if (p && typeof p.then === 'function') { p.then(voltar, voltar); } else { setTimeout(voltar, 150); }
                return;
              }
              entregar(c);
            } catch (e) { fim(reject, e); }
          });
        };
        tentar();
      });
      v.src = url;
    });
  }

  /* ------------------------------------------------------------------ olhar o MP4 antes de enviar */

  var CODECS_HEVC = ['hvc1', 'hev1', 'dvh1', 'dvhe'];
  var CODECS_VIDEO = ['avc1', 'avc3', 'hvc1', 'hev1', 'dvh1', 'dvhe', 'av01', 'vp09', 'mp4v', 'apcn', 'apch'];
  var CONTAINERS_MP4 = ['moov', 'trak', 'mdia', 'minf', 'stbl'];

  function lerBytes(blob) {
    return new Promise(function (resolve, reject) {
      var r = new FileReader();
      r.onload = function () { resolve(new Uint8Array(r.result)); };
      r.onerror = function () { reject(r.error); };
      r.readAsArrayBuffer(blob);
    });
  }

  function texto4(b, i) { return String.fromCharCode(b[i], b[i + 1], b[i + 2], b[i + 3]); }
  function u32(b, i) { return ((b[i] << 24) | (b[i + 1] << 16) | (b[i + 2] << 8) | b[i + 3]) >>> 0; }

  /** Anda pelas caixas de um trecho do MP4. cb(tipo, inicioDoConteudo, fim). */
  function caixas(b, ini, fim, cb) {
    var p = ini;
    var n = 0;
    while (p + 8 <= fim && n++ < 500) {
      var t = u32(b, p);
      var tipo = texto4(b, p + 4);
      var cab = 8;
      if (t === 1 && p + 16 <= fim) { t = u32(b, p + 12); cab = 16; } else if (t === 0) { t = fim - p; }
      if (t < cab || p + t > fim) { return; }
      cb(tipo, p + cab, p + t);
      p += t;
    }
  }

  /** Le o trecho moov e devolve {codec, largura, altura} da faixa de video, ou null. */
  function infoDoMoov(b) {
    var achado = null;
    var faixa = function (ini, fim) {
      var f = { w: 0, h: 0, codec: '' };
      var visitar = function (i, e) {
        caixas(b, i, e, function (tipo, a, z) {
          if (tipo === 'tkhd') {
            var off = b[a] === 1 ? 88 : 76;
            if (a + off + 8 <= z) { f.w = u32(b, a + off) / 65536; f.h = u32(b, a + off + 4) / 65536; }
          } else if (tipo === 'stsd') {
            if (a + 16 <= z) { f.codec = texto4(b, a + 12); }
          } else if (CONTAINERS_MP4.indexOf(tipo) >= 0) {
            visitar(a, z);
          }
        });
      };
      visitar(ini, fim);
      if (CODECS_VIDEO.indexOf(f.codec) >= 0 && !achado) {
        achado = { codec: f.codec, largura: Math.round(f.w), altura: Math.round(f.h) };
      }
    };
    caixas(b, 0, b.length, function (tipo, a, z) {
      if (tipo === 'moov') {
        caixas(b, a, z, function (t2, a2, z2) { if (t2 === 'trak') { faixa(a2, z2); } });
      }
    });
    return achado;
  }

  /**
   * Espia o MP4 sem decodificar: codec e tamanho da faixa de video. Nunca rejeita:
   * se nao deu para ler (arquivo diferente, moov muito grande), devolve null e o servidor decide.
   */
  function olharMp4(file) {
    return new Promise(function (resolve) {
      var pos = 0;
      var voltas = 0;
      var proxima = function () {
        if (pos + 16 > file.size || voltas++ > 100) { resolve(null); return; }
        lerBytes(file.slice(pos, pos + 16)).then(function (c) {
          var t = u32(c, 0);
          var tipo = texto4(c, 4);
          if (t === 1) { t = u32(c, 12); } else if (t === 0) { t = file.size - pos; }
          if (pos === 0 && tipo !== 'ftyp') { resolve(null); return; }
          if (t < 8) { resolve(null); return; }
          if (tipo !== 'moov') { pos += t; proxima(); return; }
          if (t > 32 * 1048576 || pos + t > file.size) { resolve(null); return; }
          lerBytes(file.slice(pos, pos + t)).then(function (b) {
            var r = null;
            try { r = infoDoMoov(b); } catch (e) { r = null; }
            resolve(r);
          }, function () { resolve(null); });
        }, function () { resolve(null); });
      };
      proxima();
    });
  }

  /** Mesma regra do servidor: lado curto ate 1088 e lado longo ate 1920 (Full HD, em pe ou deitado). */
  function grandeDemais(l, a) {
    return Math.min(l, a) > 1088 || Math.max(l, a) > 1920;
  }

  function minutos(seg) {
    if (!seg || !isFinite(seg)) { return ''; }
    var s = Math.round(seg);
    return Math.floor(s / 60) + ':' + ('0' + (s % 60)).slice(-2);
  }

  function chaveRetomada(f) { return 'pm-envio:' + [f.name, f.size, f.lastModified].join('|'); }
  function lembrar(f, id) {
    try { if (id) { localStorage.setItem(chaveRetomada(f), id); } else { localStorage.removeItem(chaveRetomada(f)); } } catch (e) { /* sem armazenamento */ }
  }
  function lembrado(f) {
    try { return localStorage.getItem(chaveRetomada(f)) || ''; } catch (e) { return ''; }
  }

  /**
   * Manda o vídeo em partes. `est` guarda {envio, parte, total, recebidas}. Cada chamada continua de onde parou,
   * então "Tentar de novo" depois de uma queda só manda o que falta.
   */
  function enviarPartes(file, op, est, ctl, mostrar) {
    var feitas = {};
    (est.recebidas || []).forEach(function (i) { feitas[i] = true; });
    var bytesFeitos = 0;
    Object.keys(feitas).forEach(function (i) { bytesFeitos += Math.min(est.parte, file.size - i * est.parte); });
    var i = 0;
    function proxima() {
      while (i < est.total && feitas[i]) { i++; }
      if (i >= est.total) { return Promise.resolve(); }
      if (ctl.cancelado) { return Promise.reject(erro('cancelado')); }
      var idx = i;
      var ini = idx * est.parte;
      var pedaco = file.slice(ini, Math.min(file.size, ini + est.parte));
      return chamar(op, { acao: 'video_parte', envio: est.envio, indice: String(idx), arquivo: { blob: pedaco, nome: 'parte.bin' } }, ctl, function (env) {
        mostrar(bytesFeitos + env, file.size);
      }).then(function () {
        feitas[idx] = true;
        bytesFeitos += pedaco.size;
        mostrar(bytesFeitos, file.size);
        return proxima();
      });
    }
    return proxima();
  }

  function escolherVideo(op) {
    op = op || {};
    return pedirArquivo('video/mp4,video/quicktime,video/*', op).then(function (file) {
      if (!/^video\//i.test(file.type || '') && !/\.(mp4|mov|m4v)$/i.test(file.name || '')) { throw erro('tipo'); }
      if (file.size > LIMITE_VIDEO_MB * 1048576) { throw erro('video_pesado', { tamanho_mb: mb(file.size), limite_mb: LIMITE_VIDEO_MB }); }

      // Olha o MP4 antes de abrir a janela e antes de gastar internet: HEVC e acima de 1080p são recusados aqui.
      return olharMp4(file).then(function (info) {
        if (info && CODECS_HEVC.indexOf(info.codec) >= 0) { throw erro('hevc'); }
        if (info && grandeDemais(info.largura, info.altura)) { throw erro('grande_demais', { largura: info.largura, altura: info.altura }); }
        return enviarVideo(file, op);
      });
    });
  }

  function enviarVideo(file, op) {
      var janela = abrirJanela('Seu vídeo');
      var ctl = { xhr: null, cancelado: false };
      var envio = null;
      var sair = function (e) { janela.fechar(); throw e; };
      var cancelarTudo = function () {
        ctl.cancelado = true;
        if (ctl.xhr) { ctl.xhr.abort(); }
      };
      janela.texto('Abrindo o vídeo…');
      janela.botoes([{ rotulo: 'Cancelar', acao: cancelarTudo }]);

      return capaDoVideo(file).then(function (c) { return c; }, function () { throw erro('poster_falhou'); }).then(function (capa) {
        if (ctl.cancelado) { throw erro('cancelado'); }
        if (grandeDemais(capa.largura, capa.altura)) { throw erro('grande_demais', { largura: capa.largura, altura: capa.altura }); }
        // 1. Prévia: capa, duração e tamanho. A pessoa confirma antes de gastar internet.
        var previa = capa ? URL.createObjectURL(capa.blob) : '';
        janela.capa(previa);
        var partes = [mb(file.size) + ' MB'];
        if (capa && capa.duracao) { partes.unshift(minutos(capa.duracao)); }
        janela.texto(partes.join(' · '));
        var confirmar = op.semConfirmar ? Promise.resolve() : new Promise(function (ok, no) {
          janela.botoes([
            { rotulo: 'Enviar este vídeo', principal: true, acao: function () { ok(); } },
            { rotulo: 'Cancelar', acao: function () { no(erro('cancelado')); } }
          ]);
        });
        return confirmar.then(function () { return capa; });
      }).then(function (capa) {
        // 2. Envio em partes, com "Tentar de novo" que continua de onde parou
        var texto = 'Enviando o vídeo…';
        var mostrar = function (env, tot) {
          var p = tot ? Math.floor(env * 100 / tot) : 0;
          janela.barra(p);
          janela.texto(texto + ' ' + p + '%');
          notificar(op, { etapa: 'video', enviado: env, total: tot, percentual: p, texto: texto });
        };
        janela.erro('');
        janela.barra(0);
        janela.botoes([{ rotulo: 'Cancelar', acao: cancelarTudo }]);

        function comecar() {
          var salvo = lembrado(file);
          var tentarSalvo = salvo ? chamar(op, { acao: 'video_estado', envio: salvo }, ctl).then(function (j) { return j; }, function (e) {
            if (e.codigo === 'rede' || e.codigo === 'cancelado') { throw e; }
            lembrar(file, '');
            return null;
          }) : Promise.resolve(null);
          return tentarSalvo.then(function (j) {
            if (j) { return j; }
            return chamar(op, { acao: 'video_iniciar', tamanho: String(file.size), nome: file.name || 'video.mp4' }, ctl).then(function (r) {
              lembrar(file, r.envio);
              return { envio: r.envio, parte: r.parte, total: r.total, recebidas: [] };
            });
          });
        }

        function tentar() {
          janela.erro('');
          janela.botoes([{ rotulo: 'Cancelar', acao: cancelarTudo }]);
          return comecar().then(function (est) {
            envio = est.envio;
            return enviarPartes(file, op, est, ctl, mostrar);
          }).then(function () {
            texto = 'Finalizando o vídeo…';
            janela.barra(null);
            janela.texto(texto);
            return chamar(op, { acao: 'video_concluir', envio: envio }, ctl);
          }).catch(function (e) {
            if (e.codigo === 'cancelado' || ctl.cancelado) { throw erro('cancelado'); }
            var recuperavel = e.codigo === 'rede' || e.codigo === 'parte_invalida' || e.codigo === 'partes_faltando';
            if (!recuperavel) { throw e; }
            // Fica na tela esperando a pessoa tocar em "Tentar de novo"
            return new Promise(function (ok, no) {
              janela.erro(e.message);
              janela.botoes([
                { rotulo: 'Tentar de novo', principal: true, acao: function () { tentar().then(ok, no); } },
                { rotulo: 'Cancelar', acao: function () { no(erro('cancelado')); } }
              ]);
            });
          });
        }

        return tentar().then(function (j) {
          lembrar(file, '');
          // 3. Capa (primeiro quadro)
          janela.texto('Guardando a capa…');
          // Se a capa não for guardada, o vídeo já montado no servidor ficaria sobrando: apaga.
          var descartar = function (e) {
            var erroFinal = e.codigo === 'rede' ? erro('poster_falhou') : e;
            return chamar(op, { acao: 'video_descartar', arquivo: j.arquivo }, null).then(function () { throw erroFinal; }, function () { throw erroFinal; });
          };
          return chamar(op, { acao: 'poster', arquivo: { blob: capa.blob, nome: 'capa.jpg' } }, ctl).then(function (p) {
            return { arquivo: j.arquivo, poster: p.arquivo, largura: j.largura, altura: j.altura, duracao: j.duracao };
          }, descartar);
        });
      }).then(function (r) {
        janela.fechar();
        return r;
      }, function (e) {
        if (envio && (e.codigo === 'cancelado' || e.codigo === 'rede')) {
          // Cancelar apaga as partes; queda de rede deixa para retomar depois
          if (e.codigo === 'cancelado') {
            lembrar(file, '');
            try { chamar(op, { acao: 'video_cancelar', envio: envio }, null).catch(function () { /* melhor esforço */ }); } catch (x) { /* ok */ }
          }
        }
        return sair(e);
      });
  }

  window.PainelMidia = {
    escolherFoto: escolherFoto,
    escolherVideo: escolherVideo,
    mensagemErro: mensagemErro
  };
})();
