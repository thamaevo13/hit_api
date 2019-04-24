<!DOCTYPE html>
<html>
    <head>
    </head>
    <body>
<table align="center">
<tr>
<td>
<?php

require_once 'api_interface.php';
require_once 'db_operation.php';

/**
 * generate unique trx_id
 *
 */
function generateTrxId($length) {
	return substr(str_shuffle("0123456789"), 0, $length);
}

/**
 * pretty print JSON data
 *
 */
function prettyPrint($json) {
    $result = '';
    $level = 0;
    $in_quotes = false;
    $in_escape = false;
    $ends_line_level = NULL;
    $json_length = strlen($json);

    for ($i = 0; $i < $json_length; $i++ ) {
        $char = $json[$i];
        $new_line_level = NULL;
        $post = "";
		
        if ($ends_line_level !== NULL) {
            $new_line_level = $ends_line_level;
            $ends_line_level = NULL;
        }
        if ($in_escape) {
            $in_escape = false;
        } else if ($char === '"') {
            $in_quotes = !$in_quotes;
        } else if (! $in_quotes) {
            switch( $char ) {
                case '}': case ']':
                    $level--;
                    $ends_line_level = NULL;
                    $new_line_level = $level;
                    break;

                case '{': case '[':
                    $level++;
                case ',':
                    $ends_line_level = $level;
                    break;

                case ':':
                    $post = " ";
                    break;

                case " ": case "\t": case "\n": case "\r":
                    $char = "";
                    $ends_line_level = $new_line_level;
                    $new_line_level = NULL;
                    break;
            }
        } else if ($char === '\\') {
            $in_escape = true;
        }
        if ($new_line_level !== NULL) {
            $result .= "\n".str_repeat("\t", $new_line_level);
        }
        $result .= $char.$post;
    }

    return $result;
}

/**
 * hit API
 *
 */
$idpel = $_GET['idpel'];
$interface = new ApiInterface();
$params['trx_date'] = date("YmdHis");
$params['trx_id'] = generateTrxId(10);
$params['trx_type'] = '2100'; // 2100 = Inquiry, 2200 = Payment
$params['cust_msisdn'] = '01428800711';
$params['cust_account_no'] = $idpel;
$params['product_id'] = '80'; // 80 = PLN Prepaid
$params['product_nomination'] = ''; 
// $params['product_nomination'] = '20000'; // use this line to makes payment request
$params['periode_payment'] = '';
$params['unsold'] = '';
$input = json_encode($params, true);

// echo "<pre>";
// echo "Request :<br />";
// print_r(prettyPrint($input));
// echo "</pre>";

$request_date = date('Y-m-d H:i:s');
$output = $interface->hitApi($params);
$response_date = date('Y-m-d H:i:s');

// echo "<pre>";
// echo "Response :<br />";
// print_r(prettyPrint($output));
// echo "</pre>";

/**
 * save logs into database
 *
 */
$dbo = new DbOperation();
$logs['product_code'] = $params['product_id'];
$logs['customer_number'] = $params['cust_account_no'];
$logs['trx_type'] = $params['trx_type'];
$logs['request'] = $input;
$logs['response'] = $output;
$logs['request_date'] = $request_date;
$logs['response_date'] = $response_date;
$insert = $dbo->saveData($logs);

// echo "<pre>";
// echo "DB Operation :<br />";
// echo $insert;
// echo "</pre>";

/**
 * parse API response
 *
 */
$parse = json_decode($output, true);
$data = $parse['data']['trx'];
$rc = $data['rc'];


if ($rc == '0000') {
	echo "<pre>";
	echo "ID Pelanggan -> " . $data['subscriber_id'] . "<br />";
	echo "Nama Pelanggan -> " . $data['subscriber_name'] . "<br />";
    echo "Daya / Golongan -> " . $data['power'] . " / ". $data['subscriber_segmentation'];
	echo "</pre>";
} else {
	echo "<pre>";
	echo "Transaction ID -> " . $data['trx_id'] . "<br />";
	echo "Response Code (RC) -> " . $rc . "<br />";
	echo "Description -> " . $data['desc'];
	echo "</pre>";
}

?>

                <button><a href="index.php">Kembali</a></button>
                <td>
                </tr>
</table>
</body>
</html>
