<?php
include __DIR__ . '/config/db.php';

$office_name = $_GET['office_name'] ?? '';

$stmt = $conn->prepare("SELECT name FROM sub_offices WHERE office_name = ?");
$stmt->bind_param("s", $office_name);
$stmt->execute();
$result = $stmt->get_result();

$sub_offices = [];
while ($row = $result->fetch_assoc()) {
    $sub_offices[] = $row;
}
echo json_encode($sub_offices);
?>