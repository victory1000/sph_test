const Request = require("./request");
try {
  const Req = new Request({
    url: 'https://steamcommunity.com/market/listings/730/'
    + encodeURIComponent(skin_name)
    + '/render/?query=&start='+start+'&country=RU&currency=5&count=100',
    debug: false,
  });
  const data = Req.exec();
  console.log(JSON.stringify({"new_price": data})); // output for php
} catch (err) {
  console.error('❌ Javascript ERROR:', err);
}