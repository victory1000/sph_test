const Request = require("./request");
try {
  const Req = new Request({
    url: 'https://steamcommunity.com/market/listings/730/Charm%20%7C%20Die-cast%20AK/render/?query=&start=0&country=RU&currency=5&count=10',
    debug: false,
  });
  const data = Req.exec();
  console.log(JSON.stringify({"new_price": data})); // output for php
} catch (err) {
  console.error('❌ Javascript ERROR:', err);
}