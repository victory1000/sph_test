global.File = class {};
const cheerio = require('cheerio');
const puppeteer = require('puppeteer-extra');
const StealthPlugin = require('puppeteer-extra-plugin-stealth');
const Request = require("./request");
puppeteer.use(StealthPlugin());

const conf = {
  skins: [
    "Charm | Disco MAC",
    "Charm | Baby's AK",
    "Charm | Die-cast AK",
    "Charm | Titeenium AWP",
    "Charm | Glamour Shot"
  ],
  rate_limit: 50,
};

let listings = {};
let processed_items = 0;

for (const skin_name of conf.skins) {
  listings[`${skin_name}`] = {};
}


process.stdin.setEncoding('utf8');
process.stdin.on('data', async chunk => {

  const php_input = JSON.parse(chunk.toString());

  await (async () => {
    try {

      for (const skin_name of conf.skins) {
console.error('------'+skin_name+' processed '+processed_items)
        if (processed_items >= conf.rate_limit) break;

        for (let i = 0; i < 3; i++) {
console.error(skin_name+' start '+(i*100)+' processed '+processed_items)
          if (processed_items >= conf.rate_limit) break;

          const start = i*100;
          const Req = new Request({
            url: 'https://steamcommunity.com/market/listings/730/'
                  + encodeURIComponent(skin_name)
                  + '/render/?query=&start='+start+'&country=RU&currency=5&count=100',
            debug: false,
          });
          const data = await Req.exec();

          if (!data.hasOwnProperty('results_html') || !data.hasOwnProperty('listinginfo')) {
            console.error(`Error while getting market page for ${skin_name}`);
            // open page thru browser
            continue;
          }

          const $ = cheerio.load(data.results_html);

          $('.market_listing_row').each((i, el) => {
            const listing_id = $(el).attr('id').replace('listing_', '');
            if (processed_items < conf.rate_limit && !php_input.includes(listing_id)) {
              listings[skin_name]["" + listing_id + ""] = {"inspect": $(el).find('.market_listing_row_action a').attr('href') || null};
              processed_items++;
            }
          });

          Object.values(data.listinginfo).forEach(function (el) {
            if (listings[skin_name].hasOwnProperty(el.listingid)) {
              listings[skin_name][el.listingid]["price"] = (parseInt(el.converted_price) + parseInt(el.converted_fee)) / 100;
              listings[skin_name][el.listingid]["asset_id"] = el.asset.id;
            }
          });

          for (const [_listing_id, _data] of Object.entries(listings[skin_name])) {
            const Req = new Request({
              url: "https://api.csfloat.com/?url=" + _data.inspect,
              debug: false,
            });
            const json = await Req.exec();

            listings[skin_name][_listing_id]["pattern"] = json.iteminfo.keychains[0].pattern;

            if (listings[skin_name][_listing_id]["pattern"].length === 0) {
              console.error("Empty pattern " + skin_name + " " + _listing_id + " ", listings[skin_name][_listing_id]);
            }
          }
console.error('total_count = '+data.total_count)
          if (start + 100 > data.total_count) break;
        }
      }

      console.log(JSON.stringify({"new_listings": listings})); // output for php

    } catch (err) {
      console.error('❌ Ошибка при запуске Puppeteer:', err);
    }
  })();

});

// ИНИЦИАЛИЗАЦИЯ БРАУЗЕРА (в самом начале)
// const browser = await puppeteer.launch({
//   headless: true,
//   args: ['--no-sandbox', '--disable-setuid-sandbox']
// });
// let page = await browser.newPage();
// await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36');
// await page.setExtraHTTPHeaders({
//   'Accept-Language': 'en-US,en;q=0.9',
//   'Origin': "chrome-extension://jjicbefpemnphinccgikpdaagjebbnhg"
// });

////////////// СТАРАЯ ЛОГИКА С ОТКРЫТИЕМ БРАУЗЕРА //////////////
// парсинг стима
// const startTime = performance.now();
// const url = 'https://steamcommunity.com/market/listings/730/' + encodeURIComponent(skin_name) + '/render/?query=&start=0&country=RU&currency=5&count=100';
// await page.goto(url, {
//   waitUntil: 'networkidle2',
//   timeout: 5000
// });
// const endTime = performance.now();
// console.error(`Goto steamcommunity market ${endTime - startTime} ms`);
// // const content = await page.content();
// const preText = await page.$eval('pre', el => el.innerText);
// const data = JSON.parse(preText);

////////////// СТАРАЯ ЛОГИКА С ОТКРЫТИЕМ БРАУЗЕРА //////////////
// парсинг csfloat
// Задержка перед запросом (чтобы не попасть на лимиты)
// await new Promise(res => setTimeout(res, 100));

// if (page.isClosed()) {
//   const startTime = performance.now();
//   page = await browser.newPage();
//   await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36');
//   await page.setExtraHTTPHeaders({
//     'Accept-Language': 'en-US,en;q=0.9',
//     'Origin': "chrome-extension://jjicbefpemnphinccgikpdaagjebbnhg"
//   });
//   const endTime = performance.now();
//   console.error(`New browser page ${endTime - startTime} ms`);
// }
//
// const startTime = performance.now();
// await page.goto("https://api.csfloat.com/?url=" + _data.inspect, {
//   waitUntil: 'networkidle2',
//   timeout: 5000
// });
// const endTime = performance.now();
// console.error(`Goto csfloat page ${endTime - startTime} ms`);
//
// // const content = await page.content();
// const preText = await page.$eval('pre', el => el.innerText);
// const data2 = JSON.parse(preText);

// await browser.close();