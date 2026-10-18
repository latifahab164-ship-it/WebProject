<?php
$conn = new mysqli("localhost", "root", "root", "LibraryDatabase");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$isbn = $_GET['isbn'] ?? '';

$stmt = $conn->prepare("SELECT Books.*, Category.category FROM Books JOIN Category ON Books.categoryID = Category.ID WHERE Books.ISBN = ?");
$stmt->bind_param("s", $isbn);
$stmt->execute();
$result = $stmt->get_result();
$book = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $book['title'] ?? 'Book Details'; ?></title>
    <link rel="stylesheet" href="libraryStyle.css">
</head>
<body>
    <header>
        <a href="index.php" class="back-btn">&larr; Back</a>
        <img src="Images/logo.png" alt="Logo">
        <h1>Book Library Name</h1>
    </header>

    <main class="book-details">
        <?php if ($book): ?>
            <h2><?php echo $book['title']; ?></h2>
            <h3>By: <?php echo $book['authors']; ?></h3>
            
            <?php 
            $imagePath = "BookCovers/" . $book['category'] . "/" . $book['ISBN'] . ".png";
            ?>
            <img src="<?php echo $imagePath; ?>" alt="<?php echo $book['title']; ?>">
            
            <p><?php echo $book['description']; ?></p>
        <?php else: ?>
            <p>Book not found.</p>
        <?php endif; ?>
        
        <?php 
        $stmt->close();
        $conn->close();
        ?>
    </main>
</body>
</html>