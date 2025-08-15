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

        console.error('listinginfo', data.listinginfo);

        Object.values(data.listinginfo).forEach(function (el) {
          result[`${skin_name}`] = (parseInt(el.converted_price) + parseInt(el.converted_fee)) / 100;
          break;
        });
      }

    } catch (err) {
      console.error('❌ Javascript ERROR:', err);
    }

    console.log(JSON.stringify({"price": result})); // output for php
  })();

});