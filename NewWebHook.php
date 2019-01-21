<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "paystackdb";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);
// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Retrieve the request's body
$body = @file_get_contents("charge.json");
$signature = (isset($_SERVER['HTTP_X_PAYSTACK_SIGNATURE']) ? $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] : '');

/* It is a good idea to log all events received. Add code *
 * here to log the signature and body to db or file       */

    

        $sqlLogV = "INSERT INTO paystack_log(body, signature) VALUES ('$body', '$signature')";

    	//$parameters = array(":body"=>$body, ':signature' => $signature dd);
       
        if (mysqli_multi_query($conn, $sqlLogV)) {
		    echo "New records created successfully";
		} else {
		    echo "Error: " . $sqlLogV . "<br>" . mysqli_error($conn);
		}

		mysqli_close($conn);

		

if (!$signature) {
    // only a post with paystack signature header gets our attention
    exit();
}

define('PAYSTACK_SECRET_KEY','sk_test_2565fc33b22c3ba4d1b3e9c664e77eabf2f370a6');
// confirm the event's signature
if( $signature !== hash_hmac('sha512', $body, $signature, PAYSTACK_SECRET_KEY) ){
  // silently forget this ever happened
  exit();
}

http_response_code(200);
// parse event (which is json string) as object
// Give value to your customer but don't give any output
// Remember that this is a call from Paystack's servers and 
// Your customer is not seeing the response here at all
// $event = json_decode($body);
// $m = array();
// $pkey = array();
    
$event = json_encode($body);
switch($event->event){
    // charge.success
    case 'charge.success':
        // TIP: you may still verify the transaction
    		// before giving value.
      //Lets Update the Payments Table
       	$id = $event->data->id;
		$email = $event->data->customer->email;
		$amount = $event->data->amount;
		$dateoftran = $event->data->paid_at;
		$gateway_response = $event->data->gateway_response;
		$reference = $event->data->reference;

		$model = new Paystack;

			$model->time = $model->dbNow;
			$model->txn_ref = $reference;
			$model->email = $email;
			$model->amount = $amount;
        	$model->response = $gateway_response;
            $model->response_code = $event->data->authorization->authorization_code;
        	// Forcefully Save
			$model->save(1);
        break;
}
exit();