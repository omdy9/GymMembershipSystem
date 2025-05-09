<?php
namespace Instamojo;

class Instamojo
{
    private $apiKey;
    private $authToken;
    private $baseUrl = 'https://api.instamojo.com/v2/';

    public function __construct($apiKey, $authToken)
    {
        $this->apiKey = $apiKey;
        $this->authToken = $authToken;
    }

    // Function to create a payment request
    public function paymentRequestCreate($params)
    {
        $url = $this->baseUrl . 'payment_requests/';

        // Set up headers
        $headers = [
            "X-Api-Key: " . $this->apiKey,
            "X-Auth-Token: " . $this->authToken,
            "Content-Type: application/json"
        ];

        // cURL request setup
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));

        // Execute request and capture response
        $response = curl_exec($ch);
        $responseStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($responseStatus != 201) {
            throw new \Exception("Payment request creation failed. " . $response);
        }

        return json_decode($response, true);
    }

    // Function to check payment status
    public function paymentRequestPaymentStatus($paymentRequestId, $paymentId)
    {
        $url = $this->baseUrl . "payment_requests/{$paymentRequestId}/payments/{$paymentId}/";

        // Set up headers
        $headers = [
            "X-Api-Key: " . $this->apiKey,
            "X-Auth-Token: " . $this->authToken
        ];

        // cURL request setup
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Execute request and capture response
        $response = curl_exec($ch);
        $responseStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($responseStatus != 200) {
            throw new \Exception("Failed to fetch payment status. " . $response);
        }

        return json_decode($response, true);
    }
}
?>
