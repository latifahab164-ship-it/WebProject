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

// 3. Get the book reviews from the Web API using cURL (GET request)
$reviews = array();
$reviewMessage = "";

if ($book) {
    $apiUrl = "https://book-review-api-phi.vercel.app/BookReviewAPI?isbn=" . urlencode($book['ISBN']);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    $apiResponse = curl_exec($ch);
    $curlError = curl_errno($ch);
    curl_close($ch);

    if ($curlError || $apiResponse === false) {
        $reviewMessage = "Reviews are not available right now.";
    } else {
        $data = json_decode($apiResponse, true);

        if (is_array($data) && isset($data['status']) && $data['status'] === "Success"
            && isset($data['reviews']) && is_array($data['reviews'])) {
            $reviews = $data['reviews'];
            if (count($reviews) === 0) {
                $reviewMessage = "There are no reviews for this book yet.";
            }
        } else {
            $reviewMessage = "No reviews were found for this book.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $book ? htmlspecialchars($book['title']) : 'Book Not Found'; ?></title>
    <link rel="stylesheet" href="libraryStyle.css">
</head>
<body>
    <header>
        <a href="index.php" class="back-btn">&larr; Back</a>
        <img src="Images/logo.png" alt="Logo">
        <h1>Wonder BookStore</h1>
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

            <!-- Book reviews from the Web API -->
            <section class="reviews-section">
                <h2>Book Reviews</h2>

                <?php if ($reviewMessage !== ""): ?>
                    <p class="review-message"><?php echo htmlspecialchars($reviewMessage); ?></p>
                <?php else: ?>
                    <?php foreach ($reviews as $r): ?>
                        <div class="review">
                            <h4><?php echo htmlspecialchars(isset($r['reviewer']) ? $r['reviewer'] : 'Anonymous'); ?></h4>
                            <p><?php echo htmlspecialchars(isset($r['review']) ? $r['review'] : ''); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
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