<?php
    session_start();

    if (!isset($_SESSION['token']))
        $_SESSION['token'] = false;

    // Generate API access token
    if (isset($_POST['g-recaptcha-response'])) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'secret={Recaptcha Key}&response=' . $_POST['g-recaptcha-response'] . '&remoteit=' . getIPAddress());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $captcha = json_decode(curl_exec($ch));
        curl_close($ch);
        if (!$captcha->success) {
            header('location: index.php?error');
            die();
        }
        $token = bin2hex(openssl_random_pseudo_bytes(4));
        $_SESSION['token'] = $token;
        require_once('{Database File}');
        $stmt = $db->prepare('INSERT INTO pse_tokens(token) VALUE(?)');
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $stmt->close();
        $db->close();
        header('location: index.php');
        die();
    }

    /**
     * Fetches client's IP address.
     *
     * @return string - IP Address
     */
    function getIPAddress() {
        if (!empty($_SERVER['HTTP_CLIENT_IP']) && $this->validateIPAddress($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $iplist = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            foreach($iplist as $ip) {
                if ($this->validateIPAddress($ip)) {
                    return $ip;
                }
            }
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED']) && $this->validateIPAddress($_SERVER['HTTP_X_FORWARDED'])) {
            return $_SERVER['HTTP_X_FORWARDED'];
        }

        if (!empty($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']) && $this->validateIPAddress($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])) {
            return $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
        }

        if (!empty($_SERVER['HTTP_FORWARDED_FOR']) && $this->validateIPAddress($_SERVER['HTTP_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_FORWARDED_FOR'];
        }

        if (!empty($_SERVER['HTTP_FORWARDED']) && $this->validateIPAddress($_SERVER['HTTP_FORWARDED'])) {
            return $_SERVER['HTTP_FORWARDED'];
        }

        if ($_SERVER['REMOTE_ADDR'] == '::1') {
            return '127.0.0.1';
        }

        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * Checks if IP address is valid
     *
     * @param  string $ip
     * @return boolean
     */
    function validIPAddress($ip) {
        if (filter_var($ip, FILTER_VALIDATE_IP,
                     FILTER_FLAG_IPV4 |
                     FILTER_FLAG_IPV6 |
                     FILTER_FLAG_NO_PRIV_RANGE |
                     FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }
        return true;
    }

    /**
     *
     * Produces JSON version of companies enlisted in PSE.
     * (Requires 'scrape.html') That file is downloaded from PSE with all listed indices.
     * Truncate the file for better performance.
     *
     */

    /**
     * Produces a JSON version of companies listed in PSE's listed indices.
     *
     * Usage:
     * 1) Provide downloaded version of PSE's indice list
     * 2) Run this function
     * 3) Store output into JSON file
     *
     * WARNING: This function is supposed to be ran only by the developer to update list.
     */
    function getCompanies() {
        $file = file_get_html('scrape.html');
        $companies = [];
        foreach ($file->find('table.x-grid3-row-table tbody tr') as $row) {
            foreach ($row->find('td.x-grid3-td-1 div') as $company) {
                $name = $company->plaintext;
            }
            foreach ($row->find('td.x-grid3-td-2 div a') as $info) {
                $symbol = $info->plaintext;
                $link = explode('&', $info->href);
                $pse_id = explode('=', explode('?', $link[0])[1])[1];
                $pse_security_id = explode('=', $link[1])[1];
            }
            array_push($companies, ['name'=>$name, 'symbol'=>$symbol, 'pse_id'=>$pse_id, 'pse_security_id'=>$pse_security_id]);
        }
        echo json_encode($companies);
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PSE Scraper</title>
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <style>
        * {
            font-family: Arial;
            color: #fff;
        }

        body {
            background-color: #3498db;
        }

        h3 {
            margin-top: -15px;
            font-weight: 400;
        }

        h4 {
            margin-bottom: -10px;
        }

        p, pre {
            line-height: 25px;
        }

        form {
            margin-bottom: 20px;
        }

        form input {
            margin-top: 10px;
            background-color: #e67e22;
            padding: 10px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-weight: 600;
        }

        .error {
            background-color: #e74c3c;
            display: inline-block;
            margin-top: 0;
            padding: 10px;
            border-radius: 4px;
        }
    </style>
    <script src='https://www.google.com/recaptcha/api.js'></script>
</head>
<body>
    <h1>Philippine Stock Exchange API</h1>
    <h3>PSE API is a website application that makes use of <a href="http://www.pse.com.ph/stockMarket/home.html">PSE website's</a> end points to provide easier data source access to developers.</h3>
    <hr>
    <h4>API Overview</h4>
    <p>This application provides RESTful interface to Philippine Stock Exchange's latest data. The response is JSON-formatted that contains important data about the desired company. There are currently 323 supported stocks. The list can be found by selecting All Shares in <a href="http://www.pse.com.ph/stockMarket/marketInfo-marketActivity.html?tab=0">PSE Indices Composition</a>. <em>For computing reason, you are limited in using this. You can get a token that can support up to five (5) API calls. All information provided by this application is from the PSE website. Selecting by date is not yet supported. For more information you can <a href="http://www.edceliz.com/contact.php">contact me here.</a></em></p>
    <p><strong>API Endpoint: </strong><a href="http://pse.edceliz.com/api">http://pse.edceliz.com/api</a></p>
    <p><strong>GET Request: </strong>/stock_symbol/token - Please refer to Philippine Stock Exchange <a href="http://www.pse.com.ph/stockMarket/listedCompanyDirectory.html">company list</a> for stock symbols.</p>
    <p><strong>Sample Usage: </strong><a href="http://pse.edceliz.com/api/TEL">http://pse.edceliz.com/api/TEL/qwertyui</a></p>
    <p><strong>Response:</strong></p>
    <pre>
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
        }</pre>
    <p><strong>Request Token:</strong> <?php echo $_SESSION['token'] ?: ''; ?></p>
    <?php if (!$_SESSION['token']): ?>
    <form action="index.php" method="post">
        <?php if (isset($_GET['error'])) echo "<p class='error'>Invalid Request!</p>" ?>
        <div class="g-recaptcha" data-sitekey="{Recaptcha Key}"></div>
        <input type="submit" value="Get Token">
    </form>
    <?php
        endif;
        $_SESSION['token'] = false;
    ?>
    <footer><a href="http://www.edceliz.com">Edcel Celiz</a> &copy; <?= date('Y') ?></footer>
</body>
</html>
