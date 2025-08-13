
class Request {

  constructor(params) {
    this.debug = params.debug || false;
    this.url = params.url;
  }

  exec = async function () {
    const startTime = performance.now();
    const headers = {
      'Accept-Language': 'en-US,en;q=0.9',
      'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36'
    };
    if (this.url.includes('csfloat')) {
      headers.Origin = 'chrome-extension://jjicbefpemnphinccgikpdaagjebbnhg';
    }

    const res = await fetch(this.url, { headers: headers });
    const endTime = performance.now();

    if (!res.ok) console.error(`${this.url} HTTP error status: ${res.status}`);

    if (this.debug) {
      const url = this.url.replace(/^https:\/\/([^/]+).*$/,"$1")
      console.error(`Execution time ${url} ${endTime - startTime} ms`);
    }

    return await res.json();
  }
}

module.exports = Request;