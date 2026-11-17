# Relatório de melhorias — Studio Gabriela Santos

Data: 16/11/2026. Branch: `melhorias-claude`. **Nada foi publicado no domínio e nada foi juntado à `main`.**
O site no ar continua o antigo até o Vinicius ver a prévia e aprovar.

## 1. Resumo em uma frase

O site ficou mais rápido, os vídeos não ficam mais pretos antes do play e ganhou um painel no celular. Nele a dona troca fotos, vídeos e textos e ajusta o antes/depois com zoom, posição e sobreposição, sem mexer em código.

## 2. Melhorias por área

### Painel da dona (novo, em `/painel/`)

- **Entrada:** só com senha. A senha fica guardada fora do site, num arquivo que ninguém baixa.
- **Antes e depois:**
  - criar, trocar foto, apagar, mudar a ordem (Subir e Descer) e escolher onde cada um aparece (página inicial, página de resultados ou Escondidos);
  - definir a categoria.
- **Enquadramento de cada foto:**
  - arrastar com o dedo e aumentar ou diminuir com pinça ou com os botões − e +;
  - "Desfazer ajuste" (até 50 passos) e "Voltar ao automático";
  - **"Sobrepor a outra foto":** mostra a outra foto por cima, meio transparente, para alinhar umbigo ou nariz.
- **Vídeos:**
  - no próprio celular, o painel tira a capa do **primeiro quadro** do vídeo, para não ficar tela preta no iPhone;
  - antes de enviar, recusa vídeo HEVC ou maior que 1080p e explica como gravar em "Mais compatível";
  - se a capa falhar, o vídeo não é enviado.
- **Textos:** dá para editar os textos das seções, procedimentos, cursos, depoimentos e contato.
- **Um jeito só de gravar:** tudo, inclusive apagar, fica pendente até tocar em "Salvar no site". Quando não há mudança, o botão mostra "Nada para salvar".
- **Segurança contra erros:**
  - "Desfazer última alteração";
  - as 20 últimas versões ficam guardadas;
  - o que foi apagado vai para a lixeira;
  - se alguém mudou o site em outro aparelho, o painel avisa e pede confirmação antes de gravar por cima.
- **Ajuda:** página com o passo a passo em português simples, com os nomes reais dos botões.

### Site público

- **O visual ficou igual.** Única exceção: o botão principal do topo voltou a se destacar, porque antes estava quase invisível, com contraste de 1,04:1.
- Agora o conteúdo vem do JSON que o painel edita, mas o HTML é o mesmo e o SEO não mudou.
- **Vídeos:**
  - nenhum vídeo baixa antes do play;
  - todos têm capa no primeiro quadro, `playsinline` e `preload="none"`;
  - a capa da apresentação estava errada (era a foto "sobre") e foi corrigida.
- **Comparador antes/depois:**
  - agora dá para arrastar sobre a foto inteira, e não só na barrinha;
  - tem nome acessível;
  - a etiqueta some no fim do arraste.
- **Carrosséis:** as setas não pulam mais para o card errado no iOS antigo.
- **Menu:** fecha com Esc e tem `aria-expanded`.
- **Acessibilidade:** de 85–87 para 100.

### Segurança e privacidade (LGPD)

- **Fotos enviadas:** o servidor refaz cada foto, o que **apaga GPS e EXIF** das fotos das clientes. Os vídeos têm os metadados zerados.
- **Foto apagada:** sai do ar na hora. Fica só uma cópia privada para poder restaurar.
- **Senha:** limite de tentativas por IP (IPv6 agrupado por /64), sem brecha para pedidos em paralelo.
- **Sessão:** cookie seguro, expira por inatividade e tem limite absoluto de 12 h.
- **Formulários:** CSRF em toda ação e CSP sem script inline.
- **Campos que o painel não edita** são validados: links só `https`, mapa só do Google.

## 3. Funções antes e depois

| Função | Antes | Depois |
|---|---|---|
| Trocar foto, vídeo ou texto | Só editando o HTML | Pelo painel no celular |
| Ajustar zoom e posição no antes/depois | Só no código, em % | Arrastar, pinça, desfazer e sobrepor |
| Capa dos vídeos no iPhone | Feita à mão; a da apresentação estava errada | Primeiro quadro, automática no envio |
| Vídeos baixando sem play | 4,7 a 6,3 MB em resultados | 0 bytes |
| Comparador | Só a barrinha de 16 px funcionava | Foto inteira arrastável |
| Voltar atrás | Não havia | Desfazer, 20 versões e lixeira |

## 4. Bugs encontrados

Todos foram reproduzidos com testes automáticos antes de serem corrigidos. O detalhe está em `.auditoria/confirmacao.md` e em `tests/bugs.spec.js`.

**Confirmados e corrigidos:**

| Id | Problema |
|---|---|
| L01 | Capa errada na apresentação e caixa que mudava de tamanho |
| L02 | Vídeos baixando antes do play |
| L03 | Camada transparente bloqueando os controles e dois vídeos tocando juntos |
| L04 | Comparador só funcionava na barrinha e não tinha nome acessível |
| L05 | Setas do carrossel iam para o card errado no iOS |
| L06 | Menu, cursos e botões sem estado |
| L07 | Horário escrito de dois jeitos e a categoria Corrente desalinhada |

**Achados da revisão de código, corrigidos:**
- 2 P1: foto apagada continuava pública; vídeo recusado só depois do envio inteiro.
- 7 de 9 P2: P2-3, P2-4, P2-5, P2-7, P2-8 e P2-10.

**Descartados:**
- os botões de resultados.html já tinham `type`;
- o `preload="metadata"` em si é permitido pelo orçamento; o problema real era o peso, que foi resolvido.

Os itens da revisão que não entraram estão na seção 8.

## 5. Telas (prints)

Os prints ficam fora do git, em `.auditoria/prints/`:
- `antes/`: o site antigo, em 2x;
- `depois/`: são 96 prints do site (desktop e celular, cada seção, cada comparador e cada vídeo antes do play) e mais os do painel:
  - `painel-*.png`;
  - `v-lista.png`: lista com Editar, Subir e Descer;
  - `v-editor-sobrepor.png`: Sobrepor ligado em 50%;
  - `v-confirmar-apagar.png`;
  - `v-nada-para-salvar.png`;
  - `v-editor-completo.png`.

Os "depois" foram tirados em 1x. Para comparar lado a lado com o mesmo zoom, vale tirar de novo em 2x (veja a seção 8).

## 6. Performance (celular, Lighthouse) comparada ao orçamento

| Métrica | Limite | Home antes → depois | Resultados antes → depois |
|---|---|---|---|
| Performance | ≥ 90 | 91 → **97** | 31 → **98** |
| Acessibilidade | ≥ 90 | 87 → **100** | 85 → **100** |
| SEO | ≥ 95 | 100 → 100 | 100 → 100 |
| LCP | ≤ 2,5 s | 3,5 s → **2,49 s** | 6,6 s → **2,42 s** |
| Peso da 1ª carga | ≤ 1,5 MB | 436 KB → **266 KB** | 4.834 KB → **714 KB** |
| Bytes de mp4 sem play | — | — → **0** | 4,7 MB → **0** |

**Nenhum estouro de orçamento.**
- O JS do site público tem 6,6 KB; o do painel, 95 KB (limite 150 KB).
- O LCP da home está no limite, com folga de 0,007 s, e foi medido em localhost. É preciso conferir na Hostinger.
- Detalhes em `.auditoria/performance-depois.md`.

## 7. Testes automáticos

- `php ferramentas/testar-nucleo.php`: 163 verificações, todas certas.
- `npx playwright test`: **438 passaram, 42 pulados, 0 falhas.**
  - Roda em desktop, iPhone WebKit e iPhone Chromium.
  - Os pulados são testes que só valem para um dos aparelhos.
  - Inclui o caminho completo com envio de verdade: enviar foto ou vídeo, salvar, aparecer no site e Desfazer tirar.
  - Também confere que a capa é o quadro 0, inclusive num vídeo 1080 em pé.

## 8. O que ficou pendente e por quê

1. **Teste num iPhone de verdade.** O WebKit no Windows não toca H.264, então o comportamento real do Safari só se confirma no aparelho.
2. **Restaurar da lixeira** não devolve a foto ao site sozinho, e envios nunca usados ficam no servidor (P2-6 e P2-9). São raros e não expõem nada; ficaram para uma próxima rodada.
3. **Conferências que só dão para fazer no servidor da Hostinger:**
   - se o LiteSpeed respeita as regras do `.htaccess` que bloqueiam PHP nos uploads;
   - a limpeza automática das sessões;
   - o IP real do visitante atrás da CDN.
4. **Ajustes visuais menores da revisão:**
   - V13: fotos do comparador em 1200 px com `srcset` e a capa do ultrassom maior;
   - V14: a marca d'água "GS" cruza o texto do topo no desktop;
   - V15/V16: mais prints do painel e prints em 2x/3x.
5. **`ferramentas/verificar-migracao`** ficou obsoleto depois das correções. Ele mostra 6 "falhas" esperadas, porque o site agora é diferente do HTML antigo de propósito.

## 9. Sugestões que precisam do Vinicius

- **Aprovar a prévia** antes de qualquer publicação.
- **Criar um subdomínio de teste** (por exemplo `teste.studiogabrielasantos.com.br`) e publicar lá primeiro.
  - Fazer ali o teste obrigatório do `PUBLICACAO.md` (R3): o conteúdo da dona tem que sobreviver a uma nova publicação.
  - Repetir o Lighthouse ali.
- **Criar a senha real da dona** com `php ferramentas/criar-senha.php --saida ...`, fora do public_html, como diz o `PUBLICACAO.md` (R2). A senha de teste local não serve para o site no ar.
- **Testar no iPhone dela:**
  - entrar no painel;
  - enviar um vídeo gravado na câmera;
  - ver se a capa aparece antes do play.
- **Decidir:**
  - se liga o HSTS no domínio;
  - o que fazer com os arquivos da pasta `acervo/`, que não são usados no site;
  - o vídeo `corrente-3.mp4`, também sem uso.
- **Opcional:** uma rodada de "embelezamento" só de planejamento com um modelo maior, como já conversado.
- **Revisar os textos de ajuda do painel** com a própria dona, para ver se ela entende sozinha.

## 10. Como testar (passo a passo)

1. No terminal, na pasta do projeto: `PORTA=8795 npm run previa`. Não use a 8765, que é de outro projeto.
2. **Site:**
   - abra `http://localhost:8795/` e `http://localhost:8795/resultados.html`;
   - confira se os vídeos mostram a capa antes do play e se o comparador arrasta.
3. **Painel:**
   - abra `http://localhost:8795/painel/`;
   - a senha de teste está em `.local/senha-teste.txt`.
4. **No painel:**
   - entre em "Antes e depois e vídeos";
   - toque em Editar num item;
   - teste "Sobrepor a outra foto", arraste, desfaça;
   - toque em "Salvar no site" e veja a mudança no site.
5. **Apagar:**
   - Editar → Apagar;
   - aparece "1 item será apagado";
   - Salvar no site;
   - depois teste "Desfazer última alteração".
6. **Testes automáticos:** `PORTA=8796 npx playwright test`. Leva uns 11 minutos.

## 11. Modelos usados por fase

O documento de regras pedia o Fable 5.2 para planejamento e revisão visual. **Por pedido do Vinicius, o Fable foi trocado pelo Opus 5.5 em todas as fases.**

| Fase | Modelo |
|---|---|
| Mapeamento, inventário e prints | Haiku |
| Auditorias técnica, lógica e de UI/UX | Opus 5.5 |
| Planejamento do painel e decisão de arquitetura | Opus 5.5 (no lugar do Fable) |
| Confirmação dos bugs com testes | Sonnet |
| Implementação (site, painel, testes) | Sonnet |
| Revisão do diff | Opus 5.5 |
| Revisão visual | Opus 5.5 (no lugar do Fable) |
| Correções da revisão e ajustes de UX | Sonnet |
| Coordenação e este relatório | Opus 5.5 |

## 12. O que a Fase 7 instalou e como usar

- **`.claude/agents/`:** seis agentes prontos, cada um com o modelo indicado:

  | Agente | Faz o quê |
  |---|---|
  | `explorador` | Busca e lê arquivos, roda ferramentas e tira prints |
  | `planejador` | Planeja mudanças grandes (Opus no lugar do Fable) |
  | `executor` | Implementa |
  | `revisor` | Revisa o diff |
  | `confirmador` | Reproduz bugs |
  | `auditor-ux` | Revisa telas |

- **`.claude/settings.json`:** o padrão do projeto passa a ser Sonnet com esforço médio, para gastar menos.
- **`CLAUDE.md`:** ganhou a seção "Fluxo de trabalho com agentes", com estas regras:
  - mudança pequena: faz direto;
  - mudança grande: planejador → executores → revisor;
  - bug: explorador → confirmador → executor → revisor.
- **Uso no dia a dia:** abrir o Claude Code na pasta e pedir normalmente. A sessão em Sonnet delega para os agentes conforme essas regras. Para um problema muito difícil, escrever "ultrathink" no pedido.
