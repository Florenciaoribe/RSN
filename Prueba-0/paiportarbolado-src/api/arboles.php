<?php

require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');

$sql = "SELECT * FROM arboles";

$result = $conn->query($sql);

$arboles = [];

while ($row = $result->fetch_assoc()) {
    $arboles[] = $row;
}

echo json_encode($arboles, JSON_UNESCAPED_UNICODE);

$conn->close();
?>
