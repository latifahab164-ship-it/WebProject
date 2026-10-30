<?php
session_start();
error_reporting(E_ALL);
log: ini_set('display_errors', 1);

// استخدام قاعدة البيانات الجديدة المطلوبة في الجزء الثالث
$conn = new mysqli("localhost", "root", "root", "LibraryDatabase2");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 1. معالجة طلب الـ POST لإضافة أو حذف كتاب من المفضلة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_isbn'])) {
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        $isbn = $conn->real_escape_string($_POST['book_isbn']);
        $action = $_POST['action']; // 'add' أو 'remove'

        if ($action === 'add') {
            // إضافة الكتاب للمفضلة
            $stmt = $conn->prepare("INSERT IGNORE INTO Favourites (userID, bookISBN) VALUES (?, ?)");
            $stmt->bind_param("is", $userId, $isbn);
            $stmt->execute();
            $stmt->close();
        } elseif ($action === 'remove') {
            // حذف الكتاب من المفضلة
            $stmt = $conn->prepare("DELETE FROM Favourites WHERE userID = ? AND bookISBN = ?");
            $stmt->bind_param("is", $userId, $isbn);
            $stmt->execute();
            $stmt->close();
        }
        
        // إعادة توجيه لنفس الصفحة لتجنب إعادة إرسال النموذج عند التحديث
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
</head>
<body>
    <header>
        <img src="Images/logo.png" alt="Logo">
        <h1>Book Library Name</h1>
        
        <!-- 2. روابط تسجيل الدخول أو الترحيب بالمستخدم -->
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
        <!-- 3. قسم الكتب المفضلة (يظهر فقط إذا كان المستخدم مسجل دخول) -->
        <?php if (isset($_SESSION['user_id'])): ?>
            <section class="favourites-section">
                <h2>My Favourite Books</h2>
                <ul class="horizontal-scroll">
                    <?php
                    $userId = $_SESSION['user_id'];
                    $favQuery = "SELECT Books.*, Category.category AS cat_name FROM Books 
                                 JOIN Favourites ON Books.ISBN = Favourites.bookISBN 
                                 JOIN Category ON Books.categoryID = Category.ID 
                                 WHERE Favourites.userID = $userId";
                    $favResult = $conn->query($favQuery);

                    if ($favResult && $favResult->num_rows > 0) {
                        while ($book = $favResult->fetch_assoc()) {
                            $isbn = $book['ISBN'];
                            $imagePath = "BookCovers/" . $book['cat_name'] . "/" . $isbn . ".png";
                            
                            echo '<li>';
                            echo '<a href="displayBook.php?isbn=' . $isbn . '">';
                            echo '<img src="' . $imagePath . '" alt="' . $book['title'] . '">';
                            echo '</a>';
                            echo '</li>';
                        }
                    } else {
                        echo '<p>No favourites yet</p>';
                    }
                    ?>
                </ul>
            </section>
        <?php endif; ?>

        <!-- 4. قسم جميع الكتب (الكود القديم مضافاً إليه أزرار المفضلة) -->
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

                // زر المفضلة (يظهر فقط إذا كان المستخدم مسجل دخول)
                if (isset($_SESSION['user_id'])) {
                    // التحقق هل الكتاب مضاف مسبقاً لمفضلة هذا المستخدم
                    $checkFav = $conn->query("SELECT * FROM Favourites WHERE userID = $userId AND bookISBN = '$isbn'");
                    $isFav = ($checkFav && $checkFav->num_rows > 0);

                    $action = $isFav ? 'remove' : 'add';
                    // استبدلي أسماء الصور بالأيقونات المتوفرة عندك في مجلد Images
                    $heartImg = $isFav ? 'Images/heart_filled.png' : 'Images/heart_empty.png';

                    echo '<form method="POST" action="index.php" style="margin-top: 5px; text-align: center;">';
                    echo '<input type="hidden" name="book_isbn" value="' . $isbn . '">';
                    echo '<input type="hidden" name="action" value="' . $action . '">';
                    echo '<button type="submit" style="background:none; border:none; cursor:pointer;">';
                    echo '<img src="' . $heartImg . '" alt="Favourite" style="width: 24px; height: 24px;">';
                    echo '</button>';
                    echo '</form>';
                }

                echo '</li>';
            }
            echo '</ul>';
        }
        $conn->close();
        ?>
    </main>
</body>
</html>