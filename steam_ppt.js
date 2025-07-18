const puppeteer = require('puppeteer-extra');
const StealthPlugin = require('puppeteer-extra-plugin-stealth');
puppeteer.use(StealthPlugin());

process.stdin.setEncoding('utf8');
process.stdin.on('data', async chunk => {
  // const input = chunk.toString();
  // const data = JSON.parse(input);

  const skins = ["Charm | Baby's AK"]//, "Charm | Die-cast AK", "Charm | Titeenium AWP", "Charm | Disco MAC", "Charm | Glamour Shot"];

  skins.forEach(skin_name => {


    (async () => {
      try {
        const url = 'https://steamcommunity.com/market/listings/730/'+encodeURIComponent(skin_name)+'/render/?query=&start=0&country=RU&count=10&currency=5';

        const browser = await puppeteer.launch({
          headless: true,
          args: ['--no-sandbox', '--disable-setuid-sandbox']
        });
        const page = await browser.newPage();
        await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36');
        await page.setExtraHTTPHeaders({
          'Accept-Language': 'en-US,en;q=0.9'
        });
        await page.goto(url, {
          waitUntil: 'networkidle2',
          timeout: 60000
        });
        const content = await page.content();
        const preText = await page.$eval('pre', el => el.innerText);
        const data = JSON.parse(preText);
        // console.log(data.assets)
        let listings = {};
        let listing_id, asset_id = 0;
        listings[`${skin_name}`] = {};

        Object.values(data.listinginfo).forEach(function (el) {
          listing_id = el.listingid;
          listings[skin_name][`${listing_id}`] = { 'assetid': el.asset.id };
        });
        console.log(listings)
        for (const [_listing_id, _data] of Object.entries(listings[skin_name])) {
          console.log(_listing_id)
          console.log(_data)
          asset_id = _data.assetid;
          console.log(data.assets[730][2][asset_id])
        }
        // console.log('✅ Заголовок страницы:', data);
        // console.log('✅ Заголовок страницы: ', data);
        await browser.close();
      } catch (err) {
        console.log('❌ Ошибка при запуске Puppeteer:', err);
      }
    })();


  })
});
