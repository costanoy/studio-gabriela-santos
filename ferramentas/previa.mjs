// Sobe a prévia local: PHP embutido + roteador. Porta pela variável PORTA (padrão 8765).
import { spawn, execFileSync } from 'node:child_process';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const raiz = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const porta = process.env.PORTA || '8765';

function pastaDoPhp() {
  const dobro = process.platform === 'win32' ? 'where' : 'which';
  const caminho = execFileSync(dobro, ['php'], { encoding: 'utf8' }).split(/\r?\n/)[0].trim();
  const pasta = dirname(caminho);
  return pasta;
}

const pasta = pastaDoPhp();
const args = [
  '-d', `extension_dir=${join(pasta, 'ext')}`,
  '-d', 'extension=gd',
  '-d', 'extension=exif',
  '-d', 'upload_max_filesize=8M',
  '-d', 'post_max_size=12M',
  '-d', 'memory_limit=256M',
  '-S', `0.0.0.0:${porta}`,
  join(raiz, 'ferramentas', 'roteador.php'),
];

console.log(`Prévia em http://localhost:${porta}/`);
const filho = spawn('php', args, { cwd: raiz, stdio: 'inherit' });
filho.on('exit', (codigo) => process.exit(codigo ?? 0));
for (const sinal of ['SIGINT', 'SIGTERM']) {
  process.on(sinal, () => filho.kill());
}
