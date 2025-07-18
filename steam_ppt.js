const puppeteer = require('puppeteer-extra');
const StealthPlugin = require('puppeteer-extra-plugin-stealth');
puppeteer.use(StealthPlugin());

process.stdin.setEncoding('utf8');
process.stdin.on('data', async chunk => {
  // const input = chunk.toString();
  // const data = JSON.parse(input);

  const skins = ["Charm | Baby's AK", "Charm | Die-cast AK", "Charm | Titeenium AWP", "Charm | Disco MAC", "Charm | Glamour Shot"];
  let url;
  let listings = {};

  await (async () => {
    try {
      const browser = await puppeteer.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
      });
      const page = await browser.newPage();
      await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36');
      await page.setExtraHTTPHeaders({
        'Accept-Language': 'en-US,en;q=0.9'
      });

      for (const skin_name of skins) {
        console.error('Process '+skin_name);
        url = 'https://steamcommunity.com/market/listings/730/' + encodeURIComponent(skin_name) + '/render/?query=&start=0&country=RU&count=10&currency=5';
        await page.goto(url, {
          waitUntil: 'networkidle2',
          timeout: 60000
        });

        // const content = await page.content();
        const preText = await page.$eval('pre', el => el.innerText);
        const data = JSON.parse(preText);

        let listing_id, asset_id, pattern = 0;
        listings[`${skin_name}`] = {};

        Object.values(data.listinginfo).forEach(function (el) {
          listing_id = el.listingid;
          listings[skin_name][""+listing_id+""] = {
            "assetid": el.asset.id,
            "price": (parseInt(el.converted_price) + parseInt(el.converted_fee)) / 100
          };
        });

        for (const [_listing_id, _data] of Object.entries(listings[skin_name])) {
          asset_id = _data.assetid;
          data.assets[730][2][asset_id].descriptions.forEach(function (el) {
            if (el.value.includes('Charm Template')) {
              pattern = parseInt(el.value.split(':')[1].trim());
              listings[skin_name][_listing_id]['pattern'] = pattern;
            }
          });
        }
      }

      console.log(JSON.stringify(listings))

      await browser.close();
    } catch (err) {
      console.error('❌ Ошибка при запуске Puppeteer:', err);
    }
  })();

});
