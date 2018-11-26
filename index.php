<?
header("Content-type: text/html; charset=utf-8");
//get list of symbols
$xinfo = json_decode(file_get_contents("https://api.binance.com/api/v1/exchangeInfo"), true);
$symbols = [];
foreach ($xinfo["symbols"] as $sym) {
    $symbols[] = $sym["symbol"];
}

asort($symbols);
echo '<form method="get">';
echo '<select name="symbol">';
foreach ($symbols as $sym) {
    echo '<option val="' . $sym . '">' . $sym . '</option>';
}

echo '</select>';
echo '<input type="submit"></form>';
$intervals = array("1h", "30m", "15m", "5m", "1m");
if ($_GET["symbol"] != "") {
    echo $_GET["symbol"] . "<br>";
}

foreach ($intervals as $int) {
    echo "-----------------<br>";
    echo $int . "<br>";
    $filename = "binance_" . $_GET["symbol"] . "_" . $int . ".json";
    if (file_exists($filename)) {
        echo "file " . $filename . " exists. Skipping.<br>";
        continue;
    }
    $ts_ms = round(microtime(true) * 1000); //current GMT UNIX time in miliseconds
    $qty = 10;
    $ts_last = 1483243199000; //1.1.2017 milisec
    while ($ts_last < $ts_ms && $qty > 0) {
        echo gmdate("Y-m-d\TH:i:s\Z", $ts_last / 1000) . "<br>";
        $string = file_get_contents("https://api.binance.com/api/v1/klines?symbol=" . $_GET["symbol"] . "&interval=" . $int . "&startTime=" . $ts_last);
        //print_r($http_response_header);//global variable, always filled
        if ($http_response_header[0] != "HTTP/1.1 200 OK") {echo $string . "<br>" . $http_response_header[0];exit;}
        $json = json_decode($string, true);
        //json trim [ /not first/
        if ($ts_last != 1483243199000) {
            $string = substr($string, 1);
        }

        $ts_last = $json[count($json) - 1][6];
        $ts_last++;
        $qty = count($json);
        echo $qty . "<br>";
        //json trim ] add, /not last, should have less than 500, 0.2% chance/
        if ($qty == 500) {$string = substr($string, 0, -1);
            $string .= ",\n";}
        file_put_contents($filename, $string, FILE_APPEND | LOCK_EX);
        echo str_repeat(' ', 1024 * 64);
        flush();
        ob_flush();
        usleep(200000);
        set_time_limit(60);
    }
    echo "Done! " . $filename . "<br>";
}
