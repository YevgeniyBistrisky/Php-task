<?php

$createLedgerUrl = 'http://php-task.service.nginx/ledgers';
$operationUrl = 'http://php-task.service.nginx/transactions';
$duration = 60;
$ledgerCount = 700;

$successCount = 0;
$failureCount = 0;
$ledgers = [];
for ($i = 0; $i < $ledgerCount; $i++) {
    $response = makePostRequest($createLedgerUrl, []);
    $body = json_decode($response['response'], true);
    if ($response['http_code'] == 201 && isset($body['id'])) {
        $ledgers[] = $body['id'];
        echo "Created ledger {$i} out of {$ledgerCount}" . PHP_EOL;
    } else {
        echo "Ledger creation failed: {$response['http_code']}, {$response['error']}" . PHP_EOL;
    }
}
if (empty($ledgers)) {
    die("Ledger creation failed.\n");
}

$multiHandle = curl_multi_init();
$handles = [];
$ledgerIndex = 0;

$startTime = time();
$endTime = $startTime + $duration;
echo "Starting benchmark..." . PHP_EOL;
while (time() < $endTime) {
    $ledgerId = $ledgers[$ledgerIndex];
    $ledgerIndex = ($ledgerIndex + 1) % $ledgerCount;

    $operationData = [
        'ledgerId' => $ledgerId,
        'operationType' => 'debit',
        'amount' => 0.3,
        'currency' => 'UAH',
        'transactionId' => uniqid('')
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $operationUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($operationData),
        CURLOPT_HTTPHEADER => [
            "accept: application/json",
            "content-type: application/json"
        ],
    ]);

    curl_multi_add_handle($multiHandle, $ch);
    $handles[] = $ch;
    if (count($handles) >= 10) {
        executeMultiHandle($multiHandle, $handles, $successCount, $failureCount);
        $handles = [];
    }
}
if (!empty($handles)) {
    executeMultiHandle($multiHandle, $handles, $successCount, $failureCount);
}

curl_multi_close($multiHandle);
$totalRequests = $successCount + $failureCount;
$successRate = ($successCount / $totalRequests) * 100;

echo PHP_EOL;
echo "Finished benchmark:" . PHP_EOL;
echo "Duration: {$duration} seconds" . PHP_EOL;
echo "Total requests: {$totalRequests}" . PHP_EOL;
echo "Successful requests: {$successCount}" . PHP_EOL;
echo "Failed requests: {$failureCount}" . PHP_EOL;

function executeMultiHandle($multiHandle, &$handles, &$successCount, &$failureCount)
{
    do {
        $status = curl_multi_exec($multiHandle, $active);
        curl_multi_select($multiHandle);
    } while ($active && $status == CURLM_OK);

    foreach ($handles as $ch) {
        $response = curl_multi_getcontent($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        if ($httpCode == 200 && empty($error)) {
            $successCount++;
        } else {
            $failureCount++;
            echo "Error: $httpCode, $error" . PHP_EOL;
        }

        curl_multi_remove_handle($multiHandle, $ch);
        curl_close($ch);
        usleep(3000);
    }
}

function makePostRequest($url, $data): array
{
    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            "accept: application/json",
            "content-type: application/json"
        ],
    ]);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);

    curl_close($curl);

    return [
        'response' => $response,
        'http_code' => $httpCode,
        'error' => $error
    ];
}
