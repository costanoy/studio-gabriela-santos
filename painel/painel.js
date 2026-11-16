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
    sujo: false     // há mudanças não salvas
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
  // Início do painel
  // ---------------------------------------------------------------------------
  carregar().then(function (ok) { if (ok) desenhar(); });
})();
