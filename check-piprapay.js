const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.connectOverCDP('http://localhost:9222');
  const context = browser.contexts()[0];
  const page = context.pages()[0] || await context.newPage();
  
  console.log('Connected! Current URL:', page.url());
  
  await page.goto('http://144.79.133.224:8082/login', { waitUntil: 'networkidle', timeout: 15000 });
  console.log('Navigated to login page');
  
  const title = await page.title();
  console.log('Title:', title);
  
  // Take screenshot
  await page.screenshot({ path: '/home/Sam/Desktop/PipraPay/screenshots/login.png', fullPage: false });
  console.log('Screenshot saved');
  
  // Get page content
  const content = await page.evaluate(() => {
    return {
      title: document.title,
      heading: document.querySelector('h1,h2,.page-title,h3')?.innerText || 'no heading',
      inputs: Array.from(document.querySelectorAll('input')).map(i => ({ type: i.type, name: i.name, placeholder: i.placeholder })),
      buttons: Array.from(document.querySelectorAll('button,input[type=submit]')).map(b => b.innerText || b.value)
    };
  });
  console.log('Page content:', JSON.stringify(content, null, 2));
  
  await browser.close();
})().catch(e => { console.error(e); process.exit(1); });
