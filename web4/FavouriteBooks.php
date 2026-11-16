<?php
// favouriteBooks.php - adds or removes a favourite book and returns JSON
session_start();
header("Content-Type: application/json");

$response = array("success" => false, "message" => "");

// The user must be logged in
if (!isset($_SESSION['user_id'])) {
    $response["message"] = "Please login first.";
    echo json_encode($response);
    exit();
}

// The request must be POST and contain the ISBN
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['book_isbn'])) {
    $response["message"] = "Invalid request.";
    echo json_encode($response);
    exit();
}

$userId = $_SESSION['user_id'];
$isbn = trim($_POST['book_isbn']);
$action = isset($_POST['action']) ? $_POST['action'] : "";

$conn = new mysqli("localhost", "root", "root", "LibraryDatabase2");
if ($conn->connect_error) {
    $response["message"] = "Database connection failed.";
    echo json_encode($response);
    exit();
}
$conn->set_charset("utf8mb4");

if ($action === 'add') {
    // Add the book to the user's favourites
    $stmt = $conn->prepare("INSERT IGNORE INTO Favourites (userID, bookISBN) VALUES (?, ?)");
    $stmt->bind_param("is", $userId, $isbn);
} elseif ($action === 'remove') {
    // Remove the book from the user's favourites
    $stmt = $conn->prepare("DELETE FROM Favourites WHERE userID = ? AND bookISBN = ?");
    $stmt->bind_param("is", $userId, $isbn);
} else {
    $response["message"] = "Invalid action.";
    echo json_encode($response);
    $conn->close();
    exit();
}

if ($stmt->execute()) {
    $response["success"] = true;
    $response["message"] = "Done.";
} else {
    $response["message"] = "The operation failed.";
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>