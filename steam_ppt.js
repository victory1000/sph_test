global.File = class {};
const cheerio = require('cheerio');
const puppeteer = require('puppeteer-extra');
const StealthPlugin = require('puppeteer-extra-plugin-stealth');
puppeteer.use(StealthPlugin());

process.stdin.setEncoding('utf8');
process.stdin.on('data', async chunk => {
  const input = chunk.toString();
  const php_input = JSON.parse(input);

  const skins = ["Charm | Disco MAC", "Charm | Baby's AK", "Charm | Die-cast AK", "Charm | Titeenium AWP"];//, "Charm | Glamour Shot"];
  const items = 100;
  let url;
  let listings = {};

  await (async () => {
    try {
      const browser = await puppeteer.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
      });
      let page = await browser.newPage();
      await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36');
      await page.setExtraHTTPHeaders({
        'Accept-Language': 'en-US,en;q=0.9',
        'Origin': "chrome-extension://jjicbefpemnphinccgikpdaagjebbnhg"
      });

      for (const skin_name of skins) {
        url = 'https://steamcommunity.com/market/listings/730/' + encodeURIComponent(skin_name) + '/render/?query=&start=0&country=RU&currency=5&count='+items;
        await page.goto(url, {
          waitUntil: 'networkidle2',
          timeout: 5000
        });

        // const content = await page.content();
        const preText = await page.$eval('pre', el => el.innerText);
        const data = JSON.parse(preText);

        listings[`${skin_name}`] = {};
        const processed_skins = php_input[skin_name] || [];
        let count_listings = 0;
        // console.error({processed_skins});

        const $ = cheerio.load(data.results_html);

        $('.market_listing_row').each((i, el) => {
          const listing_id = $(el).attr('id').replace('listing_', '');
          if (count_listings < 10 && !processed_skins.includes(listing_id)) {
            count_listings++;
            listings[skin_name][""+listing_id+""] = {
              "inspect": $(el).find('.market_listing_row_action a').attr('href') || null
            };
          }
        });

        Object.values(data.listinginfo).forEach(function (el) {
          if (listings[skin_name].hasOwnProperty(el.listingid)) {
            listings[skin_name][el.listingid]["price"] = (parseInt(el.converted_price) + parseInt(el.converted_fee)) / 100;
            // "assetid": el.asset.id,
          }
        });

        for (const [_listing_id, _data] of Object.entries(listings[skin_name])) {
          try {
            // Задержка перед запросом (чтобы не попасть на лимиты)
            // await new Promise(res => setTimeout(res, 100));

            if (page.isClosed()) {
              const startTime = performance.now();
              page = await browser.newPage();
              await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36');
              await page.setExtraHTTPHeaders({
                'Accept-Language': 'en-US,en;q=0.9',
                'Origin': "chrome-extension://jjicbefpemnphinccgikpdaagjebbnhg"
              });
              const endTime = performance.now();
              console.error(`New browser page ${endTime - startTime} ms`);
            }

            const startTime = performance.now();
            await page.goto("https://api.csfloat.com/?url=" + _data.inspect, {
              waitUntil: 'networkidle2',
              timeout: 5000
            });
            const endTime = performance.now();
            console.error(`Goto csfloat page ${endTime - startTime} ms`);

            // const content = await page.content();
            const preText = await page.$eval('pre', el => el.innerText);
            const data2 = JSON.parse(preText);
            listings[skin_name][_listing_id]["pattern"] = data2.iteminfo.keychains[0].pattern;

            if (listings[skin_name][_listing_id]["pattern"].length === 0) {
              console.error("Empty pattern "+skin_name+" "+_listing_id+" ", listings[skin_name][_listing_id]);
            }

          } catch (err) {
            console.error("Ошибка при переходе:", err.message);
          }

          // old logic
          // data.assets[730][2][asset_id].descriptions.forEach(function (el) {
          //   if (el.value.includes('Charm Template')) {
          //     pattern = parseInt(el.value.split(':')[1].trim());
          //     listings[skin_name][_listing_id]['pattern'] = pattern;
          //   }
          // });
        }
      }

      console.log(JSON.stringify(listings)); // output for php

      await browser.close();
    } catch (err) {
      console.error('❌ Ошибка при запуске Puppeteer:', err);
    }
  })();

});
