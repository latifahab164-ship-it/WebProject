<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connect to the Part 3 database
$conn = new mysqli("localhost", "root", "root", "LibraryDatabase2");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle the POST request to add or remove a favourite book
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_isbn'])) {
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        $isbn = $_POST['book_isbn'];
        $action = isset($_POST['action']) ? $_POST['action'] : "";

        if ($action === 'add') {
            // Add the book to the user's favourites
            $stmt = $conn->prepare("INSERT IGNORE INTO Favourites (userID, bookISBN) VALUES (?, ?)");
            $stmt->bind_param("is", $userId, $isbn);
            $stmt->execute();
            $stmt->close();
        } elseif ($action === 'remove') {
            // Remove the book from the user's favourites
            $stmt = $conn->prepare("DELETE FROM Favourites WHERE userID = ? AND bookISBN = ?");
            $stmt->bind_param("is", $userId, $isbn);
            $stmt->execute();
            $stmt->close();
        }

        // Redirect to the same page to avoid form resubmission on refresh
        header("Location: index.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book Library</title>
    <link rel="stylesheet" href="libraryStyle.css">
    <link rel="stylesheet" href="css.css">
</head>
<body>
    <header>
        <img src="Images/logo.png" alt="Logo">
        <h1>Book Library Name</h1>

        <!-- Login link, or welcome note and logout link -->
        <div class="auth-section">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="logout.php" class="logout-link">Logout</a>
            <?php else: ?>
                <a href="login.php" class="login-link">Login / Sign Up</a>
            <?php endif; ?>
        </div>
    </header>

    <main>
        <!-- Favourite books section (only if the user is logged in) -->
        <?php if (isset($_SESSION['user_id'])): ?>
            <section class="favourites-section">
                <h2>My Favourite Books</h2>
                <?php
                $userId = $_SESSION['user_id'];

                $favStmt = $conn->prepare("SELECT Books.ISBN, Books.title, Category.category AS cat_name
                                           FROM Books
                                           JOIN Favourites ON Books.ISBN = Favourites.bookISBN
                                           JOIN Category ON Books.categoryID = Category.ID
                                           WHERE Favourites.userID = ?");
                $favStmt->bind_param("i", $userId);
                $favStmt->execute();
                $favResult = $favStmt->get_result();

                if ($favResult->num_rows > 0) {
                    echo '<ul class="horizontal-scroll">';
                    while ($book = $favResult->fetch_assoc()) {
                        $isbn = $book['ISBN'];
                        $imagePath = "BookCovers/" . $book['cat_name'] . "/" . $isbn . ".png";

                        echo '<li>';
                        echo '<a href="displayBook.php?isbn=' . urlencode($isbn) . '">';
                        echo '<img src="' . htmlspecialchars($imagePath) . '" alt="' . htmlspecialchars($book['title']) . '">';
                        echo '</a>';
                        echo '</li>';
                    }
                    echo '</ul>';
                } else {
                    echo '<p>No favourites yet</p>';
                }
                $favStmt->close();
                ?>
            </section>
        <?php endif; ?>

        <!-- All books section, with favourite buttons when logged in -->
        <h2>All Library Books</h2>

        <?php
        // Prepare the "is this book a favourite?" query once (only if logged in)
        $checkStmt = null;
        if (isset($_SESSION['user_id'])) {
            $checkStmt = $conn->prepare("SELECT bookISBN FROM Favourites WHERE userID = ? AND bookISBN = ?");
        }

        $catResult = $conn->query("SELECT * FROM Category");
        while ($cat = $catResult->fetch_assoc()) {
            echo "<h3>Books in " . htmlspecialchars($cat['category']) . " Category</h3>";
            echo '<ul class="horizontal-scroll">';

            $catID = $cat['ID'];
            $booksResult = $conn->query("SELECT * FROM Books WHERE categoryID = $catID");

            while ($book = $booksResult->fetch_assoc()) {
                $isbn = $book['ISBN'];
                $imagePath = "BookCovers/" . $cat['category'] . "/" . $isbn . ".png";

                echo '<li>';
                echo '<a href="displayBook.php?isbn=' . urlencode($isbn) . '">';
                echo '<img src="' . htmlspecialchars($imagePath) . '" alt="' . htmlspecialchars($book['title']) . '">';
                echo '</a>';

                // Favourite button (only if the user is logged in)
                if (isset($_SESSION['user_id'])) {
                    // Check whether this book is already in the user's favourites
                    $checkStmt->bind_param("is", $userId, $isbn);
                    $checkStmt->execute();
                    $checkResult = $checkStmt->get_result();
                    $isFav = ($checkResult->num_rows > 0);

                    $action = $isFav ? 'remove' : 'add';
                    $heartImg = $isFav ? 'Images/heart_filled.png' : 'Images/heart_empty.png';

                    echo '<form method="POST" action="index.php" class="fav-form">';
                    echo '<input type="hidden" name="book_isbn" value="' . htmlspecialchars($isbn) . '">';
                    echo '<input type="hidden" name="action" value="' . $action . '">';
                    echo '<button type="submit" class="fav-button">';
                    echo '<img src="' . $heartImg . '" alt="Favourite" class="fav-icon" width="24" height="24">';
                    echo '</button>';
                    echo '</form>';
                }

                echo '</li>';
            }
            echo '</ul>';
        }

        if ($checkStmt !== null) {
            $checkStmt->close();
        }
        $conn->close();
        ?>
    </main>
</body>
</html>