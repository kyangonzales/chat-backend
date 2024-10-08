<?php
include "../../connection/connection.php";

$accountId = $_GET['accountId'];

$sql = "SELECT id FROM customers WHERE accountId = $accountId";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode(['success' => true, 'customerId' => $row['id']]);
} else {
    echo json_encode(['success' => false]);
}
?>
