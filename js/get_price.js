const puppeteer = require('puppeteer-extra');
const StealthPlugin = require('puppeteer-extra-plugin-stealth');
puppeteer.use(StealthPlugin());

const conf = {
  skins: [
    "Charm | Disco MAC",
    "Charm | Baby's AK",
    "Charm | Die-cast AK",
    "Charm | Titeenium AWP",
    "Charm | Glamour Shot",
    "Charm | Hot Hands",
  ],
};

process.stdin.setEncoding('utf8');
process.stdin.on('data', async chunk => {

  await (async () => {
    const browser = await puppeteer.launch({
      headless: true,
      args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
    let page = await browser.newPage();
    await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36');
    await page.setExtraHTTPHeaders({
      'Accept-Language': 'en-US,en;q=0.9',
    });

    try {
      for (const skin_name of conf.skins) {
        await new Promise(res => setTimeout(res, 1000));
        if (page.isClosed()) {
          page = await browser.newPage();
          await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36');
          await page.setExtraHTTPHeaders({'Accept-Language': 'en-US,en;q=0.9',});
        }

        await page.goto("https://steamcommunity.com/market/priceoverview/?market_hash_name="+encodeURIComponent(skin_name)+"&appid=730&currency=5", {
          waitUntil: 'networkidle2',
          timeout: 5000
        });

const content = await page.content();
//         const preText = await page.$eval('pre', el => el.innerText);
//         const data2 = JSON.parse(preText);
        console.error(content);
      }
    } catch (err) {
      console.error('❌ Javascript ERROR:', err);
    }

    console.log(JSON.stringify({"output": null})); // output for php
    await browser.close();
  })();

});