const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.connectOverCDP('http://localhost:9222');
  const context = browser.contexts()[0];
  const page = context.pages()[0];
  
  // Navigate to addons
  await page.goto('http://144.79.133.224:8082/admin/addons', { waitUntil: 'networkidle', timeout: 15000 });
  await page.waitForTimeout(2000);
  
  const title = await page.title();
  console.log('Page title:', title);
  
  // Get page content
  const content = await page.evaluate(() => {
    return {
      title: document.title,
      heading: document.querySelector('h1,h2,.page-title')?.innerText || 'no heading',
      tableRows: Array.from(document.querySelectorAll('table tbody tr')).map(r => r.innerText),
      sidebar: Array.from(document.querySelectorAll('.sidebar a, .nav-link')).map(a => ({ text: a.innerText.trim(), href: a.href })).filter(a => a.text)
    };
  });
  console.log('Content:', JSON.stringify(content, null, 2));
  
  await page.screenshot({ path: '/home/Sam/Desktop/PipraPay/screenshots/addons.png', fullPage: false });
  console.log('Screenshot saved');
  
  await browser.close();
})().catch(e => { console.error(e); process.exit(1); });
