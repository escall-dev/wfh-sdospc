<?php
include 'db.php';

if (isset($_GET['weekly'])) {
    header('Content-Type: text/csv; charset=utf-8');

    $filename = 'CSM_Submissions_Weekly_' . date('Y-m-d') . '.csv';
    header("Content-Disposition: attachment; filename=\"$filename\"");

    $output = fopen('php://output', 'w');

    // CSV Header
    fputcsv($output, [
        'Sex', 'Age', 'Customer Type', 'Offices', 'Sub Offices', 'Services', 
        'CC1', 'CC2', 'CC3', 
        'SQD1', 'SQD2', 'SQD3', 'SQD4', 'SQD5', 'SQD6', 'SQD7', 'SQD8', 
        'Remarks'
    ]);

    // Fetch last 7 days
    $query = "SELECT sex, age, customer_type, offices, sub_offices, services, 
                     cc1, cc2, cc3, 
                     sqd1, sqd2, sqd3, sqd4, sqd5, sqd6, sqd7, sqd8, remarks
              FROM csm_submission
              WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
              ORDER BY created_at DESC";

    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
    } else {
        fputcsv($output, ['No records found in the last 7 days.']);
    }

    fclose($output);
    exit;
}


//<!-- Weekly CSV Export Button -->
//<form method="get" action="export.php"><button type="submit" name="weekly" value="1" style="padding:10px 20px; font-size:16px; cursor:pointer;">Download Weekly CSM CSV</button></form>