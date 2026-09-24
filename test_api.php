<?php

$url = "http://localhost/absensi/api_absen.php";

$data = [
    "fingerprint_id" => 3
];

$options = [
    "http" => [
        "header" =>
            "Content-Type: application/json\r\n",

        "method" => "POST",

        "content" =>
            json_encode($data)
    ]
];

$context =
    stream_context_create($options);

$response =
    file_get_contents(
        $url,
        false,
        $context
    );

echo "<h2>Hasil Simulasi Fingerprint</h2>";

echo "<pre>";
echo htmlspecialchars($response);
echo "</pre>";

?>