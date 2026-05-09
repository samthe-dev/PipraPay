const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.connectOverCDP('http://localhost:9222');
  const context = browser.contexts()[0];
  const page = context.pages()[0];
  
  // Fill login form
  await page.fill('input[name="username"]', 'sammiahmedsam@gmail.com');
  await page.fill('input[name="password"]', 'sam123');
  await page.click('button:has-text("Sign in")');
  
  // Wait for navigation
  await page.waitForTimeout(3000);
  
  const url = page.url();
  const title = await page.title();
  console.log('After login URL:', url);
  console.log('After login title:', title);
  
  await page.screenshot({ path: '/home/Sam/Desktop/PipraPay/screenshots/after-login.png', fullPage: false });
  console.log('Screenshot saved');
  
  await browser.close();
})().catch(e => { console.error(e); process.exit(1); });
