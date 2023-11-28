<?php
// index.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$logstashHost = 'logstash'; // Nome do serviço Logstash no docker-compose
$logstashPort = '5000';     // Porta configurada para o input HTTP do Logstash

$esHost = 'elasticsearch'; // Endereço do Elasticsearch
$esPort = '9200';          // Porta padrão do Elasticsearch
$index = 'meu_indice';     // Nome do índice no Elasticsearch

// Verificar o método da requisição e a ação
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'send') {
        // Enviar dados para o Logstash
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
    } elseif ($action === 'update') {
        // Atualizar documento no Elasticsearch
        $documentId = $_POST['documentId'];
        $message = $_POST['message'];
        $updatedData = json_encode(['doc' => ['message' => $message]]);
        $updateUrl = "http://$esHost:$esPort/$index/_update/$documentId";
        $ch = curl_init($updateUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $updatedData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($updatedData)
        ]);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Curl error: ' . curl_error($ch);
        }
        curl_close($ch);
        $responseArray = json_decode($response, true);
        echo "<pre>Resposta do Elasticsearch: " . json_encode($responseArray, JSON_PRETTY_PRINT) . "</pre>";
    } elseif($action === 'delete') {
        // Deletar documento no Elasticsearch
        $documentId = $_POST['documentId'];
        $deleteUrl = "http://$esHost:$esPort/$index/_doc/$documentId";

        // Inicializar cURL
        $ch = curl_init($deleteUrl);

        // Configurar opções do cURL
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Executar a requisição
        $response = curl_exec($ch);

        // Verificar se ocorreu algum erro
        if (curl_errno($ch)) {
            echo 'Curl error: ' . curl_error($ch);
        } else {
            $responseArray = json_decode($response, true);
            echo "<pre>Documento deletado: " . json_encode($responseArray, JSON_PRETTY_PRINT) . "</pre>";
        }

        // Fechar a conexão cURL
        curl_close($ch);
    }


}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET['message'])) {
    // Preparar a URL de busca
    $searchUrl = "http://$esHost:$esPort/$index/_search";

    // Preparar o corpo da requisição de busca
    $searchBody = json_encode([
        'query' => [
            'match' => [
                'message' => $_GET['message']
            ]
        ]
    ]);

    // Inicializar cURL
    $client = curl_init($searchUrl);
    curl_setopt($client, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($client, CURLOPT_POSTFIELDS, $searchBody);
    curl_setopt($client, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

    // Executar a requisição
    $response = curl_exec($client);

    if ($response === false) {
        echo "cURL Error: " . curl_error($client);
    } else {
        $responseArray = json_decode($response, true);

        // Verificar se houve resultados
        if (isset($responseArray['hits']['hits']) && count($responseArray['hits']['hits']) > 0) {
            echo "<pre>Resultados encontrados: " . json_encode($responseArray['hits']['hits'], JSON_PRETTY_PRINT) . "</pre>";
        } else {
            echo "Nenhum documento encontrado.";
        }
    }

    // Fechar a conexão cURL
    curl_close($client);
}

?>

<form method="post">
    <input type="hidden" name="action" value="send">
    <input type="text" name="message">
    <button type="submit">Enviar para Logstash</button>
</form>

<form method="post">
    <input type="hidden" name="action" value="update">
    <input type="text" name="documentId" placeholder="ID do Documento">
    <input type="text" name="message" placeholder="Nova Mensagem">
    <button type="submit">Atualizar Documento</button>
</form>

<form method="get">
    <input type="text" name="message" placeholder="Mensagem para buscar">
    <button type="submit">Buscar por Mensagem</button>
</form>

<form method="post">
    <input type="hidden" name="action" value="delete">
    <input type="text" name="documentId" placeholder="ID do Documento para Deletar">
    <button type="submit">Deletar Documento</button>
</form>