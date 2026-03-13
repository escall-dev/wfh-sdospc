<?php
include __DIR__ . '/config/db.php';

$unit_name = $_GET['office_name'] ?? '';

$stmt = $conn->prepare("SELECT name FROM unit_services WHERE office_name = ?");
$stmt->bind_param("s", $unit_name);
$stmt->execute();
$result = $stmt->get_result();

$services = [];
while ($row = $result->fetch_assoc()) {
    $services[] = $row;
}
echo json_encode($services);
?>
