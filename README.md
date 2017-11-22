# PSEStockAPI
Philippine Stock Exchange Live Stock Data API

<strong>Live Version: <a href='https://pse.edceliz.com/'>https://pse.edceliz.com/</a></strong>

<h2>About</h2>
<p>This application provides RESTful interface to Philippine Stock Exchange's latest data. The response is JSON-formatted that contains important data about the desired company. There are currently 323 supported stocks. The list can be found by selecting All Shares in PSE Indices Composition. For computing reason, you are limited in using this. You can get a token that can support up to five (5) API calls. All information provided by this application is from the PSE website. Selecting by date is not yet supported.</p>

<h2>Usage</h2>
<pre>GET http://pse.edceliz.com/api/:stock_symbol/:access_token</pre>
<pre>Sample Usage: http://pse.edceliz.com/api/TEL/abcdefgh</pre>
<pre>
Response:
{
  "name":"PLDT Inc.",
  "symbol":"TEL",
  "currency":"PHP",
  "value":79765605.00,
  "volume":47295
  "last_trade_price":1686.00,
  "open":1695.00,
  "previous_close":1695.00,
  "previous_close_date":"Sep 11, 2017",
  "change": -9.00,
  "percent_change": -0.53,
  "high":1700.00,
  "low":1683.00,
  "average":1686.55,
  "as_of":"September 11, 2017 03:20 PM"
}
</pre>

<h2>Disclaimer</h2>
<p>All response is based from PSE's website. I'm not responsible for any inaccuracies and errors from PSE. Contact me if you want to use more than 5 attempts for your token at <a href='mailto:edceliz01@gmail.com'>edceliz01@gmail.com</a></p>
