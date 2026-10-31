<?php
// displayBook.php - shows the details of one book, selected by ISBN in the query string

$conn = new mysqli("localhost", "root", "root", "LibraryDatabase2");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// 1. Check the ISBN sent in the query string
$isbn = isset($_GET['isbn']) ? trim($_GET['isbn']) : '';
$book = null;

if ($isbn !== '' && strlen($isbn) <= 30) {
    // 2. Retrieve all of the book's data (prepared statement)
    $stmt = $conn->prepare(
        "SELECT Books.ISBN, Books.title, Books.authors, Books.description, Category.category
         FROM Books
         JOIN Category ON Books.categoryID = Category.ID
         WHERE Books.ISBN = ?"
    );
    $stmt->bind_param("s", $isbn);
    $stmt->execute();
    $result = $stmt->get_result();
    $book = $result->fetch_assoc();
    $stmt->close();
}

$conn->close();

if (!$book) {
    http_response_code(404);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $book ? htmlspecialchars($book['title']) : 'Book Not Found'; ?></title>
    <link rel="stylesheet" href="css.css">
</head>
<body>
    <header>
        <a href="index.php" class="back-btn">&larr; Back</a>
        <img src="Images/logo.png" alt="Logo">
        <h1>Book Library Name</h1>
    </header>

    <main class="book-details">
        <?php if ($book): ?>
            <h2><?php echo htmlspecialchars($book['title']); ?></h2>
            <h3>By: <?php echo htmlspecialchars($book['authors']); ?></h3>

            <?php
            // Same cover path convention as index.php: BookCovers/<category>/<ISBN>.png
            $imagePath = "BookCovers/" . $book['category'] . "/" . $book['ISBN'] . ".png";
            ?>
            <?php if (file_exists($imagePath)): ?>
                <img src="<?php echo htmlspecialchars($imagePath); ?>"
                     alt="<?php echo htmlspecialchars($book['title']); ?>">
            <?php else: ?>
                <p class="no-cover">Cover image not available.</p>
            <?php endif; ?>

            <p><?php echo nl2br(htmlspecialchars($book['description'])); ?></p>
        <?php else: ?>
            <h2>Book not found</h2>
            <p class="error-message">
                <?php echo ($isbn === '')
                    ? 'No book was selected.'
                    : 'No book matches the ISBN you requested.'; ?>
            </p>
            <p><a href="index.php">Return to all library books</a></p>
        <?php endif; ?>
    </main>
</body>
</html>