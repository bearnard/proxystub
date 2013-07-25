<?
$proxy_addr = 'REPLACE_ME';

function apache_request_headers() {
  $arh = array();
  $rx_http = '/\AHTTP_/';
  foreach($_SERVER as $key => $val) {
    if( preg_match($rx_http, $key) ) {
      $arh_key = preg_replace($rx_http, '', $key);
      $rx_matches = array();
      // do some nasty string manipulations to restore the original letter case
      // this should work in most cases
      $rx_matches = explode('_', $arh_key);
      if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
        foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
        $arh_key = implode('-', $rx_matches);
      }
      if ($arh_key == 'HOST' && isset($_SERVER['HTTP_X_HOST'])) {
          $val = $_SERVER['HTTP_X_HOST'];
      }
      array_push($arh, dashesToCamelCase(ucfirst(strtolower($arh_key)), true) . ": ". $val);
    }
  }
  return( $arh );
}

function dashesToCamelCase($string, $capitalizeFirstCharacter = false) {
  return preg_replace_callback("/-[a-zA-Z]/", 'removeDashAndCapitalize', $string);
}

function removeDashAndCapitalize($matches) {
  return strtoupper($matches[0][0].$matches[0][1]);
}

function proxy(){
    global $proxy_addr;
    $ch=curl_init();
    if($_SERVER['REQUEST_METHOD'] == "POST"){
	curl_setopt($ch, CURLOPT_POST,1);
	curl_setopt($ch, CURLOPT_POSTFIELDS,file_get_contents("php://input"));
    }
    curl_setopt($ch,CURLOPT_URL,'http://' . $proxy_addr . $_SERVER['REQUEST_URI']);
    curl_setopt($ch,CURLOPT_HTTPHEADER, apache_request_headers());
    curl_setopt($ch,CURLOPT_FOLLOWLOCATION,false); 
    curl_setopt($ch,CURLOPT_HEADER,false);
    curl_setopt($ch,CURLOPT_HEADERFUNCTION,'proxy_headers');
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,false);
    curl_exec($ch);
    curl_close($ch);
    
}

function proxy_headers($ch, $header_line) {
    if (strpos(strtolower($header_line), 'transfer-encoding') === 0)
	    return strlen($header_line);

	header($header_line);

	return strlen($header_line);
}
ini_set('expose_php', 0);
proxy();
?>
