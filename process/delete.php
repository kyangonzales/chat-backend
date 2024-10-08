<?php
include "../../connection/connection.php";
$data = json_decode(file_get_contents('php://input'), true);

$customerId = $data['customerId'];

$sql = "DELETE FROM messages WHERE sender_id = ? OR receiver_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $customerId, $customerId);

$response = array();

if ($stmt->execute()) {
    $response['success'] = true;
} else {
    $response['success'] = false;
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>
