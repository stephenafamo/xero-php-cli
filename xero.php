<?php
$shortopts  = "";
$shortopts .= "f:";  // Required value: The file to upload
$shortopts .= "t:";  // Required value: The type (bill or invoice)
$shortopts .= "s::";  // Optional value: The status (draft, submitted or authorised. Default is draft)

$longopts = ["help"];

$options = getopt($shortopts, $longopts);

$help_text = "\r\n Help for HNG Xero API script \r\n \r\n";
$help_text .= "-f path to csv file to upload. Required. \r\n \r\n";
$help_text .= "-t upload type: bill or invoice. Required \r\n \r\n";
$help_text .= "-s status to set for the uploaded invoices: draft, submitted or authorised. \r\n";

if (isset($options['help'])) die( $help_text);

if (!isset($options['f'], $options['t'])) die( "File or type not defined");

$upload_sheet = $options['f'];
if (!is_file($upload_sheet))  die("invalid upload file");

if (strtolower($options['t']) == 'invoice') $invoice_type = "ACCREC";
elseif (strtolower($options['t']) == 'bill') $invoice_type = "ACCPAY";
else die("-t (type) must be bill or invoice");

$invoice_status = "DRAFT";

if(isset($options['s'])){
	if (strtolower($options['s']) == 'draft') $invoice_status = "DRAFT";
	elseif (strtolower($options['s']) == 'submitted') $invoice_status = "SUBMITTED";
	elseif (strtolower($options['s']) == 'authorised') $invoice_status = "AUTHORISED";
	else die('if defined, -s (status) must be draft, submitted or authorised. Default is "draft"');
}

require_once __DIR__ . '/config/hng.php';

//function defination to convert array to xml
function array_to_xml($array, &$xml_object) {
    foreach($array as $key => $value) {
        if(is_array($value)) {
            if(!is_numeric($key)){
                $subnode = $xml_object->addChild("$key");
                array_to_xml($value, $subnode);
            }else{
                $subnode = $xml_object->addChild("item$key");
                array_to_xml($value, $subnode);
            }
        }else {
            $xml_object->addChild("$key",htmlspecialchars("$value"));
        }
    }
}

$rows = array_map('str_getcsv', file($upload_sheet));
$header = array_shift($rows);

$invoices = [];
$contacts = [];

$contacts_xml = new SimpleXMLElement("<?xml version=\"1.0\"?><Contacts></Contacts>");
$invoices_xml = new SimpleXMLElement("<?xml version=\"1.0\"?><Invoices></Invoices>");

foreach ($rows as $row) {
	$contacts['Contact'] = ['Name' => $row[0]]; 
	array_to_xml($contacts, $contacts_xml);
	$invoices[] = array_combine($header, $row);
}

$endpoint = 'Contacts';
$url = $XeroOAuth->url($endpoint);

$response = $XeroOAuth->request( "POST", $url, [], $contacts_xml->asXML());

$xml = simplexml_load_string($response['response']) or die("Error: Cannot create object");

foreach ($xml->Contacts->Contact as $contact_object) {
	$x = (string) $contact_object->ContactID;
	$y = (string) $contact_object->Name;
	$contact_hash[$x] = $y;
}

foreach ($invoices as $key => $row) {
	$invoices[$key]['ContactID'] = array_search($row['*ContactName'], $contact_hash);
}

foreach ($invoices as $key => $invoice) {
	$invoices_array['Invoice']['Type'] = $invoice_type;
	$invoices_array['Invoice']['Status'] = $invoice_status;;
	$invoices_array['Invoice']['Contact']["ContactID"] = $invoice['ContactID'];
	$invoices_array['Invoice']['Contact']["Name"] = $invoice['*ContactName'];
	$invoices_array['Invoice']['InvoiceNumber'] = $invoice['*InvoiceNumber'];
	$invoices_array['Invoice']['Date'] = date('Y-m-d', strtotime($invoice['*InvoiceDate']));
	$invoices_array['Invoice']['DueDate'] = date('Y-m-d', strtotime($invoice['*DueDate']));

	$invoices_array['Invoice']['LineItems']['LineItem']['Description'] = $invoice['*Description'];
	$invoices_array['Invoice']['LineItems']['LineItem']['Quantity'] = $invoice['*Quantity'];
	$invoices_array['Invoice']['LineItems']['LineItem']['UnitAmount'] = $invoice['*UnitAmount'];
	$invoices_array['Invoice']['LineItems']['LineItem']['AccountCode'] = $invoice['*AccountCode'];

	$tax_rate = 0;
	if(is_numeric($invoice['*TaxType'])) $tax_rate = (int) $invoice['*TaxType'];
	$tax_amount = $invoice['*Quantity'] * $invoice['*UnitAmount'] * $tax_rate / 100;
	$invoices_array['Invoice']['LineItems']['LineItem']['TaxAmount'] = $tax_amount;


	if(is_numeric($invoice['TaxAmount'])) 
		$invoices_array['Invoice']['LineItems']['LineItem']['TaxAmount'] = $invoice['TaxAmount'];

	if(!empty($invoice['Currency'])) 
		$invoices_array['Invoice']['CurrencyCode'] = $invoice['Currency'];

	array_to_xml($invoices_array, $invoices_xml);
}

$endpoint = 'Invoices';
$url = $XeroOAuth->url($endpoint);

$response = $XeroOAuth->request( "POST", $url, [], $invoices_xml->asXML());

if($response['code'] === 200) echo "Upload successful";
else echo "Upload failed";

// var_dump ($response);