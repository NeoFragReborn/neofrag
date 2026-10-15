// Le pilote des vignettes-cartes (tools/vignettes-cartes.php) : il ouvre chaque gabarit HTML
// (carte.html, rempli par l'outil) et l'enregistre en JPEG au format des vignettes. La page est dessinée à 1600 × 1000 et réduite à
// 960 × 600 : le texte reste net sur un écran à deux points par pixel, et le fichier reste léger.
//   node tests/Vignettes/pilote.mjs <cartes.json> <largeur> <hauteur>
import { chromium } from 'playwright';
import fs from 'node:fs';
import path from 'node:path';

const [liste, largeur, hauteur] = process.argv.slice(2);
const cartes = JSON.parse(fs.readFileSync(liste, 'utf8'));

// Le Chrome du système d'abord (serveur), celui de Playwright sinon (poste).
const candidats = [process.env.NF_CHROME, '/usr/bin/google-chrome', '/usr/bin/chromium',
  'C:/Program Files (x86)/Microsoft/EdgeCore/155.0.4283.45/msedge.exe'].filter(Boolean);
const chemin = candidats.find((c) => { try { return fs.existsSync(c); } catch { return false; } });

const nav = await chromium.launch(chemin ? { executablePath: chemin, args: ['--no-sandbox', '--disable-gpu'] }
                                         : { args: ['--no-sandbox', '--disable-gpu'] });
const ctx = await nav.newContext({ viewport: { width: 1600, height: 1000 }, deviceScaleFactor: 1 });
const page = await ctx.newPage();
let faites = 0;

for (const carte of cartes) {
  await page.goto('file://' + path.resolve(carte.html).replace(/\\/g, '/'), { waitUntil: 'load' });
  await page.evaluate(() => document.fonts.ready);
  // Le JPEG est enregistré à la taille de la page, puis RÉDUIT au format des vignettes : Playwright
  // ne redimensionne pas, c'est le navigateur qui le fait, en dessinant la page à l'échelle voulue.
  await page.setViewportSize({ width: Number(largeur), height: Number(hauteur) });
  await page.addStyleTag({ content: `body { zoom: ${Number(largeur) / 1600}; }` });
  await page.evaluate(() => document.fonts.ready);
  await page.screenshot({ path: carte.jpeg, type: 'jpeg', quality: 92 });
  faites++;
}

await nav.close();
console.log(`${faites} carte(s) dessinée(s)`);
