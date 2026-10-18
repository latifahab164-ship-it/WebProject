<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli("localhost", "root", "root", "LibraryDatabase");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book Library</title>
    <link rel="stylesheet" href="libraryStyle.css">
</head>
<body>
    <header>
        <img src="Images/logo.png" alt="Logo">
        <h1>Book Library Name</h1>
    </header>

    <main>
        <h2>All Library Books</h2>
        
        <?php
        $catResult = $conn->query("SELECT * FROM Category");
        while ($cat = $catResult->fetch_assoc()) {
            echo "<h3>Books in " . $cat['category'] . " Category</h3>";
            echo '<ul class="horizontal-scroll">';
            
            $catID = $cat['ID'];
            $booksResult = $conn->query("SELECT * FROM Books WHERE categoryID = $catID");
            
            while ($book = $booksResult->fetch_assoc()) {
                $isbn = $book['ISBN'];
                $imagePath = "BookCovers/" . $cat['category'] . "/" . $isbn . ".png";
                
                echo '<li>';
                echo '<a href="displayBook.php?isbn=' . $isbn . '">';
                echo '<img src="' . $imagePath . '" alt="' . $book['title'] . '">';
                echo '</a>';
                echo '</li>';
            }
            echo '</ul>';
        }
        $conn->close();
        ?>
    </main>
</body>
</html>