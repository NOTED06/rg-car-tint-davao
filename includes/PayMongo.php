<?php

class PayMongo {
    
    private $secret_key;
    private $public_key;
    private $api_base = 'https://api.paymongo.com/v1';
    private $pdo;
    
    
    public function __construct($pdo = null) {
        $this->pdo = $pdo;
        $this->loadApiKeys();
    }
    
    
    private function loadApiKeys() {
        if ($this->pdo) {
            try {
                $stmt = $this->pdo->prepare("SELECT meta_value FROM system_info WHERE meta_field = ?");
                $stmt->execute(['paymongo_secret_key']);
                $val = $stmt->fetchColumn();
                if ($val) $this->secret_key = $val;
                
                $stmt->execute(['paymongo_public_key']);
                $val = $stmt->fetchColumn();
                if ($val) $this->public_key = $val;
            } catch (PDOException $e) {
                error_log("Failed to load PayMongo keys from DB: " . $e->getMessage());
            }
        }
        
        
        if (empty($this->secret_key)) {
            $this->secret_key = 'YOUR_PAYMONGO_SECRET_KEY';
        }
        
        if (empty($this->public_key)) {
            $this->public_key = '';
        }
    }
    
    
    public function createGcashSource($amount, $description, $success_url, $failed_url, $metadata = []) {
        return $this->createSource('gcash', $amount, $description, $success_url, $failed_url, $metadata);
    }

    
    public function createCheckoutSession($amount, $description, $success_url, $failed_url, $metadata = []) {
        $amount_centavos = (int) round($amount * 100);
        
        if ($amount_centavos < 10000) {
            return [
                'status' => 'failed',
                'msg' => 'Minimum amount for payment is ₱100.00'
            ];
        }
        
        $data = [
            'data' => [
                'attributes' => [
                    'line_items' => [
                        [
                            'currency' => 'PHP',
                            'amount' => $amount_centavos,
                            'description' => $description,
                            'name' => 'Window Tinting Package',
                            'quantity' => 1
                        ]
                    ],
                    'payment_method_types' => ['gcash', 'paymaya', 'card'],
                    'success_url' => $success_url,
                    'cancel_url'  => $failed_url,
                    'description' => $description
                ]
            ]
        ];
        
        $response = $this->makeRequest('POST', '/checkout_sessions', $data);
        
        if (isset($response['data']['attributes']['checkout_url'])) {
            return [
                'status' => 'success',
                'source_id' => $response['data']['id'],
                'checkout_url' => $response['data']['attributes']['checkout_url'],
                'status_source' => 'active',
                'raw' => $response
            ];
        }
        
        
        return $this->createSource('gcash', $amount, $description, $success_url, $failed_url, $metadata);
    }
    
    
    public function createSource($type, $amount, $description, $success_url, $failed_url, $metadata = []) {
        $amount_centavos = (int) round($amount * 100);
        
        if ($amount_centavos < 10000) {
            return [
                'status' => 'failed',
                'msg' => 'Minimum amount for GCash payment is ₱100.00'
            ];
        }
        
        if ($amount_centavos > 10000000) {
            return [
                'status' => 'failed',
                'msg' => 'Payment amount (₱' . number_format($amount, 2) . ') exceeds the PayMongo/GCash maximum single transaction limit of ₱100,000.00. Please select the 10% Down Payment option or pay remaining balance on your appointment day.'
            ];
        }
        
        $billing = $metadata['billing'] ?? null;
        unset($metadata['billing']);
        
        $flat_metadata = [];
        foreach ($metadata as $key => $value) {
            if (is_array($value)) {
                $flat_metadata[$key] = json_encode($value);
            } else {
                $flat_metadata[$key] = (string) $value;
            }
        }
        
        $data = [
            'data' => [
                'attributes' => [
                    'amount' => $amount_centavos,
                    'currency' => 'PHP',
                    'type' => $type,
                    'redirect' => [
                        'success' => $success_url,
                        'failed' => $failed_url
                    ]
                ]
            ]
        ];
        
        if ($billing) {
            $data['data']['attributes']['billing'] = $billing;
        }
        
        if (!empty($flat_metadata)) {
            $data['data']['attributes']['metadata'] = $flat_metadata;
        }
        
        $response = $this->makeRequest('POST', '/sources', $data);
        
        if (isset($response['data']['id'])) {
            return [
                'status' => 'success',
                'source_id' => $response['data']['id'],
                'checkout_url' => $response['data']['attributes']['redirect']['checkout_url'],
                'status_source' => $response['data']['attributes']['status'],
                'raw' => $response
            ];
        }
        
        $error_msg = 'Payment initialization failed';
        if (isset($response['errors'][0]['detail'])) {
            $error_msg = $response['errors'][0]['detail'];
        } elseif (isset($response['error'])) {
            $error_msg = $response['error'];
        }
        if (strpos($error_msg, '10000000') !== false) {
            $error_msg = 'Payment amount cannot exceed ₱100,000.00 per GCash transaction. Please choose the 10% Down Payment option or pay remaining balance on appointment day.';
        }
        
        return [
            'status' => 'failed',
            'msg' => $error_msg,
            'raw' => $response
        ];
    }
    
    
    public function getSource($source_id) {
        $response = $this->makeRequest('GET', '/sources/' . $source_id);
        
        if (isset($response['data']['id'])) {
            return [
                'status' => 'success',
                'source_id' => $response['data']['id'],
                'source_status' => $response['data']['attributes']['status'],
                'amount' => $response['data']['attributes']['amount'] / 100,
                'type' => $response['data']['attributes']['type'],
                'metadata' => $response['data']['attributes']['metadata'] ?? [],
                'raw' => $response
            ];
        }
        
        return [
            'status' => 'failed',
            'msg' => 'Source not found',
            'raw' => $response
        ];
    }
    
    
    public function createPayment($source_id, $amount, $description = '') {
        $amount_centavos = (int) round($amount * 100);
        
        $data = [
            'data' => [
                'attributes' => [
                    'amount' => $amount_centavos,
                    'currency' => 'PHP',
                    'description' => $description,
                    'source' => [
                        'id' => $source_id,
                        'type' => 'source'
                    ]
                ]
            ]
        ];
        
        $response = $this->makeRequest('POST', '/payments', $data);
        
        if (isset($response['data']['id'])) {
            return [
                'status' => 'success',
                'payment_id' => $response['data']['id'],
                'payment_status' => $response['data']['attributes']['status'],
                'amount' => $response['data']['attributes']['amount'] / 100,
                'raw' => $response
            ];
        }
        
        $error_msg = 'Payment creation failed';
        if (isset($response['errors'][0]['detail'])) {
            $error_msg = $response['errors'][0]['detail'];
        }
        
        return [
            'status' => 'failed',
            'msg' => $error_msg,
            'raw' => $response
        ];
    }
    
    
    private function makeRequest($method, $endpoint, $data = null) {
        $url = $this->api_base . $endpoint;
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($this->secret_key . ':')
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log("PayMongo cURL error: " . $error);
            return ['error' => 'Connection error: ' . $error];
        }
        
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("PayMongo JSON decode error: " . json_last_error_msg());
            return ['error' => 'Invalid response from payment gateway'];
        }
        
        return $decoded;
    }
}
