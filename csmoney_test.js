// const puppeteer = require('puppeteer');
//
// (async () => {
//   const browser = await puppeteer.launch({ headless: true });
//   const page = await browser.newPage();
//
//   await page.setUserAgent("Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36");
//
//   await page.goto("https://cs.money/2.0/market/sell-orders?limit=60&offset=0&type=21&name=die&order=asc&sort=price", {
//     waitUntil: 'networkidle2'
//   });
//
//   const content = await page.content();
//   console.log(content);
//
//   await browser.close();
// })();


const puppeteer = require('puppeteer');

(async () => {
  try {
    const browser = await puppeteer.launch({ headless: true });
    const page = await browser.newPage();
    await page.goto('https://example.com');
    const title = await page.title();
    console.log('✅ Заголовок страницы:', title);
    await browser.close();
  } catch (err) {
    console.error('❌ Ошибка при запуске Puppeteer:', err);
  }
})();
