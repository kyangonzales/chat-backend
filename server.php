<?php
require __DIR__ . '/vendor/autoload.php';


use Ratchet\WebSocket\WsServer;
use Ratchet\HTTP\HttpServer;
use Ratchet\WebSocket\WsServerInterface;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;

class SimpleChat implements MessageComponentInterface
{
    protected $clients;
    protected $userConnections;
    protected $db;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->userConnections = [];
        $this->db = new PDO('mysql:host=localhost;dbname=capstonebilling', 'root', '');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg);
        $senderId = $data->senderId;
        $receiverId = $data->receiverId;
        $message = $data->message;
        date_default_timezone_set('Asia/Manila');

        $createdAt = new DateTime();
        $createdAtFormatted = $createdAt->format('n/j/Y, g:i:s A');
        // Save message to the database
        $stmt = $this->db->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$senderId, $receiverId, $message]);

        // Register the connection with the sender's user ID
        $this->userConnections[$senderId] = $from;
        // Prepare data to send, including timestamp
        $dataToSend = [
            'senderId' => $senderId,
            'receiverId' => $receiverId,
            'message' => $message,
            'created_at' => $createdAtFormatted // Include timestamp
        ];

        // Send the message only to the intended recipient
        if (isset($this->userConnections[$receiverId])) {
            $recipient = $this->userConnections[$receiverId];
            $recipient->send(json_encode($dataToSend));
        }

        // Optionally, send an acknowledgment to the sender
        $from->send(json_encode(['status' => 'Message sent']));
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        // Remove the connection from the userConnections array
        $userId = array_search($conn, $this->userConnections);
        if ($userId !== false) {
            unset($this->userConnections[$userId]);
        }
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new SimpleChat()
        )
    ),
    8080
);

$server->run();