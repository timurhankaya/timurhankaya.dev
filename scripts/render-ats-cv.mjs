// Generate private, selectable-text application PDFs from the public ATS views.
import puppeteer from 'puppeteer-core';
import { mkdir, readFile, writeFile, chmod } from 'node:fs/promises';
import { createHash } from 'node:crypto';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const base = new URL(process.argv.find((arg) => arg.startsWith('--base-url='))?.slice(11) ?? 'https://timurhankaya.dev');
const output = path.join(root, 'storage/app/private/career/resumes');
await mkdir(output, { recursive: true, mode: 0o700 });
const browser = await puppeteer.launch({
    executablePath: process.env.CHROME_PATH ?? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
    headless: true,
});
const manifest = { generated_at: new Date().toISOString(), resumes: {} };

try {
    for (const [locale, route] of Object.entries({ en: '/cv?ats=1', tr: '/tr/ozgecmis?ats=1' })) {
        const page = await browser.newPage();
        const url = new URL(route, base).href;
        const response = await page.goto(url, { waitUntil: 'networkidle0', timeout: 20000 });
        if (response?.status() !== 200) {
            throw new Error('ATS page did not return a usable response: ' + locale);
        }
        const cv = await page.$('#ats-cv');
        if (!cv || await page.$eval('html', (element) => element.lang) !== locale) {
            throw new Error('ATS content or language is missing: ' + locale);
        }
        const text = await page.$eval('#ats-cv', (element) => element.innerText);
        const file = path.join(output, 'timurhan-kaya-ats-' + locale + '.pdf');
        await page.pdf({ path: file, format: 'A4', preferCSSPageSize: true, tagged: true, displayHeaderFooter: false });
        await chmod(file, 0o600);
        manifest.resumes[locale] = {
            url, file: path.relative(root, file),
            text_sha256: createHash('sha256').update(text).digest('hex'),
            pdf_sha256: createHash('sha256').update(await readFile(file)).digest('hex'),
        };
        await page.close();
    }
    const file = path.join(output, 'manifest.json');
    await writeFile(file, JSON.stringify(manifest, null, 2) + '\n', { mode: 0o600 });
    console.log('Created English and Turkish ATS PDFs in storage/app/private/career/resumes');
} finally {
    await browser.close();
}
