<?php
include __DIR__ . '/config/db.php';

$sub_office_name = $_GET['sub_office_name'] ?? '';

$stmt = $conn->prepare("SELECT name FROM services WHERE sub_office_name = ?");
$stmt->bind_param("s", $sub_office_name);
$stmt->execute();
$result = $stmt->get_result();

$services = [];
while ($row = $result->fetch_assoc()) {
    $services[] = $row;
}
echo json_encode($services);
?>
