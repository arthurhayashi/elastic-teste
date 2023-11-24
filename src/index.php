<?php
// index.php

$esHost = 'elasticsearch'; // Nome do serviço Elasticsearch no docker-compose
$esPort = '9200';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client = curl_init("http://$esHost:$esPort/my_index/_doc/");
    $data = json_encode(['message' => $_POST['message']]);

    curl_setopt($client, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($client, CURLOPT_POSTFIELDS, $data);
    curl_setopt($client, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($client, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data)
    ]);

    $response = curl_exec($client);
    curl_close($client);

    echo "Data sent to Elasticsearch: $response";
}
?>

<form method="post">
    <input type="text" name="message">
    <button type="submit">Send</button>
</form>
