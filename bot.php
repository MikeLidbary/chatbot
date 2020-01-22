<?php

require __DIR__ . '/vendor/autoload.php';
use GuzzleHttp\Client as GuzClient;

if ($_SERVER[ 'REQUEST_METHOD' ] === 'POST') {
    
    $chatbot_details = $_REQUEST[ 'Memory' ];

    $chatbot_details = json_decode($chatbot_details);

    $exchange = $chatbot_details->twilio->collected_data->chatbot_details->answers;

    $base_symbol = $exchange->convert_from->answer;

    $convert_symbol = $exchange->convert_to->answer;

    $amount =  0;

    if (isset($exchange->amount->answer)) {
        $amount = $exchange->amount->answer;
    }
    $message ="";
    if ($amount ==0) {
        $content = getExchangeRates($base_symbol, $convert_symbol);
        if (isset($content['error'])) {
            $message = $content['error'];
        } else {
            foreach ($content['rates'] as $base_sym =>$arr) {
                $message.= "1 ".$content['base']." = ".$arr." ".$base_sym."\n";
            }
        }
    } else {
        $content = convert($base_symbol, $convert_symbol, $amount);
        if (isset($content['error'])) {
            $message = $content['error'];
        } else {
            $message .=$amount." ".$base_symbol." = ".$content." ".$convert_symbol;
        }
    }
    $response = array(
       'actions' => array(
           array(
               'say' => $message
           ),
           array(
               'redirect' => 'task://goodbye'
           )
       )
   );

   echo json_encode( $response );
}
/**
 * Get the latest exchange rates
 * @param string $base_symbol - the symbol of the currency to convert from
 * @param string $convert_symbol - the symbol of the currency to convert to
 * @return array $data 
 */
function getExchangeRates($base_symbol,$convert_symbol){
    try {
        $url = "https://api.exchangeratesapi.io/latest?base=".$base_symbol."&symbols=".$convert_symbol;

        $client = new GuzClient();

        $response = $client->get($url);

        $data = json_decode($response->getBody()->getContents(),TRUE);
    }
    catch (GuzzleHttp\Exception\ClientException $e) {
        $response = $e->getResponse();
        $responseBodyAsString = $response->getBody()->getContents();
        $data = json_decode($responseBodyAsString,TRUE);
    }
    catch(Exception $e)
    {
        $data = $e->getMessage();
    }

    return $data;

}

/**
 * Get the convert rates
 * @param string $base_symbol - the symbol of the currency to convert from
 * @param string $convert_symbol - the symbol of the currency to convert to
 * @return float $value 
 */
function convert($base_symbol,$convert_symbol,$amount){
    $rates = (array) getExchangeRates($base_symbol,$convert_symbol);
    $value =0;
     if(!isset($rates['error'])){
        $rate = $rates['rates'][$convert_symbol];
        $value = $amount * $rate;
    }else{
        return $rates;
    }
    return $value;
}

