<?php

namespace Baselinker\Interview;

require 'src/SpringCourier.php';

// Define API base URL
$apiBaseUrl = 'https://mtapi.net/';

// Create SpringCourier instance
$springCourier = new SpringCourier($apiBaseUrl);

// Define order and parameters
$order = [
  'sender_company' => 'BaseLinker',
  'sender_fullname' => 'Jan Kowalski',
  'sender_address' => 'Kopernika 10',
  'sender_city' => 'Gdansk',
  'sender_postalcode' => '80208',
  'sender_email' => '',
  'sender_phone' => '666666666',

  'delivery_company' => 'Spring GDS',
  'delivery_fullname' => 'Maud Driant',
  'delivery_address' => 'Strada Foisorului, Nr. 16, Bl. F11C, Sc. 1, Ap. 10',
  'delivery_city' => 'Bucuresti, Sector 3',
  'delivery_postalcode' => '031179',
  'delivery_country' => 'RO',
  'delivery_email' => 'john@doe.com',
  'delivery_phone' => '555555555',
];

$params = [
  'api_key' => 'f16753b55cac6c6e',
  'label_format' => 'PDF',
  'service' => 'PPTT',
];

// Create shipment and get tracking number
$trackingNumber = $springCourier->newPackage($order, $params);

// Get shipping label and force download0
$springCourier->packagePDF($trackingNumber);
