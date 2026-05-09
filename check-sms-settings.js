const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.connectOverCDP('http://localhost:9222');
  const context = browser.contexts()[0];
  const page = context.pages()[0];
  
  // Click Edit on SMS Notification
  const editBtn = await page.$('table tbody tr a:has-text("Edit"), table tbody tr .dropdown-item:first-child');
  if (editBtn) {
    await editBtn.click();
    await page.waitForTimeout(3000);
  } else {
    // Try clicking the row
    await page.click('table tbody tr');
    await page.waitForTimeout(3000);
  }
  
  const url = page.url();
  const title = await page.title();
  console.log('URL:', url);
  console.log('Title:', title);
  
  // Get all form elements
  const content = await page.evaluate(() => {
    return {
      title: document.title,
      heading: document.querySelector('h1,h2,.page-title')?.innerText || 'no heading',
      breadcrumb: Array.from(document.querySelectorAll('.breadcrumb-item')).map(b => b.innerText),
      inputs: Array.from(document.querySelectorAll('input,textarea,select')).map(i => ({
        type: i.type || i.tagName.toLowerCase(),
        name: i.name,
        placeholder: i.placeholder || '',
        value: i.value || '',
        id: i.id
      })),
      labels: Array.from(document.querySelectorAll('label')).map(l => l.innerText.trim()),
      buttons: Array.from(document.querySelectorAll('button')).map(b => b.innerText.trim()).filter(t => t)
    };
  });
  console.log('Content:', JSON.stringify(content, null, 2));
  
  await page.screenshot({ path: '/home/Sam/Desktop/PipraPay/screenshots/sms-settings.png', fullPage: false });
  console.log('Screenshot saved');
  
  await browser.close();
})().catch(e => { console.error(e); process.exit(1); });
