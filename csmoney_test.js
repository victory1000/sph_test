const puppeteer = require('puppeteer');

(async () => {
  try {
    const url = "https://lis-skins.com/ru/market/csgo/fracture-case/?sort_by=price_asc";
    // const url = 'https://cs.money/2.0/market/sell-orders?limit=60&offset=0&type=21&name=die&order=asc&sort=price';

    const browser = await puppeteer.launch({
      headless: true,
      args: ['--no-sandbox']
    });
    const page = await browser.newPage();
    await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36');
    await page.goto(url, {
      waitUntil: 'networkidle0',
      timeout: 60000
    });
    const content = await page.content();
    // const preText = await page.$eval('pre', el => el.innerText);
    // const data = JSON.parse(preText);
    // data.items.forEach(el => console.log(el.asset))
    // console.log('✅ Заголовок страницы:', data);
    console.log('✅ Заголовок страницы:', content);
    await browser.close();
  } catch (err) {
    console.error('❌ Ошибка при запуске Puppeteer:', err);
  }
})();
