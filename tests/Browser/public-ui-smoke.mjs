// UI smoke test against built Vue assets and isolated HTTP fixtures; never opens the project database.
// Run npm run build, php artisan ziggy:generate storage/app/ui-ziggy.js, then node tests/Browser/public-ui-smoke.mjs.
import assert from 'node:assert/strict';
import { createServer } from 'node:http';
import { readFile, writeFile, mkdir } from 'node:fs/promises';
import { existsSync } from 'node:fs';
import { spawn } from 'node:child_process';
import { resolve, extname } from 'node:path';
import { pathToFileURL } from 'node:url';

const root = process.cwd();
const catalogMode = process.argv.includes('--catalog');
const output = resolve(root, 'storage/app/ui-review');
await mkdir(output, { recursive: true });
const manifest = JSON.parse(await readFile(resolve(root, 'public/build/manifest.json'), 'utf8'));
const entry = manifest['resources/js/app.js'];
const { Ziggy } = await import(pathToFileURL(resolve(root, 'storage/app/ui-ziggy.js')).href);
const event = {
    id: 1, slug: 'ui-test', title: 'Workshop Pengujian Antarmuka', category: 'Workshop',
    banner_url: '/test-poster.svg', description: 'Deskripsi dari data event pengujian.\nInformasi jadwal dan kegiatan peserta.',
    terms: 'Bawa identitas saat check-in.', organizer_name: 'Penyelenggara Pengujian',
    start_at: '2026-11-14T09:00:00Z', end_at: '2026-11-14T12:00:00Z', location_name: 'Gedung Event',
    location_address: 'Karawang, Jawa Barat', contact_email: 'organizer@example.test', contact_phone: '0800000000',
    ticket_types: [
        { id: 1, name: 'Regular Entry', price: '150000.00', remaining_quota: 3 },
        { id: 2, name: 'Early Bird', price: '125000.00', remaining_quota: 0 },
    ],
};
const checkout = { order_number: 'ORD-UI-TEST', customer_email: 'buyer@example.test' };
const catalogFixtures = [
    { ...event, id: 101, slug: 'ongoing-test', title: 'Workshop Berlangsung — Data Pengujian', location_name: 'Bandung', schedule_status: 'ongoing', start_at: new Date(Date.now() - 3600000).toISOString() },
    ...Array.from({ length: 14 }, (_, index) => ({ ...event, id: 102 + index, slug: 'catalog-test-' + index, title: 'Event Mendatang Pengujian ' + (index + 1), category: index % 2 ? 'Musik' : 'Workshop', location_name: index % 2 ? 'Jakarta' : 'Bandung', schedule_status: 'upcoming', start_at: new Date(Date.now() + (index + 1) * 86400000).toISOString() })),
    { ...event, id: 199, slug: 'ended-test', title: 'Event Selesai Pengujian', schedule_status: 'ended', location_name: 'Jakarta' },
];

function catalogPage(url) {
    const fixtures = url.searchParams.get('fixture') === 'empty' ? [] : catalogFixtures;
    const filters = Object.fromEntries(['search', 'category', 'location', 'schedule'].map((key) => [key, url.searchParams.get(key) || (key === 'schedule' ? 'all' : '')]));
    const matches = fixtures.filter((item) => (!filters.search || (item.title + ' ' + item.location_name).toLowerCase().includes(filters.search.toLowerCase())) && (!filters.category || item.category === filters.category) && (!filters.location || item.location_name === filters.location));
    const counts = { all: matches.length, ongoing: matches.filter((item) => item.schedule_status === 'ongoing').length, upcoming: matches.filter((item) => item.schedule_status === 'upcoming').length };
    const selected = matches.filter((item) => filters.schedule === 'all' || item.schedule_status === filters.schedule);
    const page = Number(url.searchParams.get('page') || 1);
    const last = Math.max(1, Math.ceil(selected.length / 12));
    const pageUrl = (number) => { const params = new URLSearchParams(url.searchParams); params.set('page', number); return '/events?' + params; };
    const discover = !filters.search && !filters.category && !filters.location && filters.schedule === 'all' && page === 1;
    const categories = [...new Set(fixtures.map((item) => item.category))].sort().map((name) => ({ name, count: fixtures.filter((item) => item.category === name).length }));
    return {
        filters, counts, categories, locations: [...new Set(fixtures.map((item) => item.location_name))].sort(),
        events: { data: selected.slice((page - 1) * 12, page * 12), total: selected.length, current_page: page, last_page: last, prev_page_url: page > 1 ? pageUrl(page - 1) : null, next_page_url: page < last ? pageUrl(page + 1) : null, links: Array.from({ length: last }, (_, index) => ({ label: String(index + 1), active: index + 1 === page, url: pageUrl(index + 1) })) },
        featuredEvents: discover ? fixtures.filter((item) => item.schedule_status !== 'ended').slice(0, 3) : [],
        ongoingEvents: discover ? fixtures.filter((item) => item.schedule_status === 'ongoing').slice(0, 4) : [],
        upcomingEvents: discover ? fixtures.filter((item) => item.schedule_status === 'upcoming').slice(0, 4) : [],
    };
}
const order = {
    id: 1, ...checkout, event, total_amount: '450000.00', order_status: 'pending', payment_status: 'pending',
    expires_at: new Date(Date.now() + 1800000).toISOString(),
    payment: { method: 'bank_transfer', amount: '450000.00', status: 'pending', proof_uploaded_at: null },
    payment_options: { methods: [{ id: 'bank_transfer', label: 'Bank transfer' }], bank_accounts: [
        { bank_name: 'BANK PENGUJIAN', account_number: '0000000000', account_holder: 'REKENING PENGUJIAN' },
    ] },
};
let formErrors = {};
let created = false;
let selectedItems = [{ ticket_type_id: 1, quantity: 3 }];
let lastPayload;
const json = (response, body, status = 200, headers = {}) => {
    response.writeHead(status, { 'Content-Type': 'application/json', ...headers });
    response.end(JSON.stringify(body));
};
const server = createServer(async (request, response) => {
    try {
        const url = new URL(request.url, 'http://localhost');
        const chunks = [];
        for await (const chunk of request) chunks.push(chunk);
        const rawBody = Buffer.concat(chunks).toString();
        if (url.pathname === '/favicon.ico') { response.writeHead(204); response.end(); return; }
        if (url.pathname === '/test-poster.svg') {
            response.writeHead(200, { 'Content-Type': 'image/svg+xml' });
            response.end('<svg xmlns="http://www.w3.org/2000/svg" width="720" height="360"><rect width="720" height="360" fill="#102c57"/><circle cx="590" cy="110" r="150" fill="#e8bd65"/><path d="M0 360 280 60 490 360Z" fill="#a73548"/><text x="35" y="230" fill="white" font-size="42" font-family="sans-serif">POSTER PENGUJIAN</text></svg>');
            return;
        }
        if (url.pathname.startsWith('/build/') || url.pathname === '/images/arc-logo.png' || url.pathname === '/test-ziggy.js') {
            const path = url.pathname === '/test-ziggy.js' ? resolve(root, 'vendor/tightenco/ziggy/dist/index.js') : resolve(root, 'public', '.' + url.pathname);
            assert(path.startsWith(root));
            const types = { '.js': 'text/javascript', '.css': 'text/css', '.png': 'image/png' };
            response.writeHead(200, { 'Content-Type': types[extname(path)] || 'application/octet-stream' });
            response.end(await readFile(path)); return;
        }
        if (url.pathname === '/api/orders/status') { json(response, { data: order }); return; }
        if (url.pathname === '/api/orders/1/payments/manual') { json(response, { data: order.payment }, 201); return; }
        if (url.pathname === '/api/orders/1/payments/manual/proof') {
            assert(rawBody.includes('buyer@example.test'));
            order.payment.proof_uploaded_at = new Date().toISOString();
            json(response, { data: order.payment }); return;
        }
        if (request.method === 'POST' && url.pathname.endsWith('/data-diri')) {
            lastPayload = JSON.parse(rawBody);
            if (lastPayload.customer_name.trim() === '') {
                formErrors = { customer_name: 'Nama lengkap wajib diisi.' };
                response.writeHead(303, { Location: '/events/ui-test/data-diri' }); response.end(); return;
            }
            assert.equal(lastPayload.event_id, 1);
            assert.equal(lastPayload.attendees.length, 3);
            assert.equal(lastPayload.items[0].quantity, 3);
            assert.equal(lastPayload.attendees[0].metadata.profesi, 'Designer');
            assert(!('total_amount' in lastPayload));
            formErrors = {}; created = true;
            response.writeHead(303, { Location: '/payment' }); response.end(); return;
        }
        let component;
        let props = { auth: { user: null }, errors: formErrors };
        if (url.pathname === '/events') component = 'Public/Events', props = { ...props, ...catalogPage(url), auth: { user: url.searchParams.has('auth') ? { name: 'Test Organizer' } : null } };
        else if (url.pathname.endsWith('/checkout')) component = 'Public/Checkout', props = { ...props, event, quantities: {} };
        else if (url.pathname.endsWith('/data-diri')) {
            component = 'Public/DataDiri';
            const quantity = Number(url.searchParams.get('qty[1]'));
            if (quantity) selectedItems = [{ ticket_type_id: 1, quantity }];
            props = { ...props, event, items: selectedItems };
        } else if (url.pathname === '/payment' || url.pathname === '/order-status') {
            component = 'Public/OrderStatus'; props = { ...props, checkout: created ? checkout : null, mode: url.pathname === '/payment' ? 'payment' : 'status' };
        } else component = 'Public/EventDetail', props = { ...props, event };
        const page = { component, props, url: request.url, version: 'ui-test', clearHistory: false, encryptHistory: false };
        if (request.headers['x-inertia']) { json(response, page, 200, { 'X-Inertia': 'true' }); return; }
        const ziggy = { ...Ziggy, url: base, port: Number(new URL(base).port) };
        response.writeHead(200, { 'Content-Type': 'text/html' });
        response.end(`<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">${entry.css.map((css) => `<link rel="stylesheet" href="/build/${css}">`).join('')}</head><body><div id="app" data-page="${JSON.stringify(page).replaceAll('&', '&amp;').replaceAll('"', '&quot;')}"></div><script type="module">import {route} from '/test-ziggy.js';window.Ziggy=${JSON.stringify(ziggy)};window.route=route;await import('/build/${entry.file}');</script></body></html>`);
    } catch (error) { json(response, { message: error.message }, 500); }
});
await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
const base = `http://127.0.0.1:${server.address().port}`;
const browserPaths = [process.env.TICKET_UI_BROWSER, 'C:/Program Files (x86)/Microsoft/EdgeCore/156.0.4314.42/msedge.exe', 'C:/Program Files/Google/Chrome/Application/chrome.exe', '/usr/bin/chromium'].filter(Boolean);
const executable = browserPaths.find(existsSync);
assert(executable, 'Set TICKET_UI_BROWSER to an installed Chromium browser.');
const browser = spawn(executable, ['--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check', '--remote-debugging-port=0', '--remote-debugging-address=127.0.0.1', '--user-data-dir=' + resolve(output, 'browser-profile'), 'about:blank'], { windowsHide: true, stdio: ['ignore', 'ignore', 'pipe'] });
let websocket;
let nextId = 0;
const pending = new Map();
const errors = [];
try {
    const debugAddress = await new Promise((resolve, reject) => {
        let log = '';
        const timer = setTimeout(() => reject(new Error('Browser startup timed out: ' + log.slice(-500))), 15000);
        browser.stderr.on('data', (chunk) => { log += chunk; const match = log.match(/DevTools listening on (ws:\/\/[^\s]+)/); if (match) { clearTimeout(timer); resolve(match[1]); } });
        browser.on('error', reject);
    });
    const debugUrl = new URL(debugAddress);
    const targets = await (await fetch(`http://${debugUrl.host}/json/list`, { signal: AbortSignal.timeout(5000) })).json();
    websocket = new WebSocket(targets.find((target) => target.type === 'page').webSocketDebuggerUrl);
    await new Promise((resolve, reject) => {
        const timer = setTimeout(() => reject(new Error('Browser WebSocket connection timed out')), 5000);
        websocket.addEventListener('open', () => { clearTimeout(timer); resolve(); }, { once: true });
        websocket.addEventListener('error', () => { clearTimeout(timer); reject(new Error('Browser WebSocket connection failed')); }, { once: true });
    });
    websocket.addEventListener('message', (event) => {
        const message = JSON.parse(event.data);
        if (message.id) { const handler = pending.get(message.id); pending.delete(message.id); message.error ? handler.reject(new Error(message.error.message)) : handler.resolve(message.result); }
        if (message.method === 'Runtime.exceptionThrown') errors.push(message.params.exceptionDetails.exception?.description || message.params.exceptionDetails.text);
    });
    const send = (method, params = {}) => new Promise((resolve, reject) => {
        const id = ++nextId;
        const timer = setTimeout(() => { pending.delete(id); reject(new Error('Browser command timed out: ' + method)); }, 5000);
        pending.set(id, { resolve: (result) => { clearTimeout(timer); resolve(result); }, reject: (error) => { clearTimeout(timer); reject(error); } });
        websocket.send(JSON.stringify({ id, method, params }));
    });
    const evaluate = async (expression) => { const result = await send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true }); if (result.exceptionDetails) throw new Error((result.exceptionDetails.exception?.description || result.exceptionDetails.text) + '\nExpression: ' + expression); return result.result.value; };
    const until = async (expression) => {
        for (let attempt = 0; attempt < 100; attempt++) { if (await evaluate(expression)) return; await new Promise((resolve) => setTimeout(resolve, 50)); }
        throw new Error('UI did not reach expected state: ' + expression + '; exceptions: ' + errors.join('\n'));
    };
    const viewport = (width) => send('Emulation.setDeviceMetricsOverride', { width, height: 844, deviceScaleFactor: 1, mobile: width < 768 });
    const clickText = (text) => evaluate(`Array.from(document.querySelectorAll('button,a')).find(el=>{const clone=el.cloneNode(true);clone.querySelectorAll('[aria-hidden="true"]').forEach(node=>node.remove());return clone.textContent.trim()===${JSON.stringify(text)}}).click()`);
    const labeledButton = (label) => `Array.from(document.querySelectorAll('button')).find(el=>el.getAttribute('aria-label')===${JSON.stringify(label)})`;
    const screenshot = async (name, top = true) => {
        if (top) await evaluate('window.scrollTo({top:0,behavior:"instant"})');
        await evaluate('new Promise(resolve=>requestAnimationFrame(()=>requestAnimationFrame(resolve)))');
        assert.equal(await evaluate('Math.round(document.querySelector("header").getBoundingClientRect().top)'), 0, 'Header should stay at the top');
        const shot = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false });
        await writeFile(resolve(output, name + '.png'), Buffer.from(shot.data, 'base64'));
    };
    const responsive = async () => {
        for (const width of [320, 390, 768, 1280]) {
            await viewport(width);
            await new Promise((resolve) => setTimeout(resolve, 50));
            assert(await evaluate('document.documentElement.scrollWidth <= window.innerWidth'), 'Horizontal overflow at width ' + width);
        }
        await viewport(390);
    };
    await send('Runtime.enable'); await send('Page.enable'); await viewport(390);
    if (catalogMode) {
        await send('Page.navigate', { url: base + '/events' });
        await until('Boolean(document.querySelector("#catalog-search"))');
        await responsive(); await screenshot('homepage-mobile');
        await viewport(1280); await screenshot('homepage-desktop');
        await evaluate('document.querySelector("#catalog").scrollIntoView({block:"start",behavior:"instant"})');
        await screenshot('homepage-catalog-desktop', false);
        assert.equal(await evaluate('document.querySelector("nav").textContent.includes("Beranda")'), true);
        await evaluate(`${labeledButton('Event berikutnya')}.click()`);
        await until('document.querySelector("section[aria-roledescription=carousel]").textContent.includes("Event Mendatang Pengujian 1")');
        await evaluate(`${labeledButton('Event sebelumnya')}.click()`);
        await viewport(390);
        await evaluate('document.querySelector("button[aria-controls=public-menu]").click()');
        await until('Boolean(document.querySelector("#public-menu"))');
        await evaluate('window.__menuVisitFinished=false;document.addEventListener("inertia:finish",()=>window.__menuVisitFinished=true,{once:true})');
        await evaluate('document.querySelector("#public-menu a").click()');
        await until('window.__menuVisitFinished && !document.querySelector("#public-menu") && Boolean(document.querySelector("#catalog-search"))');
        await evaluate('(()=>{const field=document.querySelector("#catalog-search");field.value="zz-no-event";field.dispatchEvent(new Event("input",{bubbles:true}));})()');
        await clickText('Cari Event');
        await until('document.body.textContent.includes("Belum menemukan event yang cocok?")');
        await responsive(); await screenshot('homepage-empty-search');
        await clickText('Lihat semua event');
        await until('document.querySelector("#catalog-search").value === "" && document.body.textContent.includes("Event Sedang Berlangsung")');
        await evaluate('(()=>{const category=document.querySelector("#catalog-category");category.value="Musik";category.dispatchEvent(new Event("change",{bubbles:true}));})()');
        await until('new URL(location.href).searchParams.get("category")==="Musik"');
        assert(await evaluate('Array.from(document.querySelectorAll("article h3")).every(el=>el.textContent.includes("Event Mendatang"))'));
        await evaluate('(()=>{const locationField=document.querySelector("#catalog-location");locationField.value="Jakarta";locationField.dispatchEvent(new Event("change",{bubbles:true}));})()');
        await until('new URL(location.href).searchParams.get("location")==="Jakarta"');
        assert.equal(await evaluate('document.querySelector("#catalog-category").value'), 'Musik');
        await clickText('Reset filter');
        await until('document.querySelector("#catalog-category").value === "" && document.body.textContent.includes("Event Sedang Berlangsung")');
        await evaluate(`document.querySelector('a[aria-label="Halaman berikutnya"]').click()`);
        await until('new URL(location.href).searchParams.get("page")==="2"');
        assert.equal(await evaluate('document.querySelectorAll("article").length'), 4);
        await send('Page.navigate', { url: base + '/events?fixture=empty' });
        await until('document.body.textContent.includes("Nantikan event berikutnya.")');
        assert.equal(await evaluate('document.querySelectorAll("article").length'), 0);
        await responsive(); await screenshot('homepage-no-events');
        await send('Page.navigate', { url: base + '/events?auth=1' });
        await until('document.body.textContent.includes("Dashboard")');
        assert.equal(errors.length, 0, errors.join('\n'));
        console.log(JSON.stringify({ result: 'passed', mode: 'catalog', widths: [320, 390, 768, 1280], checks: ['ARC header/auth navigation', 'mobile menu', 'featured carousel', 'search empty state', 'category/location combined filters', 'filter reset', 'pagination', 'no-event fallback', 'no horizontal overflow', 'no browser exceptions'], screenshots: output }));
    } else {
    await send('Page.navigate', { url: base + '/events/ui-test' });
    await until("Boolean(document.querySelector('.public-site'))");
    assert.equal(await evaluate('document.querySelectorAll("header").length'), 1);
    await until('document.querySelector("header img").naturalWidth > 0');
    assert.equal(await evaluate('getComputedStyle(document.querySelector("header")).backgroundColor'), 'rgb(227, 243, 255)');
    await evaluate('document.querySelector("button[aria-controls=public-menu]").click()');
    await until('document.querySelector("button[aria-controls=public-menu]").getAttribute("aria-expanded")==="true"');
    await evaluate('document.querySelector("button[aria-controls=public-menu]").click()');
    await clickText('Syarat & Ketentuan');
    await until('document.querySelector("#event-panel").textContent.includes("Bawa identitas")');
    await clickText('Deskripsi'); await responsive(); await screenshot('event-mobile');
    await viewport(1280); await screenshot('event-desktop'); await viewport(390);
    await clickText('Pesan Sekarang');
    await until(`Boolean(${labeledButton('Pilih Regular Entry')})`);
    await evaluate(`${labeledButton('Pilih Regular Entry')}.click()`);
    await until(`Boolean(${labeledButton('Tambah Regular Entry')})`);
    await evaluate(`${labeledButton('Tambah Regular Entry')}.click()`);
    await evaluate(`${labeledButton('Tambah Regular Entry')}.click()`);
    await until(`${labeledButton('Tambah Regular Entry')}.disabled`);
    assert(!(await evaluate(`Boolean(${labeledButton('Pilih Early Bird')})`)));
    assert(await evaluate('document.querySelector(".arc-dock").textContent.includes("450.000")'));
    await responsive(); await screenshot('checkout-mobile'); await clickText('Beli');
    await until('Boolean(document.querySelector("#buyer-nama"))'); await responsive(); await screenshot('data-mobile');
    await evaluate(`(() => { const values = { nama:' ', wa:'08123456789', email:'buyer@example.test', profesi:'Designer', brand:'Usaha Pengujian', domisili:'Karawang' }; for (const [key,value] of Object.entries(values)) { const input=document.querySelector('#buyer-'+key); input.value=value; input.dispatchEvent(new Event('input',{bubbles:true})); } })()`);
    await clickText('Submit'); await until('Boolean(document.querySelector("#nama-error"))');
    assert.equal(await evaluate('document.querySelector("#buyer-email").value'), 'buyer@example.test');
    await evaluate('const input=document.querySelector("#buyer-nama");input.value="Pembeli Pengujian";input.dispatchEvent(new Event("input",{bubbles:true}))');
    await clickText('Submit'); await until('location.pathname==="/payment" && Boolean(document.querySelector("button")) && document.body.textContent.includes("Bank transfer")');
    await responsive(); await screenshot('payment-method-mobile'); await clickText('Pilih');
    await until('Boolean(document.querySelector("#payment-proof"))'); await responsive(); await screenshot('payment-mobile');
    assert(await evaluate('document.body.textContent.includes("0000000000")'));
    await evaluate(`(() => { const transfer=new DataTransfer();transfer.items.add(new File(['invalid'],'proof.txt',{type:'text/plain'})); const input=document.querySelector('#payment-proof');input.files=transfer.files;input.dispatchEvent(new Event('change',{bubbles:true})); })()`);
    await until('document.querySelector("#proof-help").textContent.includes("Gunakan file")');
    await evaluate(`(() => { const transfer=new DataTransfer();transfer.items.add(new File(['proof'],'proof.png',{type:'image/png'})); const input=document.querySelector('#payment-proof');input.files=transfer.files;input.dispatchEvent(new Event('change',{bubbles:true})); })()`);
    await clickText('Upload bukti pembayaran');
    await until('Boolean(document.querySelector("[role=status]")) && document.body.textContent.includes("Bukti pembayaran diterima")');
    assert.equal(order.payment_status, 'pending');
    await clickText('Cek status order');
    await until('location.pathname==="/order-status" && document.body.textContent.includes("Menunggu verifikasi organizer")');
    await responsive(); await screenshot('status-mobile');
    assert.equal(errors.length, 0, errors.join('\n'));
    console.log(JSON.stringify({ result: 'passed', widths: [320, 390, 768, 1280], checks: ['logo/header', 'hamburger', 'tabs', 'stock limits', 'reactive totals', 'buyer validation retention', 'checkout payload', 'bank account display', 'file validation', 'proof upload pending verification', 'status navigation', 'no horizontal overflow', 'no browser exceptions'], screenshots: output }));
    }
    await send('Browser.close').catch(() => {});
} finally {
    websocket?.close(); browser.kill(); server.close();
}
