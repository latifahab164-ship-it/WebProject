<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connect to the Part 3 database
$conn = new mysqli("localhost", "root", "root", "LibraryDatabase2");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Note: the add/remove favourite code was moved to favouriteBooks.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Library</title>
    <link rel="stylesheet" href="libraryStyle.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
    <header>
        <img src="Images/logo.png" alt="Logo">
        <h1>Wonder BookStore</h1>

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
        <!-- Book search box and results -->
        <section class="search-section">
            <input type="text" id="searchBox" placeholder="Search for a book by title..." autocomplete="off">
            <div id="searchResults" hidden></div>
        </section>

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
                $hasFavs = ($favResult->num_rows > 0);
                ?>

                <!-- The list is always on the page so JavaScript can add books to it -->
                <ul class="horizontal-scroll" id="favList" <?php if (!$hasFavs) echo 'hidden'; ?>>
                    <?php
                    while ($book = $favResult->fetch_assoc()) {
                        $isbn = $book['ISBN'];
                        $imagePath = "BookCovers/" . $book['cat_name'] . "/" . $isbn . ".png";

                        echo '<li data-isbn="' . htmlspecialchars($isbn) . '">';
                        echo '<a href="displayBook.php?isbn=' . urlencode($isbn) . '">';
                        echo '<img src="' . htmlspecialchars($imagePath) . '" alt="' . htmlspecialchars($book['title']) . '">';
                        echo '</a>';
                        echo '</li>';
                    }
                    $favStmt->close();
                    ?>
                </ul>

                <p id="noFav" <?php if ($hasFavs) echo 'hidden'; ?>>No favourites yet</p>
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

                    // data-title and data-cover are used by JavaScript to add the book to the favourites section
                    echo '<form method="POST" action="favouriteBooks.php" class="fav-form"'
                        . ' data-title="' . htmlspecialchars($book['title']) . '"'
                        . ' data-cover="' . htmlspecialchars($imagePath) . '">';
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

    <script>
    $(document).ready(function () {

        // ===== Favourite button (AJAX) =====
        $(".fav-form").on("submit", function (event) {
            event.preventDefault(); // stop the normal form submit (no page reload)

            var form = $(this);
            var isbn = form.find("input[name='book_isbn']").val();
            var action = form.find("input[name='action']").val();

            $.ajax({
                url: "favouriteBooks.php",
                method: "POST",
                data: form.serialize(),
                dataType: "json",
                success: function (response) {
                    if (response.success) {
                        if (action === "add") {
                            // Toggle the heart and the next action
                            form.find(".fav-icon").attr("src", "Images/heart_filled.png");
                            form.find("input[name='action']").val("remove");

                            // Add the book to the favourites section
                            var li = $("<li>").attr("data-isbn", isbn);
                            var link = $("<a>").attr("href", "displayBook.php?isbn=" + encodeURIComponent(isbn));
                            var img = $("<img>").attr("src", form.attr("data-cover"))
                                                .attr("alt", form.attr("data-title"));
                            link.append(img);
                            li.append(link);
                            $("#favList").append(li);
                        } else {
                            // Toggle the heart and the next action
                            form.find(".fav-icon").attr("src", "Images/heart_empty.png");
                            form.find("input[name='action']").val("add");

                            // Remove the book from the favourites section
                            $("#favList li").filter(function () {
                                return $(this).attr("data-isbn") === isbn;
                            }).remove();
                        }

                        // Show the list or the "No favourites yet" message
                        if ($("#favList li").length > 0) {
                            $("#favList").prop("hidden", false);
                            $("#noFav").prop("hidden", true);
                        } else {
                            $("#favList").prop("hidden", true);
                            $("#noFav").prop("hidden", false);
                        }
                    } else {
                        alert(response.message);
                    }
                },
                error: function () {
                    alert("Something went wrong. Please try again.");
                }
            });
        });

        // ===== Book search (AJAX) =====
        $("#searchBox").on("input", function () {
            var text = $(this).val().trim();

            // Empty search box: hide the results and do not send a request
            if (text === "") {
                $("#searchResults").prop("hidden", true).empty();
                return;
            }

            $("#searchResults").prop("hidden", false);

            $.ajax({
                url: "bookSearch.php",
                method: "GET",
                data: { q: text },
                dataType: "json",
                success: function (response) {
                    // Ignore old responses if the text has changed in the meantime
                    if ($("#searchBox").val().trim() !== text) {
                        return;
                    }

                    $("#searchResults").empty();

                    if (response.found) {
                        var list = $("<ol>");
                        for (var i = 0; i < response.books.length; i++) {
                            var book = response.books[i];
                            var link = $("<a>")
                                .attr("href", "displayBook.php?isbn=" + encodeURIComponent(book.isbn))
                                .text(book.title);
                            list.append($("<li>").append(link));
                        }
                        $("#searchResults").append(list);
                    } else {
                        $("#searchResults").append($("<p>").text("No books match your search."));
                    }
                },
                error: function () {
                    $("#searchResults").empty().append($("<p>").text("Search failed. Please try again."));
                }
            });
        });
    });
    </script>
</body>
</html>