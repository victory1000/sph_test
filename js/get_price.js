global.File = class {};
// const cheerio = require('cheerio');
// const puppeteer = require('puppeteer-extra');
// const StealthPlugin = require('puppeteer-extra-plugin-stealth');
const Request = require("./request");
// puppeteer.use(StealthPlugin());

process.stdin.setEncoding('utf8');
process.stdin.on('data', async chunk => {

  const php_input = JSON.parse(chunk.toString());
  let result = {};

  console.error({php_input}); // TODO delete

  await (async () => {
    try {

      for (const skin_name of php_input.skins) {
        const Req = new Request({
          url: 'https://steamcommunity.com/market/listings/730/'
          + encodeURIComponent(skin_name)
          + '/render/?query=&start=0&country=RU&currency=5&count=10',
          debug: false,
        });
        const data = await Req.exec();

        if (!data.hasOwnProperty('listinginfo')) {
          console.error(`Error while getting market page for ${skin_name}`);
          continue;
        }

        for (const info of Object.values(data.listinginfo)) {
          result[`${skin_name}`] = (parseInt(info.converted_price) + parseInt(info.converted_fee)) / 100;
          console.error(result[`${skin_name}`]);
          if (result[`${skin_name}`] !== null && result[`${skin_name}`] > 0) {
            break;
          }
        }
      }

    } catch (err) {
      console.error('❌ Javascript ERROR:', err);
    }

    console.log(JSON.stringify({"price": result})); // output for php
  })();

});