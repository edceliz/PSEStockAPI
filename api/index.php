<?php
    session_start();
    header('Content-Type: application/json');
    require_once('{Database File}');

    /**
     * Checks if token can still try accessing the API
     *
     * @param  string $token
     * @return boolean
     */
    function hasTries($token) {
        $stmt = $GLOBALS['db']->prepare('SELECT attempts FROM pse_tokens WHERE token = ? LIMIT 1');
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $stmt->bind_result($attempts);
        $stmt->fetch();
        $stmt->close();
        return $attempts > 0;
    }

    /**
     * Reduces possible attempts of the token
     *
     * @param string $token
     */
    function consumeAttempt($token) {
        $stmt = $GLOBALS['db']->prepare('UPDATE pse_tokens SET attempts = attempts - 1 WHERE token = ? LIMIT 1');
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $stmt->close();
    }

    $response = [
        'name'=>'None',
        'symbol'=>'NULL',
        'currency'=>'PHP',
        'value'=>0.00,
        'volume'=>0,
        'last_trade_price'=>0.00,
        'open'=>0.00,
        'previous_close'=>0.00,
        'previous_close_date'=>'None',
        'change'=>0.00,
        'percent_change'=>0.00,
        'high'=>0.00,
        'low'=>0.00,
        'average'=>0.00,
        'as_of'=>'None'
    ];

    // Verify if GET has content and put it in $params array
    if (empty($_GET['url']))
        die(json_encode($response));
    $params = explode('/', $_GET['url']);

    // Verify if params' characters are valid and if token still have attempts
    if (!ctype_alnum($params[0]) || !ctype_alnum($params[1]) || !hasTries($params[1])) {
        $db->close();
        die(json_encode($response));
    }
    consumeAttempt($params[1]);

    // Get list of companies
    $companies = json_decode(file_get_contents('{JSON File}'));
    $symbol = strtoupper($params[0]);

    // Check if requested symbol exists
    if (!isset($companies->{$symbol}))
        die(json_encode($response));

    // Select the requested company information and clears list of companies.
    $company = $companies->{$symbol};
    $companies = null;

    // Prepare cURL operation to get information from PSE's endpoint
    $posts = [
        'method'=>'fetchHeaderData',
        'ajax'=>'true',
        'company'=>$company->pse_id,
        'security'=>$company->pse_security_id
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://www.pse.com.ph/stockMarket/companyInfo.html");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $posts);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $apiData = json_decode(curl_exec($ch))->records[0];
    curl_close ($ch);

    $response['name']                 = $company->name;
    $response['symbol']               = $symbol;
    $response['value']                = floatval(str_replace(',', '', $apiData->headerTotalValue));
    $response['volume']               = floatval(str_replace(',', '', $apiData->headerTotalVolume));
    $response['last_trade_price']     = floatval(str_replace(',', '', $apiData->headerLastTradePrice));
    $response['open']                 = floatval(str_replace(',', '', $apiData->headerSqOpen));
    $response['previous_close']       = floatval(str_replace(',', '', $apiData->headerSqPrevious));
    $response['previous_close_date']  = explode(' ', $apiData->lastTradedDate)[0];
    $response['change']               = floatval(str_replace(',', '', $apiData->headerChangeClose));
    $response['percent_change']       = floatval(str_replace(',', '', $apiData->headerPercChangeClose));
    $response['high']                 = floatval(str_replace(',', '', $apiData->headerSqHigh));
    $response['low']                  = floatval(str_replace(',', '', $apiData->headerSqLow));
    $response['average']              = floatval(str_replace(',', '', $apiData->headerAvgPrice));
    $response['as_of']                = date('Y-m-d H:i:s');

    echo json_encode($response);
?>
