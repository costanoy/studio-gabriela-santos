/* Painel do Studio Gabriela Santos.
 * Regras deste arquivo:
 *  - o DOM é montado só com createElement + textContent (nunca HTML em texto), então nada vindo dos dados vira código;
 *  - nenhum estilo escrito em atributo (a CSP do painel bloqueia); estilo dinâmico só por el.style.x = ...;
 *  - textos para a dona em português simples, sem códigos técnicos.
 */
(function () {
  'use strict';

  // ---------------------------------------------------------------------------
  // Ajudantes de DOM
  // ---------------------------------------------------------------------------

  /** h('div', {class:'x', text:'oi', on:{click:fn}, attrs:{role:'alert'}}, filho1, 'texto', ...) */
  function h(tag, props) {
    var el = document.createElement(tag);
    props = props || {};
    Object.keys(props).forEach(function (k) {
      var v = props[k];
      if (v === undefined || v === null || v === false) return;
      if (k === 'class') el.className = v;
      else if (k === 'text') el.textContent = v;
      else if (k === 'on') Object.keys(v).forEach(function (ev) { el.addEventListener(ev, v[ev]); });
      else if (k === 'attrs') Object.keys(v).forEach(function (a) { if (v[a] !== null && v[a] !== undefined) el.setAttribute(a, String(v[a])); });
      else if (k === 'css') Object.keys(v).forEach(function (p) { el.style[p] = v[p]; });
      else el[k] = v;
    });
    for (var i = 2; i < arguments.length; i++) anexar(el, arguments[i]);
    return el;
  }
  function anexar(el, f) {
    if (f === null || f === undefined || f === false) return;
    if (Array.isArray(f)) { f.forEach(function (x) { anexar(el, x); }); return; }
    el.appendChild(typeof f === 'string' || typeof f === 'number' ? document.createTextNode(String(f)) : f);
  }
  function limpar(el) { while (el.firstChild) el.removeChild(el.firstChild); }

  var app = document.getElementById('app');

  // ---------------------------------------------------------------------------
  // Página de entrada (login): só um enfeite para mostrar/esconder a senha
  // ---------------------------------------------------------------------------
  var verSenha = document.getElementById('ver-senha');
  if (verSenha) {
    verSenha.addEventListener('click', function () {
      var campo = document.getElementById('senha');
      var mostrar = campo.type === 'password';
      campo.type = mostrar ? 'text' : 'password';
      verSenha.textContent = mostrar ? 'Esconder senha' : 'Mostrar senha';
      verSenha.setAttribute('aria-pressed', mostrar ? 'true' : 'false');
    });
  }
  if (!app) return; // fim do que a página de login precisa

  // ---------------------------------------------------------------------------
  // Estado
  // ---------------------------------------------------------------------------
  var S = {
    c: null,        // conteúdo do site (como veio do servidor, mais o que ela mudou)
    rev: 0,         // revisão em que a tela abriu (usada para detectar "outra tela")
    tela: 'home',
    arg: null,
    sujo: false,    // há mudanças não salvas
    resumo: []      // o que mudou (vai para o histórico)
  };

  // ---------------------------------------------------------------------------
  // Frases para a dona (P4): nunca mostrar código técnico
  // ---------------------------------------------------------------------------
  var MSG = {
    rede: 'A internet caiu. Nada foi mudado no site. Confira o sinal e tente de novo.',
    servidor: 'Não consegui salvar. O site continua como estava. Tente de novo.',
    sessao: 'Por segurança, entre de novo. Suas mudanças foram guardadas.',
    conflito: 'O site foi alterado em outra tela. Carregue a versão nova antes de continuar.',
    invalido: 'Alguns campos precisam de ajuste. Confira os avisos em vermelho.',
    pedido: 'Não consegui entender o pedido. Atualize a página e tente de novo.',
    nao_encontrada: 'Essa versão não existe mais.',
    nao_restaurou: 'Não foi possível trazer de volta. Já existe um arquivo com esse nome.',
    sem_midia: 'Envio de fotos indisponível no momento'
  };
  function mensagemErro(r) {
    if (!r) return MSG.servidor;
    if (r.erro === 'rede') return MSG.rede;
    if (r.status === 401 || r.status === 403) return MSG.sessao;
    return MSG[r.erro] || MSG.servidor;
  }

  // ---------------------------------------------------------------------------
  // Conversa com o servidor
  // ---------------------------------------------------------------------------
  function csrf() {
    var m = document.querySelector('meta[name="csrf"]');
    return m ? m.getAttribute('content') : '';
  }

  /** Devolve sempre uma promessa que resolve com {ok, status, ...}. Nunca rejeita. */
  function api(acao, dados) {
    var corpo = { acao: acao };
    Object.keys(dados || {}).forEach(function (k) { corpo[k] = dados[k]; });
    return fetch('api.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-CSRF': csrf() },
      body: JSON.stringify(corpo)
    }).then(function (r) {
      return r.json().catch(function () { return { ok: false, erro: 'servidor' }; }).then(function (j) {
        j.status = r.status;
        if (r.status === 401 || (r.status === 403 && j.erro === 'csrf')) j.sessao = false;
        return j;
      });
    }).catch(function () { return { ok: false, erro: 'rede', status: 0 }; });
  }

  // ---------------------------------------------------------------------------
  // Avisos na tela
  // ---------------------------------------------------------------------------
  function alerta(texto, tipo, extra) {
    return h('div', { class: 'alerta' + (tipo ? ' ' + tipo : ''), attrs: { role: 'alert' } },
      h('span', { class: 'alerta-icone', text: tipo === 'ok' ? '✓' : '!', attrs: { 'aria-hidden': 'true' } }),
      h('div', { class: 'alerta-corpo' }, h('span', { text: texto }), extra || null));
  }

  var toastAtual = null;
  var toastTempo = null;
  function fecharToast() {
    if (toastTempo) { clearTimeout(toastTempo); toastTempo = null; }
    if (toastAtual && toastAtual.parentNode) toastAtual.parentNode.removeChild(toastAtual);
    toastAtual = null;
  }
  /** Aviso no rodapé. acao = {texto, fazer} mostra um botão (ex.: Desfazer). */
  function toast(texto, acao, segundos) {
    fecharToast();
    var botao = acao ? h('button', { class: 'botao', type: 'button', text: acao.texto, on: { click: function () { fecharToast(); acao.fazer(); } } }) : null;
    toastAtual = h('div', { class: 'toast', attrs: { role: 'status' } }, h('span', { text: texto }), botao);
    document.body.appendChild(toastAtual);
    toastTempo = setTimeout(fecharToast, (segundos || 6) * 1000);
  }

  // ---------------------------------------------------------------------------
  // Telas
  // ---------------------------------------------------------------------------
  var TELAS = {};

  function ir(tela, arg) {
    fecharToast();
    S.tela = tela;
    S.arg = arg === undefined ? null : arg;
    desenhar();
    window.scrollTo(0, 0);
  }

  function topo(titulo, voltar) {
    return h('header', { class: 'topo' },
      voltar ? h('button', { class: 'botao', type: 'button', text: '← Voltar', on: { click: function () { ir(voltar); } } }) : null,
      h('h1', { text: titulo }));
  }

  function desenhar() {
    limpar(app);
    var f = TELAS[S.tela] || TELAS.home;
    f(app);
  }

  function carregar() {
    return api('ler').then(function (r) {
      if (!r.ok) {
        limpar(app);
        app.appendChild(alerta(mensagemErro(r), null,
          h('button', { class: 'botao', type: 'button', text: 'Tentar de novo', on: { click: function () { location.reload(); } } })));
        return false;
      }
      S.c = r.conteudo;
      S.rev = r.revisao;
      return true;
    });
  }

  // --- Início ---
  var CARTOES = [
    /*CARTOES*/
  ];

  TELAS.home = function (raiz) {
    raiz.appendChild(h('header', { class: 'topo' },
      h('h1', { text: 'Painel do site' }),
      h('button', { class: 'botao', type: 'button', id: 'sair', text: 'Sair', on: { click: sair } })));
    raiz.appendChild(h('p', { class: 'dica', text: 'O que você quer fazer?' }));
    var grade = h('div', { class: 'cartoes' });
    CARTOES.forEach(function (c) {
      var el = c.href
        ? h('a', { class: 'cartao', href: c.href, target: c.alvo || null, rel: c.alvo ? 'noopener' : null })
        : h('button', { class: 'cartao', type: 'button', on: { click: c.ir } });
      el.id = c.id;
      el.appendChild(h('span', { class: 'cartao-icone', text: c.icone, attrs: { 'aria-hidden': 'true' } }));
      el.appendChild(h('span', { class: 'cartao-titulo', text: c.titulo }));
      el.appendChild(h('span', { class: 'cartao-texto', text: c.texto }));
      grade.appendChild(el);
    });
    raiz.appendChild(grade);
  };

  function sair() {
    api('sair').then(function () { location.href = './'; });
  }

  CARTOES.push({ id: 'cartao-site', icone: '🌐', titulo: 'Ver o site', texto: 'Abre o site como os clientes veem', href: '../', alvo: '_blank' });

  // ---------------------------------------------------------------------------
  // Dados: ler e escrever por caminho ("home.hero.titulo", "resultados.itens.2.titulo")
  // ---------------------------------------------------------------------------
  function obter(raiz, caminho) {
    var o = raiz;
    var p = caminho.split('.');
    for (var i = 0; i < p.length; i++) {
      if (o === undefined || o === null) return undefined;
      o = o[p[i]];
    }
    return o;
  }
  function definir(raiz, caminho, v) {
    var p = caminho.split('.');
    var o = raiz;
    for (var i = 0; i < p.length - 1; i++) o = o[p[i]];
    o[p[p.length - 1]] = v;
  }
  /** Conta letras como o servidor (emoji conta 1). */
  function contar(s) { return Array.from(s).length; }
  function cortar(s, max) {
    var a = Array.from(s);
    return a.length > max ? a.slice(0, max).join('') : s;
  }
  function copia(o) { return JSON.parse(JSON.stringify(o)); }
  function codigoNovo(prefixo) {
    var a = new Uint8Array(4);
    (window.crypto || window.msCrypto).getRandomValues(a);
    return prefixo + '-' + Array.prototype.map.call(a, function (b) { return ('0' + b.toString(16)).slice(-2); }).join('');
  }

  // ---------------------------------------------------------------------------
  // Mudanças não salvas e salvar
  // ---------------------------------------------------------------------------
  var barraSalvarEl = null;
  function atualizarBarra() {
    if (barraSalvarEl) barraSalvarEl.disabled = !S.sujo;
  }
  /** Chame sempre que ela mudar algo. $rotulo entra no histórico ("Mudou os textos"). */
  function mudou(rotulo) {
    S.sujo = true;
    if (rotulo && S.resumo.indexOf(rotulo) < 0) S.resumo.push(rotulo);
    atualizarBarra();
  }

  function limparErrosCampos() {
    Array.prototype.forEach.call(document.querySelectorAll('.msg-campo'), function (el) { el.parentNode.removeChild(el); });
    Array.prototype.forEach.call(document.querySelectorAll('.campo-erro'), function (el) { el.classList.remove('campo-erro'); });
  }
  function erroNoCampo(caminho, texto) {
    var alvo = document.querySelector('[data-caminho="' + caminho + '"]');
    if (!alvo) return false;
    var entrada = alvo.querySelector('input, textarea, select');
    if (entrada) entrada.classList.add('campo-erro');
    alvo.appendChild(h('p', { class: 'msg-campo', attrs: { role: 'alert' } },
      h('span', { text: '⚠', attrs: { 'aria-hidden': 'true' } }), h('span', { text: texto })));
    var det = alvo.closest('details');
    if (det) det.open = true;
    return true;
  }
  function mostrarErros(detalhes) {
    limparErrosCampos();
    var soltos = [];
    var primeiro = null;
    (detalhes || []).forEach(function (d) {
      if (erroNoCampo(d.campo, d.mensagem)) {
        if (!primeiro) primeiro = document.querySelector('[data-caminho="' + d.campo + '"]');
      } else {
        soltos.push(d.mensagem);
      }
    });
    if (primeiro) primeiro.scrollIntoView({ block: 'center' });
    return soltos;
  }

  /** Confere o que dá para conferir no aparelho. Devolve a lista {campo, mensagem}. */
  function validarLocal() {
    var erros = [];
    Array.prototype.forEach.call(document.querySelectorAll('[data-obrigatorio="1"]'), function (w) {
      var v = obter(S.c, w.getAttribute('data-caminho'));
      if (typeof v !== 'string' || v.trim() === '') erros.push({ campo: w.getAttribute('data-caminho'), mensagem: 'Este campo não pode ficar vazio.' });
    });
    var zap = obter(S.c, 'contato.whatsapp');
    if (typeof zap === 'string' && !/^[0-9]{10,15}$/.test(zap)) {
      erros.push({ campo: 'contato.whatsapp', mensagem: 'Digite só números, com 55 + DDD + telefone. Exemplo: 5562984141116.' });
    }
    return erros;
  }

  var salvando = false;
  /** Grava no servidor. Resolve com a resposta. Trata erros e mostra o aviso "Salvo no site". */
  function salvar() {
    if (salvando) return Promise.resolve({ ok: false });
    var locais = validarLocal();
    if (locais.length) {
      mostrarErros(locais);
      avisoTopo(MSG.invalido);
      return Promise.resolve({ ok: false, erro: 'invalido' });
    }
    salvando = true;
    var resumo = S.resumo.length ? S.resumo.join('; ') : 'Salvou mudanças';
    return api('salvar', { conteudo: S.c, revisao: S.rev, resumo: resumo }).then(function (r) {
      salvando = false;
      if (r.ok) {
        limparErrosCampos();
        S.c = r.conteudo;
        S.rev = r.revisao;
        S.sujo = false;
        S.resumo = [];
        atualizarBarra();
        avisoTopo(null);
        toast('Salvo no site.', null, 6);
        return r;
      }
      if (r.status === 422) {
        var soltos = mostrarErros(r.detalhes);
        avisoTopo(soltos.length ? soltos.join(' ') : MSG.invalido);
        return r;
      }
      avisoTopo(mensagemErro(r));
      return r;
    });
  }

  /** Faixa de aviso fixa no alto da tela de edição. null apaga. */
  function avisoTopo(texto) {
    var velho = document.getElementById('aviso-topo');
    if (velho) velho.parentNode.removeChild(velho);
    if (!texto) return;
    var pai = document.getElementById('conteudo-tela') || app;
    var el = alerta(texto);
    el.id = 'aviso-topo';
    pai.insertBefore(el, pai.firstChild);
    el.scrollIntoView({ block: 'nearest' });
  }

  function barraAcao(texto) {
    var b = h('button', { class: 'botao botao-principal', type: 'button', id: 'salvar', text: texto || 'Salvar no site', on: { click: function () { salvar(); } } });
    barraSalvarEl = b;
    b.disabled = !S.sujo;
    return h('div', { class: 'barra-acao' }, b);
  }

  // ---------------------------------------------------------------------------
  // Textos do site (P6): rótulo simples, dica, limite com contador
  // ---------------------------------------------------------------------------
  var seq = 0;

  function campoWrap(caminho, rotulo, dica, id, obrigatorio) {
    return h('div', { class: 'campo', attrs: { 'data-caminho': caminho, 'data-obrigatorio': obrigatorio ? '1' : null } },
      h('label', { text: rotulo, attrs: { for: id } }),
      dica ? h('p', { class: 'dica', id: id + '-dica', text: dica }) : null);
  }

  function campoTexto(caminho, s) {
    var id = 'f' + (++seq);
    var area = s.tipo === 'area';
    var wrap = campoWrap(caminho, s.rotulo, s.dica, id, !s.opcional);
    var entrada = h(area ? 'textarea' : 'input', { id: id, attrs: { rows: area ? (s.linhas || 4) : null, autocomplete: 'off', inputmode: s.digitos ? 'numeric' : null, 'aria-describedby': s.dica ? id + '-dica' : null } });
    if (!area) entrada.type = 'text';
    entrada.value = String(obter(S.c, caminho) || '');
    var cont = h('p', { class: 'contador' });
    function pintar() {
      var n = contar(entrada.value);
      cont.textContent = n + ' de ' + s.max + (n >= s.max ? ' (limite)' : '');
      cont.className = 'contador' + (n >= s.max ? ' estourou' : n >= s.max * 0.9 ? ' perto' : '');
    }
    entrada.addEventListener('input', function () {
      var v = entrada.value;
      if (s.digitos) v = v.replace(/\D/g, '');
      if (!area) v = v.replace(/[\r\n]+/g, ' ');
      v = cortar(v, s.max);
      if (v !== entrada.value) entrada.value = v;
      definir(S.c, caminho, v);
      pintar();
      entrada.classList.remove('campo-erro');
      mudou('Mudou textos');
    });
    wrap.appendChild(entrada);
    wrap.appendChild(cont);
    pintar();
    return wrap;
  }

  /** Várias linhas (endereço): cada linha vira um item da lista. */
  function campoLinhas(caminho, s) {
    var id = 'f' + (++seq);
    var wrap = campoWrap(caminho, s.rotulo, s.dica, id, false);
    var entrada = h('textarea', { id: id, attrs: { rows: 5, autocomplete: 'off' } });
    entrada.value = (obter(S.c, caminho) || []).join('\n');
    var cont = h('p', { class: 'contador' });
    function pintar() {
      var linhas = entrada.value.split(/\r?\n/);
      cont.textContent = linhas.length + ' de ' + s.maxLinhas + ' linhas, até ' + s.max + ' letras em cada';
      cont.className = 'contador' + (linhas.length >= s.maxLinhas ? ' perto' : '');
    }
    entrada.addEventListener('input', function () {
      var linhas = entrada.value.split(/\r?\n/).slice(0, s.maxLinhas).map(function (l) { return cortar(l, s.max); });
      var v = linhas.join('\n');
      if (v !== entrada.value) entrada.value = v;
      definir(S.c, caminho, linhas.map(function (l) { return l.trim(); }).filter(Boolean));
      pintar();
      mudou('Mudou textos');
    });
    wrap.appendChild(entrada);
    wrap.appendChild(cont);
    pintar();
    return wrap;
  }

  function botaoPequeno(texto, fn, rotulo) {
    return h('button', { class: 'botao botao-leve', type: 'button', text: texto, on: { click: fn }, attrs: { 'aria-label': rotulo || null } });
  }

  /** Lista de textos soltos (parágrafos). */
  function listaTextos(caminho, s) {
    var caixa = h('div', { class: 'lista', attrs: { 'data-lista': caminho } });
    function montar() {
      limpar(caixa);
      var itens = obter(S.c, caminho);
      itens.forEach(function (_, i) {
        var linha = h('div', { class: 'item-lista' });
        linha.appendChild(campoTexto(caminho + '.' + i, { rotulo: s.nome + ' ' + (i + 1), tipo: 'area', max: s.max, linhas: 5 }));
        var bt = h('div', { class: 'linha-botoes' });
        if (i > 0) bt.appendChild(botaoPequeno('↑ Subir', function () { trocar(itens, i, i - 1); }, 'Subir ' + s.nome + ' ' + (i + 1)));
        if (i < itens.length - 1) bt.appendChild(botaoPequeno('↓ Descer', function () { trocar(itens, i, i + 1); }, 'Descer ' + s.nome + ' ' + (i + 1)));
        linha.appendChild(bt);
        caixa.appendChild(linha);
      });
      if (itens.length < s.maxItens) {
        caixa.appendChild(botaoPequeno('+ Adicionar ' + s.nome.toLowerCase(), function () {
          itens.push('Novo texto. Escreva aqui.');
          mudou('Mudou textos');
          montar();
        }));
      }
    }
    function trocar(l, a, b) {
      var t = l[a]; l[a] = l[b]; l[b] = t;
      mudou('Mudou textos');
      montar();
    }
    montar();
    return h('div', { class: 'bloco' }, s.titulo ? h('h3', { text: s.titulo }) : null, caixa);
  }

  /** Lista de itens com vários campos (procedimentos, depoimentos...). */
  function listaItens(caminho, s) {
    var caixa = h('div', { class: 'lista', attrs: { 'data-lista': caminho } });
    function montar() {
      limpar(caixa);
      var itens = obter(S.c, caminho);
      itens.forEach(function (item, i) {
        var base = caminho + '.' + i;
        var fs = h('fieldset', { class: 'item-lista' }, h('legend', { text: s.nome + ' ' + (i + 1) }));
        s.campos.forEach(function (cs) { fs.appendChild(montarCampo(cs, base)); });
        var bt = h('div', { class: 'linha-botoes' });
        if (i > 0) bt.appendChild(botaoPequeno('↑ Subir', function () { trocar(itens, i, i - 1); }, 'Subir ' + s.nome + ' ' + (i + 1)));
        if (i < itens.length - 1) bt.appendChild(botaoPequeno('↓ Descer', function () { trocar(itens, i, i + 1); }, 'Descer ' + s.nome + ' ' + (i + 1)));
        fs.appendChild(bt);
        caixa.appendChild(fs);
      });
      if (s.novo && itens.length < s.maxItens) {
        caixa.appendChild(botaoPequeno('+ Adicionar ' + s.nome.toLowerCase(), function () {
          itens.push(s.novo());
          mudou('Mudou textos');
          montar();
        }));
      }
    }
    function trocar(l, a, b) {
      var t = l[a]; l[a] = l[b]; l[b] = t;
      mudou('Mudou textos');
      montar();
    }
    montar();
    return h('div', { class: 'bloco' }, s.titulo ? h('h3', { text: s.titulo }) : null, caixa);
  }

  function montarCampo(s, base) {
    var caminho = base ? base + '.' + s.c : s.c;
    if (s.tipo === 'lista-textos') return listaTextos(caminho, s);
    if (s.tipo === 'lista-itens') return listaItens(caminho, s);
    if (s.tipo === 'linhas') return campoLinhas(caminho, s);
    return campoTexto(caminho, s);
  }

  var GRUPOS = [
    { id: 'topo', titulo: 'Topo do site', texto: 'O que aparece logo ao abrir o site', previa: 'topo', campos: [
      { c: 'home.hero.titulo', rotulo: 'Título grande do topo', dica: 'Aparece em letras grandes no começo da página inicial.', max: 80 },
      { c: 'home.hero.texto', rotulo: 'Texto do topo', dica: 'Frase logo abaixo do título grande.', max: 600, tipo: 'area' }
    ] },
    { id: 'boas-vindas', titulo: 'Boas-vindas', texto: 'Texto de apresentação', campos: [
      { c: 'home.boas_vindas.paragrafos', tipo: 'lista-textos', nome: 'Parágrafo', max: 600, maxItens: 6 }
    ] },
    { id: 'sobre', titulo: 'Sobre o studio', texto: 'Sua história e os destaques', campos: [
      { c: 'home.sobre.rotulo', rotulo: 'Palavra pequena acima do título', dica: 'Fica em letras pequenas, acima do título.', max: 40 },
      { c: 'home.sobre.titulo', rotulo: 'Título de "Sobre o studio"', max: 80 },
      { c: 'home.sobre.paragrafos', tipo: 'lista-textos', titulo: 'Textos ao lado da sua foto', nome: 'Parágrafo', max: 600, maxItens: 6 },
      { c: 'home.sobre.video_paragrafos', tipo: 'lista-textos', titulo: 'Textos ao lado do vídeo', nome: 'Parágrafo', max: 600, maxItens: 6 },
      { c: 'home.sobre.numeros', tipo: 'lista-itens', titulo: 'Destaques', nome: 'Destaque', maxItens: 4,
        novo: function () { return { id: codigoNovo('num'), valor: 'Novo destaque', legenda: 'Explique em poucas palavras' }; },
        campos: [
          { c: 'valor', rotulo: 'Destaque', max: 40 },
          { c: 'legenda', rotulo: 'Frase pequena embaixo', max: 40 }
        ] }
    ] },
    { id: 'procedimentos', titulo: 'Procedimentos', texto: 'Os tratamentos que você oferece', campos: [
      { c: 'home.procedimentos.rotulo', rotulo: 'Palavra pequena acima do título', max: 40 },
      { c: 'home.procedimentos.titulo', rotulo: 'Título da seção', max: 80 },
      { c: 'home.procedimentos.itens', tipo: 'lista-itens', titulo: 'Procedimentos', nome: 'Procedimento', maxItens: 12,
        novo: function () { return { id: codigoNovo('proc'), titulo: 'Novo procedimento', texto: 'Explique como é o procedimento.' }; },
        campos: [
          { c: 'titulo', rotulo: 'Nome do procedimento', max: 80 },
          { c: 'texto', rotulo: 'Explicação', max: 600, tipo: 'area', linhas: 5 }
        ] }
    ] },
    { id: 'cursos', titulo: 'Cursos', texto: 'Formações para profissionais', campos: [
      { c: 'home.cursos.titulo', rotulo: 'Título da seção', max: 80 },
      { c: 'home.cursos.descricao', rotulo: 'Texto de apresentação dos cursos', max: 600, tipo: 'area' },
      { c: 'home.cursos.itens', tipo: 'lista-itens', titulo: 'Cursos', nome: 'Curso', maxItens: 4,
        campos: [
          { c: 'titulo', rotulo: 'Nome do curso', max: 80 },
          { c: 'carga_horaria', rotulo: 'Carga horária', dica: 'Exemplo: Carga horária: 10 horas', max: 80 },
          { c: 'descricao', rotulo: 'Descrição do curso', max: 600, tipo: 'area', linhas: 5 },
          { c: 'mensagem_whatsapp', rotulo: 'Mensagem pronta do WhatsApp', dica: 'É o que já vem escrito quando a cliente toca em "Quero saber mais".', max: 200, tipo: 'area', linhas: 3 }
        ] }
    ] },
    { id: 'depoimentos', titulo: 'Depoimentos', texto: 'O que as clientes dizem', campos: [
      { c: 'home.depoimentos.titulo', rotulo: 'Título da seção', max: 80 },
      { c: 'home.depoimentos.itens', tipo: 'lista-itens', titulo: 'Depoimentos', nome: 'Depoimento', maxItens: 12,
        novo: function () { return { id: codigoNovo('dep'), texto: 'Escreva aqui o depoimento.', autor: 'Cliente do studio', servico: '' }; },
        campos: [
          { c: 'texto', rotulo: 'Depoimento', max: 400, tipo: 'area', linhas: 5 },
          { c: 'autor', rotulo: 'Nome de quem escreveu', max: 40 },
          { c: 'servico', rotulo: 'Procedimento feito', dica: 'Pode ficar em branco.', max: 40, opcional: true }
        ] }
    ] },
    { id: 'resultados', titulo: 'Página de resultados', texto: 'Título da página "Todos os resultados"', campos: [
      { c: 'resultados.titulo_pagina', rotulo: 'Título da página', max: 80 }
    ] },
    { id: 'contato', titulo: 'Contato e horário', texto: 'WhatsApp, endereço e horário (muda no site todo)', campos: [
      { c: 'contato.whatsapp', rotulo: 'WhatsApp (só números)', dica: 'Com 55 + DDD + telefone. Exemplo: 5562984141116.', max: 15, digitos: true },
      { c: 'contato.whatsapp_exibicao', rotulo: 'Telefone como aparece escrito', dica: 'Exemplo: (62) 98414-1116', max: 30 },
      { c: 'contato.horario', rotulo: 'Horário de atendimento', max: 80 },
      { c: 'contato.endereco_curto', rotulo: 'Endereço curto', dica: 'Aparece no rodapé e no cartão de contato.', max: 80 },
      { c: 'contato.endereco_linhas', tipo: 'linhas', rotulo: 'Endereço completo', dica: 'Uma linha de endereço por linha (aperte Enter para a próxima).', max: 100, maxLinhas: 6 },
      { c: 'contato.mensagem_padrao', rotulo: 'Mensagem pronta do WhatsApp', dica: 'Já vem escrita quando a cliente toca no botão do WhatsApp.', max: 200, tipo: 'area', linhas: 3 }
    ] }
  ];

  /** Prévia do topo, com as mesmas cores do site. */
  function previaTopo() {
    var t = h('p', { class: 'previa-titulo' });
    var x = h('p', { class: 'previa-texto' });
    function pintar() {
      t.textContent = obter(S.c, 'home.hero.titulo') || '';
      x.textContent = obter(S.c, 'home.hero.texto') || '';
    }
    pintar();
    var caixa = h('div', { class: 'previa', attrs: { 'aria-label': 'Prévia do topo do site' } }, h('span', { class: 'previa-rotulo', text: 'Como fica no site' }), t, x);
    document.addEventListener('input', function ouvir() {
      if (!document.body.contains(caixa)) { document.removeEventListener('input', ouvir); return; }
      pintar();
    });
    return caixa;
  }

  var abertos = {};
  TELAS.textos = function (raiz) {
    raiz.appendChild(topo('Textos do site', 'home'));
    var corpo = h('main', { id: 'conteudo-tela' });
    corpo.appendChild(h('p', { class: 'dica', text: 'Toque em um assunto para editar. Embaixo de cada campo aparece quantas letras cabem.' }));
    GRUPOS.forEach(function (g) {
      var det = h('details', { class: 'grupo', id: 'grupo-' + g.id });
      det.open = !!abertos[g.id];
      det.addEventListener('toggle', function () { abertos[g.id] = det.open; });
      det.appendChild(h('summary', null, h('span', { class: 'grupo-titulo', text: g.titulo }), h('span', { class: 'grupo-texto', text: g.texto })));
      var dentro = h('div', { class: 'grupo-corpo' });
      if (g.previa === 'topo') dentro.appendChild(previaTopo());
      g.campos.forEach(function (cs) { dentro.appendChild(montarCampo(cs, '')); });
      det.appendChild(dentro);
      corpo.appendChild(det);
    });
    raiz.appendChild(corpo);
    raiz.appendChild(barraAcao('Salvar no site'));
  };
  CARTOES.push({ id: 'cartao-textos', icone: '✏️', titulo: 'Textos', texto: 'Títulos, parágrafos, horário e contato', ir: function () { ir('textos'); } });

  /*@@TELAS*/

  // ---------------------------------------------------------------------------
  // Início do painel
  // ---------------------------------------------------------------------------
  carregar().then(function (ok) { if (ok) desenhar(); });
})();
