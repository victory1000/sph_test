const Request = require("./request");
try {
  const Req = new Request({
    url: "https://steamcommunity.com/market/priceoverview/?market_hash_name="+encodeURIComponent("Charm | Die-cast AK")+"&appid=730&currency=5",
    debug: false,
  });
  const data = Req.exec();
  console.log(JSON.stringify({"new_price": data})); // output for php
} catch (err) {
  console.error('❌ Javascript ERROR:', err);
}