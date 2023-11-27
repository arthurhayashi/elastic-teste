<?php
// index.php
$logstashHost = 'localhost';
$logstashPort = '5000';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mudar o endpoint para o Logstash
    $client = curl_init("http://$logstashHost:$logstashPort/");

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

    echo "Data sent to Logstash: $response";
}
?>

<form method="post">
    <input type="text" name="message">
    <button type="submit">Send</button>
</form>
