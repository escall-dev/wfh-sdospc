<?php
include __DIR__ . '/config/db.php';
$result = $conn->query("SELECT name FROM offices");
$offices = [];

while ($row = $result->fetch_assoc()) {
    $offices[] = $row;
}
echo json_encode($offices);
?>