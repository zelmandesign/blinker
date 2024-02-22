<?php

namespace Baselinker\Interview;

class SpringCourier
{
    private $apiBaseUrl;
    private $apiKey;

    public function __construct(string $apiBaseUrl)
    {
        $this->apiBaseUrl = $apiBaseUrl;
    }

    public function newPackage(array $order, array $params)
    {
        $this->apiKey = $params["api_key"];

        // Define supported ConsigneeCountry codes
        $supportedConsigneeCountries = [
            'AU', 'AT', 'BE', 'BG', 'BR', 'BY', 'CA', 'CH',
            'CN', 'CY', 'CZ', 'DK', 'DE', 'EE', 'ES', 'FI', 'FR', 'GB', 'GF', 'GI',
            'GP', 'GR', 'HK', 'HR', 'HU', 'ID', 'IE', 'IL', 'IS', 'IT', 'JP', 'KR',
            'LB', 'LT', 'LU', 'LV', 'MQ', 'MT', 'MY', 'NL', 'NO', 'NZ', 'PL', 'PT',
            'RE', 'RO', 'RS', 'RU', 'SA', 'SE', 'SG', 'SI', 'SK', 'TH', 'TR', 'US'
        ];

        if (!in_array($order['delivery_country'], $supportedConsigneeCountries)) {
            throw new \Exception('Error: Invalid ConsigneeCountry code.');
        }

        // Prepare request data
        $requestData = [
            "Apikey" => $this->apiKey,
            "Command" => "OrderShipment",
            "Shipment" => [
                "LabelFormat" => "PDF",
                "Service" => "PPTT",
                "Weight" => "0.85",
                "WeightUnit" => "kg",
                "Length" => "20",
                "Width" => "10",
                "Height" => "10",
                "DimUnit" => "cm",
                "Value" => "20",
                "Currency" => "EUR",
                "CustomsDuty" => "DDU",
                "Description" => "CD",
                "DeclarationType" => "SaleOfGoods",
                "DangerousGoods" => "N",
                "ConsignorAddress" => [
                    "Name" => substr($order['sender_fullname'], 0, 30),
                    "Company" => substr($order['sender_company'], 0, 30),
                    "AddressLine1" => substr($order['sender_address'], 0, 30),
                    "City" => substr($order['sender_city'], 0, 30),
                    "PostalCode" => $order['sender_postalcode'],
                    "Email" => $order['sender_email'],
                    "Phone" => substr($order['sender_phone'], 0, 15)
                ],
                "ConsigneeAddress" => [
                    "Name" => substr($order['delivery_fullname'], 0, 30),
                    "Company" => substr($order['delivery_company'], 0, 30),
                    "AddressLine1" => substr($order['delivery_address'], 0, 30),
                    "City" => substr($order['delivery_city'], 0, 30),
                    "Zip" => $order['delivery_postalcode'],
                    "Country" => substr($order['delivery_country'], 0, 2),
                    "Phone" => substr($order['delivery_phone'], 0, 15),
                    "Email" => $order['delivery_email'],
                    "Vat" => ""
                ],
                "Products" => [
                    [
                        "Description" => "CD: The Postal Service - Give Up",
                        "Sku" => "CD1202",
                        "HsCode" => "852349",
                        "OriginCountry" => "GB",
                        "ImgUrl" => "http://url.com/cd-thepostalservicegiveup.jpg",
                        "Quantity" => "2",
                        "Value" => "20",
                        "Weight" => "0.8"
                    ]
                ]
            ]
        ];

        // Send request to API
        $response = $this->sendRequest($requestData);

        // Check for errors
        if (isset($response['error'])) {
            // Return error message
            echo 'Error creating shipment.';
        } else {
            // Shipment created successfully
            return $response['Shipment']['TrackingNumber'];
        }
    }

    public function packagePDF(string $trackingNumber)
    {
        // Prepare request data
        $requestData = [
            'Apikey' => $this->apiKey,
            'Command' => 'GetShipmentLabel',
            'Shipment' => [
                'LabelFormat' => 'PNG',
                'TrackingNumber' => $trackingNumber,
            ]
        ];

        // Send request to the API
        $labelData = $this->sendRequest($requestData);

        if (isset($labelData['error'])) {
            // Return error message
            echo 'Error creating label.';
        } else {
            // $base64Image contains the potentially corrupted base64-encoded string
            $base64Image = $labelData['Shipment']['LabelImage'];

            // // Attempt to decode the base64 string
            $imageData = base64_decode($base64Image);

            // Output appropriate headers for PNG image
            header('Content-Type: image/png');

            // Output the image data
            echo $imageData;
        }
    }

    private function sendRequest($data)
    {
        // Prepare request URL
        $url = $this->apiBaseUrl;

        // Prepare JSON data
        $jsonData = json_encode($data);

        // Set HTTP headers
        $headers = [
            'Content-Type: application/json'
        ];

        // Make POST request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        curl_close($ch);

        // Check for connection errors
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            return ['error' => 'Connection error: ' . $error];
        }

        // Decode JSON response
        $decodedResponse = json_decode($response, true);

        // Check for errors
        if (!$decodedResponse) {
            // Return error message if decoding fails
            return ['error' => 'Failed to decode API response.'];
        }

        // Check for API errors
        if (isset($decodedResponse['ErrorLevel']) && $decodedResponse['ErrorLevel'] > 0) {
            $errorLevel = $decodedResponse['ErrorLevel'];
            $errorMessage = isset($decodedResponse['Error']) ? $decodedResponse['Error'] : 'Unknown error';

            // Additional check for fatal error
            $errorType = $errorLevel === 10 ? 'Fatal API Error' : 'API Error';

            // Print error message
            echo $errorType . ": Error level - " . $errorLevel . ", Error - " . $errorMessage . "<br>";

            // Return error response
            return ['error' => $errorType . ': ' . $errorMessage];
        }

        // Return successful response
        return $decodedResponse;
    }
}
