// Renders the share images from resources/og/card.html: the static cards in resources/og/cards.json
// and one card per story and language. Run `npm run og` after adding or retitling a story.
// CHROME_PATH points at a local Chrome or Chromium binary.
import puppeteer from 'puppeteer-core';
import { mkdirSync, readFileSync, readdirSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const read = (file) => JSON.parse(readFileSync(path.join(root, file), 'utf8'));
const spec = read('resources/og/cards.json');
const languages = ['en', 'tr'];
const jobs = [];

for (const card of spec.cards) {
    for (const lang of languages) {
        jobs.push({
            out: `public/${card.file}${lang === 'en' ? '' : '-' + lang}.png`,
            card: { lang, kind: card.kind ?? 'title', number: card.number, label: card.label?.[lang], title: card.title?.[lang], subtitle: card.subtitle?.[lang], role: spec.role[lang] },
        });
    }
}

const kinds = { article: { en: 'Blog', tr: 'Blog' }, case_study: { en: 'Case study', tr: 'Case study' } };
const storyFiles = readdirSync(path.join(root, 'resources/content/stories')).filter((name) => name.endsWith('.json'));
const stories = storyFiles.flatMap((file) => read(`resources/content/stories/${file}`));
stories.push(read('resources/editorial/launch-story.json'));
for (const story of stories) {
    for (const lang of languages) {
            // Anonymous client work keeps its anonymous project label; Monafy is named.
            const project = (lang === 'en' ? story.case_meta?.project_name_en : null) ?? story.case_meta?.project_name ?? (story.project_key === 'monafy' ? 'Monafy' : null);
            jobs.push({
                out: `public/og/stories/${story.slug}-${lang}.png`,
                card: { lang, kind: 'title', label: [kinds[story.type][lang], project].filter(Boolean).join(' / '), title: story[lang].title, role: spec.role[lang] },
            });
    }
}

const browser = await puppeteer.launch({
    executablePath: process.env.CHROME_PATH ?? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
    headless: true,
    args: ['--allow-file-access-from-files'],
});
const page = await browser.newPage();
await page.setViewport({ width: 1200, height: 630, deviceScaleFactor: 1 });
await page.goto(pathToFileURL(path.join(root, 'resources/og/card.html')).href, { waitUntil: 'networkidle0' });
mkdirSync(path.join(root, 'public/og/stories'), { recursive: true });
for (const job of jobs) {
    await page.evaluate((card) => window.render(card), job.card);
    await page.screenshot({ path: path.join(root, job.out), type: 'png' });
}
await browser.close();
console.log(`rendered ${jobs.length} share images`);
