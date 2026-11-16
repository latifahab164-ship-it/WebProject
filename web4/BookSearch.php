<?php
// bookSearch.php - searches books by title and returns JSON
// Response: {"found": true/false, "books": [{"isbn": "...", "title": "..."}]}
header("Content-Type: application/json");

$response = array("found" => false, "books" => array());

// Get the search text from the request
$text = isset($_GET['q']) ? trim($_GET['q']) : "";

if ($text === "") {
    echo json_encode($response);
    exit();
}

$conn = new mysqli("localhost", "root", "root", "LibraryDatabase2");
if ($conn->connect_error) {
    echo json_encode($response);
    exit();
}
$conn->set_charset("utf8mb4");

// Search for titles that contain the text
$search = "%" . $text . "%";
$stmt = $conn->prepare("SELECT ISBN, title FROM Books WHERE title LIKE ?");
$stmt->bind_param("s", $search);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $response["books"][] = array("isbn" => $row['ISBN'], "title" => $row['title']);
}

if (count($response["books"]) > 0) {
    $response["found"] = true;
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>