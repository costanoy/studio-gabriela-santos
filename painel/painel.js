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
  // Rascunho local: guarda o que ela mexeu se a sessão acabar (só neste aparelho)
  var CHAVE_RASC = 'painel-rascunho';
  function rascunhoLer() {
    try {
      var t = window.localStorage.getItem(CHAVE_RASC);
      var d = t ? JSON.parse(t) : null;
      return d && typeof d === 'object' && d.c && typeof d.c === 'object' ? d : null;
    } catch (e) { return null; }
  }
  function rascunhoGravar(d) {
    try { window.localStorage.setItem(CHAVE_RASC, JSON.stringify(d)); return true; } catch (e) { return false; }
  }
  function rascunhoApagar() {
    try { window.localStorage.removeItem(CHAVE_RASC); } catch (e) { /* sem armazenamento: nada a apagar */ }
  }
  var avisoRasc = document.getElementById('aviso-rascunho');
  if (avisoRasc && rascunhoLer()) avisoRasc.hidden = false;

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
    resumo: [],     // o que mudou (vai para o histórico)
    apagados: []    // itens marcados para apagar: saem do site só quando ela toca em Salvar no site
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
    metodo: 'Não consegui entender o pedido. Atualize a página e tente de novo.',
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
    var rasc = rascunhoLer();
    if (rasc) {
      var difere = rasc.rev !== S.rev;
      raiz.appendChild(h('div', { class: 'alerta aviso', id: 'oferta-rascunho', attrs: { role: 'alert' } },
        h('span', { class: 'alerta-icone', text: '!', attrs: { 'aria-hidden': 'true' } }),
        h('div', { class: 'alerta-corpo' },
          h('span', { text: 'Você tem mudanças guardadas de antes' + (rasc.quando ? ' (' + quando(rasc.quando) + ')' : '') + '. Quer continuar de onde parou?' + (difere ? ' O site foi alterado desde então: ao salvar, suas mudanças guardadas vão substituir o que está no site.' : '') }),
          h('div', { class: 'linha-botoes' },
            h('button', { class: 'botao botao-principal', type: 'button', id: 'rascunho-recuperar', text: 'Recuperar mudanças', on: { click: function () {
              var recuperar = function () {
                S.c = rasc.c;
                S.sujo = true;
                S.apagados = []; limparPilhas();
                S.resumo = ['Recuperou mudanças guardadas'];
                rascunhoApagar();
                ir(rasc.tela === 'resultados' ? 'resultados' : 'textos');
              };
              // Se o site mudou desde que o rascunho foi guardado (ex.: outra pessoa salvou), pede um sim claro
              if (!difere) { recuperar(); return; }
              confirmar({
                titulo: 'Substituir o que está no site?',
                texto: 'O site foi alterado depois que estas mudanças foram guardadas. Se você continuar e salvar, as mudanças guardadas vão tomar o lugar do que está no site agora. A versão de agora fica guardada em Versões, e você pode voltar para ela.',
                ok: 'Sim, recuperar minhas mudanças',
                cancelar: 'Não, voltar'
              }).then(function (sim) { if (sim) recuperar(); });
            } } }),
            h('button', { class: 'botao', type: 'button', id: 'rascunho-descartar', text: 'Descartar', on: { click: function () { rascunhoApagar(); desenhar(); } } })))));
    }
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
  function salvar(textoToast) {
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
        if (typeof textoToast !== 'string' && S.apagados.length) {
          textoToast = S.apagados.length === 1
            ? 'Salvo no site. "' + nomeItem(S.apagados[0].item) + '" foi apagado.'
            : 'Salvo no site. ' + S.apagados.length + ' itens foram apagados.';
        }
        var haviaApagados = S.apagados.length > 0;
        S.sujo = false;
        S.resumo = [];
        S.apagados = []; limparPilhas();
        if (haviaApagados) { var y0 = window.scrollY; desenhar(); window.scrollTo(0, y0); }
        atualizarBarra();
        avisoTopo(null);
        rascunhoApagar();
        // Desfazer: volta para a versão que estava no site um instante antes (a 0 é o conteúdo original)
        var anterior = r.revisao - 1;
        toast(typeof textoToast === 'string' ? textoToast : 'Salvo no site.', { texto: 'Desfazer', fazer: function () { restaurarVersao(anterior, 'Desfeito. O site voltou ao que estava antes.'); } }, 10);
        return r;
      }
      if (r.status === 422) {
        var soltos = mostrarErros(r.detalhes || []);
        avisoTopo(soltos.length ? soltos.join(' ') : MSG.invalido);
        return r;
      }
      if (r.status === 401 || r.sessao === false) {
        // A sessão acabou: guarda o que ela mexeu neste aparelho e pede para entrar de novo
        rascunhoGravar({ c: S.c, rev: S.rev, tela: S.tela, quando: new Date().toISOString() });
        avisoTopo(MSG.sessao, h('button', { class: 'botao', type: 'button', id: 'entrar-de-novo', text: 'Entrar de novo', on: { click: function () { location.reload(); } } }));
        return r;
      }
      if (r.status === 409) {
        avisoTopo(MSG.conflito, h('button', { class: 'botao', type: 'button', id: 'carregar-nova', text: 'Carregar a versão nova', on: { click: function () {
          carregar().then(function (ok) {
            if (!ok) return;
            S.sujo = false;
            S.resumo = [];
            S.apagados = []; limparPilhas();
            S.tela = 'home';
            S.arg = null;
            desenhar();
            toast('Pronto. Você está vendo a versão mais nova do site.');
          });
        } } }));
        return r;
      }
      var tentar = (r.erro === 'rede' || r.erro === 'servidor' || !MSG[r.erro])
        ? h('button', { class: 'botao', type: 'button', id: 'tentar-de-novo', text: 'Tentar de novo', on: { click: function () { salvar(textoToast); } } })
        : null;
      avisoTopo(mensagemErro(r), tentar);
      return r;
    });
  }

  /** Faixa de aviso fixa no alto da tela de edição. null apaga. extra = botão opcional. */
  function avisoTopo(texto, extra) {
    var velho = document.getElementById('aviso-topo');
    if (velho) velho.parentNode.removeChild(velho);
    if (!texto) return;
    var pai = document.getElementById('conteudo-tela') || app;
    var el = alerta(texto, null, extra);
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
    var wrap = campoWrap(caminho, s.rotulo, s.dica, id, !s.opcional && !s.rascunho);
    var entrada = h(area ? 'textarea' : 'input', { id: id, attrs: { rows: area ? (s.linhas || 4) : null, autocomplete: 'off', inputmode: s.digitos ? 'numeric' : null, 'aria-describedby': s.dica ? id + '-dica' : null } });
    if (!area) entrada.type = 'text';
    // s.raiz: campo de um rascunho (ainda não faz parte do conteúdo do site)
    var raiz = function () { return s.raiz ? s.raiz() : S.c; };
    entrada.value = String(obter(raiz(), caminho) || '');
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
      definir(raiz(), caminho, v);
      pintar();
      entrada.classList.remove('campo-erro');
      if (s.aoMudar) s.aoMudar(v);
      else if (!s.rascunho) mudou('Mudou textos');
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

  // ---------------------------------------------------------------------------
  // Resultados: lista, ordem e edição (P2, L09)
  // ---------------------------------------------------------------------------
  var ABAS = [
    { id: 'home', nome: 'Página inicial' },
    { id: 'resultados', nome: 'Página de resultados' },
    { id: 'fora', nome: 'Fora do site' }
  ];
  var TIPOS = { comparacao: 'Antes e depois', video: 'Vídeo', foto: 'Foto' };
  var LIMITE_HOME = 5;
  S.aba = 'home';
  S.cat = null;
  S.novo = null;

  function itensRes() { return S.c.resultados.itens; }
  function buscar(id) {
    var l = itensRes();
    for (var i = 0; i < l.length; i++) if (l[i].id === id) return l[i];
    return null;
  }
  function indiceDe(id) {
    var l = itensRes();
    for (var i = 0; i < l.length; i++) if (l[i].id === id) return i;
    return -1;
  }
  function categoriasOrd() {
    return S.c.resultados.categorias.slice().sort(function (a, b) { return (a.ordem || 0) - (b.ordem || 0); });
  }
  function nomeCategoria(id) {
    var l = S.c.resultados.categorias;
    for (var i = 0; i < l.length; i++) if (l[i].id === id) return l[i].nome;
    return id;
  }
  function nomeItem(i) { return (i.tipo === 'comparacao' ? i.titulo : i.rotulo) || 'Sem nome'; }
  function catAtual() {
    var cs = categoriasOrd();
    for (var i = 0; i < cs.length; i++) if (cs[i].id === S.cat) return S.cat;
    return cs.length ? cs[0].id : '';
  }
  function chaveOrdem() { return S.aba === 'home' ? 'ordem_home' : 'ordem'; }
  /** Itens que aparecem na aba atual, na ordem em que aparecem no site. */
  function listaVisivel() {
    var cat = catAtual();
    var l = itensRes().filter(function (i) {
      if (S.aba === 'home') return i.tipo === 'comparacao' && i.na_home;
      if (S.aba === 'resultados') return i.categoria === cat && i.em_resultados;
      return !i.na_home && !i.em_resultados;
    });
    var k = chaveOrdem();
    l.sort(function (a, b) { return (a[k] || 0) - (b[k] || 0); });
    return l;
  }
  function proximaOrdem(chave, filtro) {
    var m = 0;
    itensRes().forEach(function (i) { if (filtro(i) && (i[chave] || 0) > m) m = i[chave]; });
    return m + 1;
  }
  function mover(item, delta) {
    var l = listaVisivel();
    var i = l.indexOf(item);
    var j = i + delta;
    if (i < 0 || j < 0 || j >= l.length) return;
    l[i] = l[j];
    l[j] = item;
    var k = chaveOrdem();
    l.forEach(function (x, n) { x[k] = n + 1; });
    mudou('Mudou a ordem dos resultados');
    var y = window.scrollY;
    desenhar();
    window.scrollTo(0, y);
  }

  // --- Enquadramento (mesma conta do site) ---
  function arred(v) { return Math.round(v * 1000) / 1000; }
  function enqAuto(l, a) {
    if (!(l > 0) || !(a > 0)) return { width: 100, height: 100, left: 50, top: 50, automatico: true };
    var r = l / a, w, hh;
    if (r > 0.75) { hh = 100; w = 100 * r / 0.75; } else { w = 100; hh = 75 / r; }
    return { width: arred(w), height: arred(hh), left: 50, top: 50, automatico: true };
  }
  function enqLimitar(e) {
    var w = Math.min(400, Math.max(100, e.width));
    var hh = Math.min(400, Math.max(100, e.height));
    var fx = (w - 100) / 2, fy = (hh - 100) / 2;
    return {
      width: arred(w), height: arred(hh),
      left: arred(Math.min(50 + fx, Math.max(50 - fx, e.left))),
      top: arred(Math.min(50 + fy, Math.max(50 - fy, e.top))),
      automatico: !!e.automatico
    };
  }
  /** O que o site usa de verdade: automático recalcula pelo tamanho da foto. */
  function enqEfetivo(l) {
    var e = l.enquadramento;
    if (!e || e.automatico || typeof e.width !== 'number' || typeof e.height !== 'number' || typeof e.left !== 'number' || typeof e.top !== 'number') {
      return enqAuto(l.largura, l.altura);
    }
    return e;
  }
  function zoomMax(l) {
    var a = enqAuto(l.largura, l.altura);
    return Math.min(3, 400 / Math.max(a.width, a.height));
  }
  function zoomAtual(l) {
    var a = enqAuto(l.largura, l.altura);
    return enqEfetivo(l).width / a.width;
  }
  function aplicarZoom(l, z) {
    var a = enqAuto(l.largura, l.altura);
    z = Math.max(1, Math.min(zoomMax(l), z));
    var e = enqEfetivo(l);
    var f = (a.width * z) / e.width;
    l.enquadramento = enqLimitar({ width: a.width * z, height: a.height * z, left: 50 + (e.left - 50) * f, top: 50 + (e.top - 50) * f, automatico: false });
  }
  function aplicarEnq(img, e) {
    img.style.position = 'absolute';
    img.style.width = e.width + '%';
    img.style.height = e.height + '%';
    img.style.left = e.left + '%';
    img.style.top = e.top + '%';
    img.style.transform = 'translate(-50%,-50%)';
  }
  function urlFoto(arquivo) { return '../' + String(arquivo || '').replace(/^\/+/, ''); }

  // --- Envio de fotos e vídeos (Lote 2B). Se o módulo não existir, avisa. ---
  function pedirMidia(tipo, progresso, ok) {
    var M = window.PainelMidia;
    var fn = M && (tipo === 'video' ? M.escolherVideo : M.escolherFoto);
    if (typeof fn !== 'function') { avisoTopo(MSG.sem_midia); return; }
    avisoTopo(null);
    progresso.textContent = 'Preparando…';
    var p;
    try {
      p = fn.call(M, {
        csrf: csrf(),
        onProgresso: function (v) {
          if (v && typeof v === 'object') {
            if (v.texto) { progresso.textContent = String(v.texto); return; }
            v = v.percentual;
          }
          var n = Number(v);
          if (!isFinite(n)) return;
          if (n <= 1) n = n * 100;
          progresso.textContent = 'Enviando… ' + Math.round(n) + '%';
        }
      });
    } catch (e) { p = Promise.reject(e); }
    Promise.resolve(p).then(function (r) {
      progresso.textContent = '';
      ok(r);
    }, function (e) {
      progresso.textContent = '';
      if (e && (e.cancelado || e.codigo === 'cancelado')) return;
      var cod = e && (e.codigo || e.erro || e.message || e);
      var t = '';
      try { t = M.mensagemErro(cod); } catch (x) { t = ''; }
      avisoTopo(typeof t === 'string' && t ? t : MSG.servidor);
    });
  }

  // --- Lista ---
  TELAS.resultados = function (raiz) {
    raiz.appendChild(topo('Antes e depois e vídeos', 'home'));
    var corpo = h('main', { id: 'conteudo-tela' });
    corpo.appendChild(h('p', { class: 'dica', text: 'Escolha onde as fotos aparecem. Use Subir e Descer para mudar a ordem.' }));
    var avisoApag = avisoApagados();
    if (avisoApag) corpo.appendChild(avisoApag);

    var abas = h('div', { class: 'abas', attrs: { role: 'tablist' } });
    ABAS.forEach(function (a) {
      abas.appendChild(h('button', {
        class: 'aba' + (S.aba === a.id ? ' ativa' : ''), type: 'button', id: 'aba-' + a.id, text: a.nome,
        attrs: { role: 'tab', 'aria-selected': S.aba === a.id ? 'true' : 'false' },
        on: { click: function () { S.aba = a.id; desenhar(); } }
      }));
    });
    corpo.appendChild(abas);

    if (S.aba === 'resultados') {
      var sel = h('select', { id: 'filtro-categoria' });
      categoriasOrd().forEach(function (c) { sel.appendChild(h('option', { value: c.id, text: c.nome })); });
      sel.value = catAtual();
      sel.addEventListener('change', function () { S.cat = sel.value; desenhar(); });
      corpo.appendChild(h('label', { text: 'Categoria', attrs: { for: 'filtro-categoria' } }));
      corpo.appendChild(sel);
    }
    if (S.aba === 'home') {
      corpo.appendChild(h('p', { class: 'dica', text: 'Na página inicial aparecem os ' + LIMITE_HOME + ' primeiros.' }));
    }
    if (S.aba === 'fora') {
      corpo.appendChild(h('p', { class: 'dica', text: 'Estes não aparecem no site. Toque em Editar para colocar de volta.' }));
    }

    var lista = listaVisivel();
    var caixa = h('div', { class: 'lista-resultados', id: 'lista-resultados' });
    if (!lista.length) caixa.appendChild(h('p', { class: 'vazio', text: 'Nada por aqui ainda.' }));
    lista.forEach(function (item, n) {
      var foto = item.tipo === 'comparacao' ? item.depois.arquivo : item.tipo === 'video' ? item.poster : item.arquivo;
      var ordenavel = S.aba !== 'fora';
      var acoes = h('div', { class: 'acoes-item' },
        h('button', { class: 'botao', type: 'button', text: 'Editar', attrs: { 'aria-label': 'Editar ' + nomeItem(item) }, on: { click: function () { ir('item', { id: item.id, lado: 'depois' }); } } }));
      if (ordenavel) {
        var sobe = botaoPequeno('↑ Subir', function () { mover(item, -1); }, 'Subir ' + nomeItem(item));
        var desce = botaoPequeno('↓ Descer', function () { mover(item, 1); }, 'Descer ' + nomeItem(item));
        sobe.disabled = n === 0;
        desce.disabled = n === lista.length - 1;
        acoes.appendChild(sobe);
        acoes.appendChild(desce);
      } else {
        acoes.classList.add('so-editar');
      }
      var linha = h('div', { class: 'linha-res com-acoes', attrs: { 'data-id': item.id } },
        h('img', { class: 'miniatura', src: urlFoto(foto), alt: '' }),
        h('div', { class: 'linha-res-corpo' },
          h('strong', { text: nomeItem(item) }),
          h('span', { class: 'dica', text: TIPOS[item.tipo] + ' · ' + nomeCategoria(item.categoria) + (S.aba === 'home' && n >= LIMITE_HOME ? ' · não aparece (só ' + LIMITE_HOME + ')' : '') })),
        acoes);
      caixa.appendChild(linha);
    });
    corpo.appendChild(caixa);

    corpo.appendChild(h('div', { class: 'linha-botoes' },
      h('button', { class: 'botao botao-principal', type: 'button', id: 'novo-comparacao', text: '+ Novo antes e depois', on: { click: function () { iniciarNovo('comparacao'); } } }),
      h('button', { class: 'botao', type: 'button', id: 'novo-video', text: '+ Novo vídeo', on: { click: function () { iniciarNovo('video'); } } })));
    raiz.appendChild(corpo);
    raiz.appendChild(barraAcao('Salvar no site'));
  };
  CARTOES.push({ id: 'cartao-resultados', icone: '🖼️', titulo: 'Antes e depois', texto: 'Fotos, vídeos, ordem e enquadramento', ir: function () { ir('resultados'); } });

  // --- Novo antes e depois / novo vídeo ---
  function iniciarNovo(tipo) {
    var cs = categoriasOrd();
    S.novo = { tipo: tipo, titulo: '', categoria: S.aba === 'resultados' ? catAtual() : (cs.length ? cs[0].id : ''), antes: null, depois: null, video: null };
    ir('novo');
  }

  function fotoNova(r, titulo, lado) {
    return {
      arquivo: r.arquivo, largura: r.largura, altura: r.altura,
      alt: titulo + ' — ' + (lado === 'antes' ? 'antes' : 'depois') + ' do tratamento',
      enquadramento: enqAuto(r.largura, r.altura)
    };
  }

  TELAS.novo = function (raiz) {
    var n = S.novo;
    if (!n) { ir('resultados'); return; }
    var comp = n.tipo === 'comparacao';
    raiz.appendChild(topo(comp ? 'Novo antes e depois' : 'Novo vídeo', 'resultados'));
    var corpo = h('main', { id: 'conteudo-tela' });
    corpo.appendChild(campoTexto('titulo', {
      rotulo: comp ? 'Nome do tratamento' : 'Texto no canto do vídeo', dica: comp ? 'Exemplo: Drenagem linfática' : 'Exemplo: Resultado',
      max: comp ? 80 : 40, raiz: function () { return S.novo; }, rascunho: true,
      aoMudar: function () { pintarCriar(); }
    }));
    var sel = h('select', { id: 'novo-categoria' });
    categoriasOrd().forEach(function (c) { sel.appendChild(h('option', { value: c.id, text: c.nome })); });
    sel.value = n.categoria;
    sel.addEventListener('change', function () { n.categoria = sel.value; });
    corpo.appendChild(h('label', { text: 'Categoria', attrs: { for: 'novo-categoria' } }));
    corpo.appendChild(sel);

    var progresso = h('p', { class: 'dica', id: 'progresso-midia', attrs: { role: 'status' } });
    var criar = h('button', { class: 'botao botao-principal botao-bloco', type: 'button', id: 'criar', text: comp ? 'Criar antes e depois' : 'Criar vídeo' });

    function bloco(rotulo, lado, id) {
      var miniatura = h('img', { class: 'miniatura grande', alt: '' });
      var cx = h('div', { class: 'bloco-foto' });
      function pintar() {
        var x = lado === 'video' ? (n.video && n.video.poster) : (n[lado] && n[lado].arquivo);
        miniatura.hidden = !x;
        if (x) miniatura.src = urlFoto(x);
        btn.textContent = x ? 'Trocar ' + rotulo.toLowerCase() : 'Escolher ' + rotulo.toLowerCase();
      }
      var btn = h('button', { class: 'botao', type: 'button', id: id, on: { click: function () {
        pedirMidia(lado === 'video' ? 'video' : 'foto', progresso, function (r) {
          if (lado === 'video') n.video = { arquivo: r.arquivo, poster: r.poster, largura: r.largura, altura: r.altura };
          else n[lado] = fotoNova(r, n.titulo || 'Resultado', lado);
          pintar();
          pintarCriar();
        });
      } } });
      cx.appendChild(h('p', { class: 'rotulo', text: rotulo }));
      cx.appendChild(miniatura);
      cx.appendChild(btn);
      pintar();
      return cx;
    }
    if (comp) {
      corpo.appendChild(bloco('Foto ANTES', 'antes', 'escolher-antes'));
      corpo.appendChild(bloco('Foto DEPOIS', 'depois', 'escolher-depois'));
    } else {
      corpo.appendChild(bloco('Vídeo', 'video', 'escolher-video'));
    }
    corpo.appendChild(progresso);

    function pintarCriar() {
      var pronto = n.titulo.trim() !== '' && (comp ? (n.antes && n.depois) : n.video);
      criar.disabled = !pronto;
    }
    criar.addEventListener('click', function () {
      var t = n.titulo.trim();
      if (!t) return;
      var item = {
        id: codigoNovo(comp ? 'cmp' : 'vid'), tipo: n.tipo, categoria: n.categoria,
        em_resultados: true, ordem: proximaOrdem('ordem', function (i) { return i.categoria === n.categoria && i.em_resultados; }),
        na_home: false, ordem_home: 0
      };
      if (comp) {
        item.titulo = t;
        item.antes = fotoNova(n.antes, t, 'antes');
        item.antes.arquivo = n.antes.arquivo;
        item.depois = fotoNova(n.depois, t, 'depois');
      } else {
        item.rotulo = t;
        item.arquivo = n.video.arquivo;
        item.poster = n.video.poster;
        item.largura = n.video.largura;
        item.altura = n.video.altura;
      }
      itensRes().push(item);
      S.novo = null;
      mudou('Criou ' + (comp ? 'um antes e depois' : 'um vídeo'));
      S.aba = 'resultados';
      S.cat = item.categoria;
      ir('item', { id: item.id, lado: 'antes' });
    });
    corpo.appendChild(criar);
    pintarCriar();
    corpo.appendChild(h('p', { class: 'dica', text: 'Depois de criar, você ajusta o enquadramento e toca em Salvar no site.' }));
    raiz.appendChild(corpo);
  };

  // --- Edição de um resultado ---
  function caixaMarcar(id, texto, marcado, aoMudar) {
    var c = h('input', { type: 'checkbox', id: id });
    c.checked = !!marcado;
    c.addEventListener('change', function () { aoMudar(c.checked); });
    return h('label', { class: 'marcar', attrs: { for: id } }, c, h('span', { text: texto }));
  }

  /** Prévia igual à do site: mesmas medidas, cores e a barra de comparar. */
  function montarPrevia(id) {
    function lado(nome) { var i = buscar(id); return i ? i[nome] : null; }
    var imgDepois = h('img', { alt: '' });
    var imgAntes = h('img', { alt: '' });
    var clip = h('div', { class: 'ba-clip site-clip' }, imgAntes, h('span', { class: 'site-etq site-antes', text: 'Antes' }));
    var linha = h('div', { class: 'ba-line site-linha' });
    var barra = h('input', { type: 'range', class: 'site-barra', attrs: { min: '0', max: '100', value: '50', 'aria-label': 'Comparar antes e depois' } });
    barra.addEventListener('input', function () {
      clip.style.clipPath = 'inset(0 ' + (100 - barra.value) + '% 0 0)';
      linha.style.left = barra.value + '%';
    });
    var quadro = h('div', { class: 'site-quadro', id: 'previa-site' }, imgDepois, h('span', { class: 'site-etq site-depois', text: 'Depois' }), clip, linha, barra);
    function pintar() {
      var a = lado('antes'), d = lado('depois');
      if (!a || !d) return;
      imgAntes.src = urlFoto(a.arquivo);
      imgDepois.src = urlFoto(d.arquivo);
      aplicarEnq(imgAntes, enqEfetivo(a));
      aplicarEnq(imgDepois, enqEfetivo(d));
    }
    pintar();
    return { el: quadro, pintar: pintar };
  }

  /** Passos para "Desfazer ajuste", por foto (item + lado). Guarda até PASSOS_MAX enquadramentos anteriores. */
  var PASSOS_MAX = 50;
  var pilhas = {};
  function limparPilhas(prefixo) {
    Object.keys(pilhas).forEach(function (k) { if (!prefixo || k.indexOf(prefixo) === 0) delete pilhas[k]; });
  }
  S.sobrepor = false;   // "Sobrepor a outra foto" ligado
  S.transp = 50;        // transparência da outra foto (0 a 100)

  /** Quadro de edição: 1 dedo arrasta, 2 dedos aumentam/diminuem. */
  function montarEditor(id, nomeLado, aoMudar) {
    function lado() { var i = buscar(id); return i ? i[nomeLado] : null; }
    var outroNome = nomeLado === 'antes' ? 'depois' : 'antes';
    function outro() { var i = buscar(id); return i ? i[outroNome] : null; }
    var chavePilha = id + '|' + nomeLado;
    var pilha = pilhas[chavePilha] || (pilhas[chavePilha] = []);
    var img = h('img', { alt: '', class: 'foto-edicao', attrs: { draggable: 'false' } });
    var imgOutra = h('img', { alt: '', class: 'foto-sobreposta', id: 'foto-sobreposta', attrs: { draggable: 'false' } });
    imgOutra.hidden = !S.sobrepor;
    var quadro = h('div', { class: 'quadro', id: 'quadro-edicao', attrs: { role: 'img', 'aria-label': 'Foto ' + (nomeLado === 'antes' ? 'ANTES' : 'DEPOIS') + '. Arraste para mover. Use dois dedos para aumentar.' } }, img, imgOutra);
    var rotuloZoom = h('p', { class: 'zoom-valor', id: 'zoom-valor', attrs: { role: 'status' } });
    var faixa = h('input', { type: 'range', id: 'zoom', attrs: { min: '100', step: '1', 'aria-label': 'Zoom da foto' } });
    var menos = h('button', { class: 'botao', type: 'button', id: 'zoom-menos', text: '−', attrs: { 'aria-label': 'Diminuir a foto' } });
    var mais = h('button', { class: 'botao', type: 'button', id: 'zoom-mais', text: '+', attrs: { 'aria-label': 'Aumentar a foto' } });
    var auto = h('button', { class: 'botao botao-leve', type: 'button', id: 'voltar-auto', text: 'Voltar ao automático' });
    var desfazer = h('button', { class: 'botao botao-leve', type: 'button', id: 'desfazer-ajuste', text: 'Desfazer ajuste' });
    var sobrepor = h('button', { class: 'botao botao-leve', type: 'button', id: 'sobrepor', text: 'Sobrepor a outra foto', attrs: { 'aria-pressed': S.sobrepor ? 'true' : 'false' } });
    var faixaTransp = h('input', { type: 'range', id: 'sobrepor-transp', attrs: { min: '0', max: '100', step: '5', value: String(S.transp), 'aria-label': 'Transparência da outra foto' } });
    var rotuloTransp = h('span', { class: 'transp-valor', id: 'sobrepor-valor' });
    var caixaTransp = h('div', { class: 'sobrepor-controle', id: 'sobrepor-controle' },
      h('label', { class: 'rotulo-transp', text: 'Transparência da outra foto', attrs: { for: 'sobrepor-transp' } }),
      h('div', { class: 'zoom-linha' }, faixaTransp, rotuloTransp));
    caixaTransp.hidden = !S.sobrepor;

    function pintarSobreposta() {
      var o = outro();
      imgOutra.hidden = !S.sobrepor || !o;
      caixaTransp.hidden = !S.sobrepor || !o;
      sobrepor.setAttribute('aria-pressed', S.sobrepor ? 'true' : 'false');
      sobrepor.textContent = S.sobrepor ? 'Tirar a outra foto de cima' : 'Sobrepor a outra foto';
      rotuloTransp.textContent = S.transp + '%';
      faixaTransp.value = String(S.transp);
      if (!o) return;
      if (imgOutra.getAttribute('data-arq') !== o.arquivo) { imgOutra.src = urlFoto(o.arquivo); imgOutra.setAttribute('data-arq', o.arquivo); }
      aplicarEnq(imgOutra, enqEfetivo(o));
      // transparência 0% = a outra foto aparece inteira; 100% = não aparece
      imgOutra.style.opacity = String((100 - S.transp) / 100);
    }
    sobrepor.addEventListener('click', function () { S.sobrepor = !S.sobrepor; pintarSobreposta(); });
    faixaTransp.addEventListener('input', function () { S.transp = Number(faixaTransp.value); pintarSobreposta(); });

    function pintar() {
      var l = lado();
      if (!l) return;
      img.src = urlFoto(l.arquivo);
      var e = enqEfetivo(l);
      aplicarEnq(img, e);
      var zmax = Math.floor(zoomMax(l) * 100);
      faixa.max = String(zmax);
      faixa.value = String(Math.round(zoomAtual(l) * 100));
      rotuloZoom.textContent = 'Zoom: ' + Math.round(zoomAtual(l) * 100) + '%' + (l.enquadramento && l.enquadramento.automatico ? ' (automático)' : '');
      auto.disabled = !!(l.enquadramento && l.enquadramento.automatico);
      desfazer.disabled = pilha.length === 0;
      pintarSobreposta();
    }
    function mexeu() { pintar(); aoMudar(); }

    // Cada arraste ou zoom vira um passo do Desfazer. Um gesto contínuo (arrastar, mexer na barra) conta como um passo só.
    var emGesto = false;
    function ajustar(fn, agrupar) {
      var l = lado();
      if (!l) return;
      var antes = copia(enqEfetivo(l));
      fn(l);
      var mudouDeVerdade = JSON.stringify(antes) !== JSON.stringify(enqEfetivo(l));
      if (mudouDeVerdade && !(agrupar && emGesto)) {
        pilha.push(antes);
        while (pilha.length > PASSOS_MAX) pilha.shift();
      }
      if (agrupar && mudouDeVerdade) emGesto = true;
      mexeu();
    }
    function zoomPara(z, agrupar) { ajustar(function (l) { aplicarZoom(l, z); }, agrupar); }
    faixa.addEventListener('input', function () { zoomPara(Number(faixa.value) / 100, true); });
    faixa.addEventListener('change', function () { emGesto = false; });
    menos.addEventListener('click', function () { var l = lado(); if (l) zoomPara(zoomAtual(l) - 0.1); });
    mais.addEventListener('click', function () { var l = lado(); if (l) zoomPara(zoomAtual(l) + 0.1); });
    auto.addEventListener('click', function () {
      ajustar(function (l) { l.enquadramento = enqAuto(l.largura, l.altura); });
    });
    desfazer.addEventListener('click', function () {
      var l = lado();
      if (!l || !pilha.length) return;
      l.enquadramento = enqLimitar(pilha.pop());
      mexeu();
    });

    // Gestos (Pointer Events: funciona igual com dedo, mouse e caneta)
    var dedos = {};
    var pinca = null;
    function contar2() { return Object.keys(dedos).length; }
    function distancia() {
      var k = Object.keys(dedos);
      var a = dedos[k[0]], b = dedos[k[1]];
      return Math.sqrt(Math.pow(a.x - b.x, 2) + Math.pow(a.y - b.y, 2));
    }
    quadro.addEventListener('pointerdown', function (ev) {
      try { quadro.setPointerCapture(ev.pointerId); } catch (x) { /* ponteiro de teste */ }
      dedos[ev.pointerId] = { x: ev.clientX, y: ev.clientY };
      if (contar2() === 2) {
        var l = lado();
        pinca = { d: Math.max(distancia(), 1), z: l ? zoomAtual(l) : 1 };
        emGesto = false;
      }
      quadro.classList.add('mexendo');
      ev.preventDefault();
    });
    quadro.addEventListener('pointermove', function (ev) {
      var p = dedos[ev.pointerId];
      var l = lado();
      if (!p || !l) return;
      if (contar2() === 1) {
        var r = quadro.getBoundingClientRect();
        ajustar(function (l2) {
          var e = enqEfetivo(l2);
          l2.enquadramento = enqLimitar({
            width: e.width, height: e.height,
            left: e.left + (ev.clientX - p.x) / r.width * 100,
            top: e.top + (ev.clientY - p.y) / r.height * 100,
            automatico: false
          });
        }, true);
        dedos[ev.pointerId] = { x: ev.clientX, y: ev.clientY };
      } else if (contar2() === 2 && pinca) {
        dedos[ev.pointerId] = { x: ev.clientX, y: ev.clientY };
        ajustar(function (l2) { aplicarZoom(l2, pinca.z * distancia() / pinca.d); }, true);
      }
      ev.preventDefault();
    });
    function soltou(ev) {
      delete dedos[ev.pointerId];
      pinca = null;
      if (contar2() === 0) { quadro.classList.remove('mexendo'); emGesto = false; }
    }
    quadro.addEventListener('pointerup', soltou);
    quadro.addEventListener('pointercancel', soltou);
    quadro.addEventListener('lostpointercapture', soltou);

    pintar();
    var el = h('div', { class: 'editor-quadro' },
      quadro,
      h('p', { class: 'dica', text: 'Arraste com um dedo para mover a foto. Use dois dedos para aumentar ou diminuir.' }),
      h('div', { class: 'zoom-linha' }, menos, faixa, mais),
      rotuloZoom,
      h('div', { class: 'linha-botoes botoes-editor' }, desfazer, auto),
      h('p', { class: 'dica', text: 'Para alinhar com a outra foto, ponha ela por cima, meio transparente, e confira o umbigo ou o nariz.' }),
      sobrepor,
      caixaTransp);
    return { el: el, pintar: pintar };
  }

  TELAS.item = function (raiz) {
    var a = S.arg || {};
    var item = buscar(a.id);
    if (!item) { ir('resultados'); return; }
    var comp = item.tipo === 'comparacao';
    raiz.appendChild(topo(comp ? 'Editar antes e depois' : item.tipo === 'video' ? 'Editar vídeo' : 'Editar foto', 'resultados'));
    var corpo = h('main', { id: 'conteudo-tela' });
    var idx = indiceDe(item.id);
    var base = 'resultados.itens.' + idx;
    var progresso = h('p', { class: 'dica', id: 'progresso-midia', attrs: { role: 'status' } });
    var pintarTodos = function () {};

    corpo.appendChild(campoTexto(base + (comp ? '.titulo' : '.rotulo'), {
      rotulo: comp ? 'Nome do tratamento' : 'Texto no canto', dica: comp ? 'Aparece embaixo da comparação, no site.' : 'Aparece no canto do ' + (item.tipo === 'video' ? 'vídeo' : 'resultado') + '.',
      max: comp ? 80 : 40, aoMudar: function () { mudou('Mudou ' + nomeItem(item)); }
    }));

    var sel = h('select', { id: 'item-categoria' });
    categoriasOrd().forEach(function (c) { sel.appendChild(h('option', { value: c.id, text: c.nome })); });
    sel.value = item.categoria;
    sel.addEventListener('change', function () {
      item.categoria = sel.value;
      item.ordem = proximaOrdem('ordem', function (i) { return i !== item && i.categoria === item.categoria && i.em_resultados; });
      S.cat = item.categoria;
      mudou('Mudou ' + nomeItem(item));
    });
    corpo.appendChild(h('label', { text: 'Categoria', attrs: { for: 'item-categoria' } }));
    corpo.appendChild(sel);

    corpo.appendChild(h('p', { class: 'rotulo', text: 'Onde aparece no site' }));
    corpo.appendChild(caixaMarcar('item-em-resultados', 'Na página de resultados', item.em_resultados, function (v) {
      item.em_resultados = v;
      if (v) item.ordem = proximaOrdem('ordem', function (i) { return i !== item && i.categoria === item.categoria && i.em_resultados; });
      mudou('Mudou onde aparece ' + nomeItem(item));
    }));
    if (comp) {
      corpo.appendChild(caixaMarcar('item-na-home', 'Na página inicial (aparecem só os ' + LIMITE_HOME + ' primeiros)', item.na_home, function (v) {
        item.na_home = v;
        if (v) item.ordem_home = proximaOrdem('ordem_home', function (i) { return i !== item && i.na_home; });
        mudou('Mudou onde aparece ' + nomeItem(item));
      }));
    }

    if (comp) {
      var lado = a.lado === 'antes' ? 'antes' : 'depois';
      var abas = h('div', { class: 'abas', attrs: { role: 'tablist' } });
      [['antes', 'Foto ANTES'], ['depois', 'Foto DEPOIS']].forEach(function (p) {
        abas.appendChild(h('button', {
          class: 'aba' + (lado === p[0] ? ' ativa' : ''), type: 'button', id: 'aba-' + p[0], text: p[1],
          attrs: { role: 'tab', 'aria-selected': lado === p[0] ? 'true' : 'false' },
          on: { click: function () { S.arg = { id: item.id, lado: p[0] }; desenhar(); } }
        }));
      });
      corpo.appendChild(h('h2', { text: 'Enquadramento' }));
      corpo.appendChild(abas);
      var previa = montarPrevia(item.id);
      var ed = montarEditor(item.id, lado, function () {
        mudou('Mudou o enquadramento de ' + nomeItem(item));
        previa.pintar();
      });
      corpo.appendChild(ed.el);
      corpo.appendChild(h('button', { class: 'botao botao-bloco', type: 'button', id: 'trocar-foto', text: 'Trocar esta foto', on: { click: function () {
        pedirMidia('foto', progresso, function (r) {
          var velho = item[lado] || {};
          limparPilhas(item.id + '|' + lado);
          item[lado] = { arquivo: r.arquivo, largura: r.largura, altura: r.altura, alt: velho.alt || (nomeItem(item) + ' — ' + lado + ' do tratamento'), enquadramento: enqAuto(r.largura, r.altura) };
          mudou('Trocou a foto de ' + nomeItem(item));
          desenhar();
        });
      } } }));
      corpo.appendChild(progresso);
      corpo.appendChild(h('h2', { text: 'Como fica no site' }));
      corpo.appendChild(h('p', { class: 'dica', text: 'Arraste a barra para comparar, igual ao site.' }));
      corpo.appendChild(previa.el);
      pintarTodos = function () { ed.pintar(); previa.pintar(); };
    } else if (item.tipo === 'video') {
      corpo.appendChild(h('img', { class: 'miniatura grande', src: urlFoto(item.poster), alt: 'Capa do vídeo' }));
      corpo.appendChild(h('button', { class: 'botao botao-bloco', type: 'button', id: 'trocar-video', text: 'Trocar o vídeo', on: { click: function () {
        pedirMidia('video', progresso, function (r) {
          item.arquivo = r.arquivo; item.poster = r.poster; item.largura = r.largura; item.altura = r.altura;
          mudou('Trocou o vídeo ' + nomeItem(item));
          desenhar();
        });
      } } }));
      corpo.appendChild(progresso);
    } else {
      corpo.appendChild(h('img', { class: 'miniatura grande', src: urlFoto(item.arquivo), alt: '' }));
      corpo.appendChild(h('button', { class: 'botao botao-bloco', type: 'button', id: 'trocar-foto', text: 'Trocar a foto', on: { click: function () {
        pedirMidia('foto', progresso, function (r) {
          item.arquivo = r.arquivo; item.largura = r.largura; item.altura = r.altura;
          mudou('Trocou a foto ' + nomeItem(item));
          desenhar();
        });
      } } }));
      corpo.appendChild(progresso);
    }
    var zona = h('section', { class: 'zona-apagar' });
    zona.appendChild(h('h2', { text: 'Apagar' }));
    zona.appendChild(h('button', { class: 'botao botao-perigo botao-bloco', type: 'button', id: 'apagar-item', text: 'Apagar ' + (comp ? 'este antes e depois' : item.tipo === 'video' ? 'este vídeo' : 'esta foto'), on: { click: function () { apagarItem(item); } } }));
    corpo.appendChild(zona);
    raiz.appendChild(corpo);
    raiz.appendChild(barraAcao('Salvar no site'));
  };

  // ---------------------------------------------------------------------------
  // Janela de confirmação (usada ao voltar versão e ao apagar)
  // ---------------------------------------------------------------------------
  function confirmar(o) {
    return new Promise(function (resolve) {
      var anterior = document.activeElement;
      var fundo = h('div', { class: 'modal-fundo' });
      function fechar(v) {
        document.removeEventListener('keydown', tecla);
        if (fundo.parentNode) fundo.parentNode.removeChild(fundo);
        if (anterior && anterior.focus && document.body.contains(anterior)) anterior.focus();
        resolve(v);
      }
      function tecla(ev) { if (ev.key === 'Escape') fechar(false); }
      var cancelar = h('button', { class: 'botao', type: 'button', id: 'modal-cancelar', text: o.cancelar || 'Cancelar', on: { click: function () { fechar(false); } } });
      var ok = h('button', { class: 'botao ' + (o.perigo ? 'botao-perigo' : 'botao-principal'), type: 'button', id: 'modal-ok', text: o.ok, on: { click: function () { fechar(true); } } });
      fundo.appendChild(h('div', { class: 'modal', attrs: { role: 'dialog', 'aria-modal': 'true', 'aria-labelledby': 'modal-titulo' } },
        h('h2', { id: 'modal-titulo', text: o.titulo }),
        h('p', { text: o.texto }),
        h('div', { class: 'linha-botoes modal-botoes' }, cancelar, ok)));
      document.body.appendChild(fundo);
      document.addEventListener('keydown', tecla);
      cancelar.focus();
    });
  }

  // ---------------------------------------------------------------------------
  // Versões e Desfazer (L13)
  // ---------------------------------------------------------------------------
  /** Volta o site para a versão n (0 = conteúdo original). A volta também vira uma versão nova. */
  function restaurarVersao(n, aviso) {
    var revAntes = S.rev;
    return api('restaurar', { n: n, revisao: S.rev }).then(function (r) {
      if (r.ok) {
        S.c = r.conteudo;
        S.rev = r.revisao;
        S.sujo = false;
        S.resumo = [];
        S.apagados = []; limparPilhas();
        desenhar();
        toast(aviso, { texto: 'Desfazer', fazer: function () { restaurarVersao(revAntes, 'Desfeito. O site voltou ao que estava antes.'); } }, 10);
      } else {
        avisoTopo(mensagemErro(r));
      }
      return r;
    });
  }

  function quando(texto) {
    var d = new Date(texto);
    if (!texto || isNaN(d.getTime())) return '';
    return d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' }) + ' às ' +
      d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
  }

  TELAS.versoes = function (raiz) {
    raiz.appendChild(topo('Versões do site', 'home'));
    var corpo = h('main', { id: 'conteudo-tela' });
    corpo.appendChild(h('p', { class: 'dica', text: 'Cada vez que você salva, o site guarda uma versão. Ficam guardadas as últimas 20. Voltar não apaga nada: dá para desfazer.' }));
    if (S.sujo) corpo.appendChild(alerta('Você tem mudanças que ainda não salvou. Se voltar uma versão, elas serão perdidas.', 'aviso'));
    var lista = h('div', { id: 'lista-versoes' }, h('p', { class: 'carregando', text: 'Carregando…' }));
    corpo.appendChild(lista);
    raiz.appendChild(corpo);
    api('versoes').then(function (r) {
      limpar(lista);
      if (!r.ok) { lista.appendChild(alerta(mensagemErro(r))); return; }
      r.versoes.forEach(function (v) {
        var atual = v.n === S.rev;
        var nome = v.n === 0 ? 'Conteúdo original do site' : quando(v.data);
        var linha = h('div', { class: 'linha-res versao' + (atual ? ' atual' : ''), attrs: { 'data-n': String(v.n) } },
          h('div', { class: 'linha-res-corpo' },
            h('strong', { text: nome }),
            v.n === 0 ? null : h('span', { class: 'dica', text: v.resumo || 'Salvou mudanças' }),
            atual ? h('span', { class: 'etiqueta', text: 'É a versão que está no site agora' }) : null,
            atual ? null : h('div', { class: 'linha-botoes' },
              h('button', { class: 'botao', type: 'button', text: 'Voltar para esta versão', attrs: { 'aria-label': 'Voltar para a versão ' + nome },
                on: { click: function () {
                  confirmar({
                    titulo: 'Voltar para esta versão?',
                    texto: 'O site vai ficar como estava em: ' + nome + '. O que está no site agora continua guardado, e você pode desfazer.',
                    ok: 'Voltar para esta versão'
                  }).then(function (sim) {
                    if (!sim) return;
                    restaurarVersao(v.n, 'Pronto. O site voltou para: ' + nome + '.');
                  });
                } } }))));
        lista.appendChild(linha);
      });
    });
  };
  CARTOES.push({ id: 'cartao-versoes', icone: '🕘', titulo: 'Versões', texto: 'Voltar para como o site estava antes', ir: function () { ir('versoes'); } });

  // ---------------------------------------------------------------------------
  // Apagar (P3) e lixeira
  // ---------------------------------------------------------------------------
  /** Pede confirmação com o nome e marca o item para apagar. Ele só sai do site em Salvar no site. */
  function apagarItem(item) {
    var nome = nomeItem(item);
    return confirmar({
      titulo: 'Apagar "' + nome + '"?',
      texto: 'Ele fica marcado para apagar e só sai do site quando você tocar em Salvar no site. Até lá, dá para mudar de ideia.',
      ok: 'Apagar',
      perigo: true
    }).then(function (sim) {
      if (!sim) return null;
      var lista = itensRes();
      var pos = lista.indexOf(item);
      if (pos < 0) return null;
      lista.splice(pos, 1);
      S.apagados.push({ item: item, pos: pos });
      mudou('Apagou ' + nome);
      S.tela = 'resultados';
      S.arg = null;
      desenhar();
      window.scrollTo(0, 0);
      return { ok: true };
    });
  }

  /** Tira a marca de "será apagado": o item volta para o lugar e para o fim da ordem se o lugar foi tomado. */
  function naoApagar(entrada) {
    var i = S.apagados.indexOf(entrada);
    if (i < 0) return;
    S.apagados.splice(i, 1);
    var item = entrada.item;
    var lista = itensRes();
    lista.splice(Math.min(entrada.pos, lista.length), 0, item);
    [['ordem', function (x) { return x.categoria === item.categoria && x.em_resultados; }],
     ['ordem_home', function (x) { return x.na_home; }]].forEach(function (par) {
      var chave = par[0];
      var ocupado = lista.some(function (x) { return x !== item && par[1](x) && x[chave] === item[chave]; });
      if (ocupado) item[chave] = proximaOrdem(chave, function (x) { return x !== item && par[1](x); });
    });
    var rot = S.resumo.indexOf('Apagou ' + nomeItem(item));
    if (rot >= 0) S.resumo.splice(rot, 1);
    if (!S.apagados.length && !S.resumo.length) S.sujo = false;
    atualizarBarra();
    var y = window.scrollY;
    desenhar();
    window.scrollTo(0, y);
  }

  /** Faixa "N itens serão apagados" com um botão Não apagar para cada um. */
  function avisoApagados() {
    if (!S.apagados.length) return null;
    var n = S.apagados.length;
    var lista = h('ul', { class: 'lista-apagados' });
    S.apagados.forEach(function (e) {
      lista.appendChild(h('li', {},
        h('span', { text: '"' + nomeItem(e.item) + '"' }),
        h('button', { class: 'botao botao-leve', type: 'button', text: 'Não apagar', attrs: { 'aria-label': 'Não apagar ' + nomeItem(e.item) }, on: { click: function () { naoApagar(e); } } })));
    });
    return h('div', { class: 'alerta aviso', id: 'aviso-apagados', attrs: { role: 'status' } },
      h('span', { class: 'alerta-icone', text: '!', attrs: { 'aria-hidden': 'true' } }),
      h('div', { class: 'alerta-corpo' },
        h('strong', { text: n === 1 ? '1 item será apagado' : n + ' itens serão apagados' }),
        h('span', { text: ' quando você tocar em Salvar no site.' }),
        lista));
  }

  function tamanhoLegivel(b) {
    if (b >= 1048576) return (Math.round(b / 104857.6) / 10).toString().replace('.', ',') + ' MB';
    return Math.max(1, Math.round(b / 1024)) + ' KB';
  }

  TELAS.lixeira = function (raiz) {
    raiz.appendChild(topo('Lixeira', 'home'));
    var corpo = h('main', { id: 'conteudo-tela' });
    corpo.appendChild(h('p', { class: 'dica', text: 'Aqui ficam por 30 dias as fotos e vídeos que saíram das versões guardadas. Toque em Restaurar para trazer de volta.' }));
    var lista = h('div', { id: 'lista-lixeira' }, h('p', { class: 'carregando', text: 'Carregando…' }));
    corpo.appendChild(lista);
    raiz.appendChild(corpo);
    api('lixeira').then(function (r) {
      limpar(lista);
      if (!r.ok) { lista.appendChild(alerta(mensagemErro(r))); return; }
      if (!r.itens.length) { lista.appendChild(h('p', { class: 'vazio', text: 'A lixeira está vazia.' })); return; }
      r.itens.forEach(function (m) {
        var quandoTxt = quando(m.data);
        lista.appendChild(h('div', { class: 'linha-res lixo', attrs: { 'data-id': m.id } },
          h('div', { class: 'linha-res-corpo' },
            h('strong', { text: m.nome || 'Arquivo' }),
            h('span', { class: 'dica', text: (quandoTxt ? 'Saiu em ' + quandoTxt + ' · ' : '') + tamanhoLegivel(m.tamanho) }),
            h('div', { class: 'linha-botoes' },
              h('button', { class: 'botao', type: 'button', text: 'Restaurar', attrs: { 'aria-label': 'Restaurar ' + (m.nome || 'arquivo') }, on: { click: function (ev) {
                var b = ev.currentTarget;
                b.disabled = true;
                api('lixeira_restaurar', { id: m.id }).then(function (rr) {
                  if (rr.ok) { toast('Pronto. O arquivo voltou.'); desenhar(); return; }
                  b.disabled = false;
                  toast(mensagemErro(rr));
                });
              } } })))));
      });
    });
  };
  CARTOES.push({ id: 'cartao-ajuda', icone: '❓', titulo: 'Ajuda', texto: 'Passo a passo para usar o painel', href: 'ajuda.php' });
  CARTOES.push({ id: 'cartao-lixeira', icone: '🗑️', titulo: 'Lixeira', texto: 'Trazer de volta fotos e vídeos', ir: function () { ir('lixeira'); } });

  // ---------------------------------------------------------------------------
  // Diagnóstico de IP (T10): para o Vinicius conferir de onde o painel enxerga o acesso
  // ---------------------------------------------------------------------------
  TELAS.diagnostico = function (raiz) {
    raiz.appendChild(topo('Diagnóstico de acesso', 'home'));
    var corpo = h('main', { id: 'conteudo-tela' });
    corpo.appendChild(h('p', { class: 'dica', text: 'Esta tela é para o Vinicius. Ela mostra qual endereço o painel usa para limitar as tentativas de senha.' }));
    var caixa = h('div', { id: 'diagnostico-ip' }, h('p', { class: 'carregando', text: 'Carregando…' }));
    corpo.appendChild(caixa);
    raiz.appendChild(corpo);
    api('diagnostico_ip').then(function (r) {
      limpar(caixa);
      if (!r.ok) { caixa.appendChild(alerta(mensagemErro(r))); return; }
      var usaCab = !!r.cabecalho;
      var certo = usaCab ? (r.remoto_e_proxy_confiavel && !!r.valor_cabecalho) : true;
      var resumo = !usaCab
        ? 'O painel usa o endereço direto da conexão. Se o site fica atrás de uma proteção (CDN), todos podem parecer ter o mesmo endereço.'
        : certo
          ? 'Tudo certo: o painel enxerga o endereço real de quem acessa.'
          : 'Atenção: o painel não está conseguindo ler o endereço real. Fale com o Vinicius antes de publicar.';
      caixa.appendChild(alerta(resumo, certo ? 'ok' : null));
      var linhas = [
        ['Endereço que o painel usa', r.ip_cliente || '(vazio)'],
        ['Endereço da conexão direta', r.remote_addr || '(vazio)'],
        ['Cabeçalho de proxy configurado', r.cabecalho || 'nenhum'],
        ['Valor recebido nesse cabeçalho', usaCab ? (r.valor_cabecalho || '(vazio)') : '—'],
        ['A conexão direta é um proxy confiável?', r.remoto_e_proxy_confiavel ? 'Sim' : 'Não'],
        ['Proxies confiáveis', r.proxies_confiaveis && r.proxies_confiaveis.length ? r.proxies_confiaveis.join(', ') : 'nenhum'],
        ['Ambiente', r.ambiente || '—']
      ];
      var lista = h('dl', { class: 'diagnostico' });
      linhas.forEach(function (l) {
        lista.appendChild(h('dt', { text: l[0] }));
        lista.appendChild(h('dd', { text: l[1] }));
      });
      caixa.appendChild(lista);
    });
  };
  CARTOES.push({ id: 'cartao-diagnostico', icone: '🔧', titulo: 'Diagnóstico', texto: 'Para o Vinicius: confere o endereço de acesso', ir: function () { ir('diagnostico'); } });

  /*@@TELAS*/

  // ---------------------------------------------------------------------------
  // Início do painel
  // ---------------------------------------------------------------------------
  carregar().then(function (ok) { if (ok) desenhar(); });
})();
