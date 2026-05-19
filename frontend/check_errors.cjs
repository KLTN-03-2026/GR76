const puppeteer = require('puppeteer');

(async () => {
  const browser = await puppeteer.launch();
  const page = await browser.newPage();
  
  page.on('console', msg => console.log('PAGE LOG:', msg.text()));
  page.on('pageerror', err => console.log('PAGE ERROR:', err.toString()));
  
  await page.setRequestInterception(true);
  page.on('request', request => {
    if (request.url().includes('/api/auth/me')) {
      request.respond({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: { id: 1, name: 'Test User', vai_tro: 'nguoi_dung' } })
      });
    } else if (request.url().includes('/api/')) {
      request.respond({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: [] })
      });
    } else {
      request.continue();
    }
  });

  await page.goto('http://localhost:5173/login', { waitUntil: 'networkidle2' });
  
  await page.evaluate(() => {
    localStorage.setItem('sos_user', JSON.stringify({ id: 1, name: 'Test', vai_tro: 'nguoi_dung' }));
    localStorage.setItem('sos_token', 'fake_token');
  });
  
  console.log('Navigating to /home...');
  await page.goto('http://localhost:5173/home', { waitUntil: 'networkidle2' });
  
  console.log('Final URL after /home:', page.url());
  
  await page.screenshot({ path: 'screenshot_normal_home.png' });
  
  await browser.close();
})();
