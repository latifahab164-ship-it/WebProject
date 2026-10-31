<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connect to the database
$conn = new mysqli("localhost", "root", "root", "LibraryDatabase2");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

$error = "";
$action = "";

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = isset($_POST["action"]) ? $_POST["action"] : "";

    // =========================
    // SIGN UP
    // =========================
    if ($action === "signup") {

        $name = isset($_POST["name"]) ? trim($_POST["name"]) : "";
        $email = isset($_POST["email"]) ? trim($_POST["email"]) : "";
        $password = isset($_POST["password"]) ? $_POST["password"] : "";

        if ($name === "" || $email === "" || $password === "") {
            $error = "Please fill in all fields.";
        } else {

            // Check if the email is already registered
            $stmt = $conn->prepare(
                "SELECT ID FROM Users WHERE email = ?"
            );

            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {

                $error = "This email is already registered.";
                $stmt->close();

            } else {

                $stmt->close();

                // Hash the password before storing it
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Add the new user
                $stmt = $conn->prepare(
                    "INSERT INTO Users (name, email, password)
                     VALUES (?, ?, ?)"
                );

                $stmt->bind_param(
                    "sss",
                    $name,
                    $email,
                    $hashedPassword
                );

                if ($stmt->execute()) {

                    // Save user's ID and name in the session
                    $_SESSION["user_id"] = $conn->insert_id;
                    $_SESSION["user_name"] = $name;

                    $stmt->close();
                    $conn->close();

                    // Redirect to the home page
                    header("Location: index.php");
                    exit();

                } else {
                    $error = "Signup failed. Please try again.";
                    $stmt->close();
                }
            }
        }
    }

    // =========================
    // LOGIN
    // =========================
    elseif ($action === "login") {

        $email = isset($_POST["email"]) ? trim($_POST["email"]) : "";
        $password = isset($_POST["password"]) ? $_POST["password"] : "";

        if ($email === "" || $password === "") {

            $error = "Please fill in all fields.";

        } else {

            // Find the user by email
            $stmt = $conn->prepare(
                "SELECT ID, name, password
                 FROM Users
                 WHERE email = ?"
            );

            $stmt->bind_param("s", $email);
            $stmt->execute();

            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            // Check email and password
            if ($user && password_verify($password, $user["password"])) {

                // Save user's ID and name in the session
                $_SESSION["user_id"] = $user["ID"];
                $_SESSION["user_name"] = $user["name"];

                $stmt->close();
                $conn->close();

                // Redirect to the home page
                header("Location: index.php");
                exit();

            } else {

                $error = "Incorrect email or password.";
                $stmt->close();
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - Book Library</title>

    <link rel="stylesheet" href="libraryStyle.css">
    <link rel="stylesheet" href="css.css">
</head>

<body>

<header>

    <img src="Images/logo.png" alt="Logo">

    <h1>Book Library Name</h1>
</header>


<main class="login-page">

    <h2>User Account</h2>

    <?php if ($error !== ""): ?>
        <p class="error-message">
            <?php echo htmlspecialchars($error); ?>
        </p>
    <?php endif; ?>


    <!-- =========================
         LOGIN FORM
         ========================= -->

    <div id="loginForm">

        <h2>Login</h2>

        <form method="POST" action="login.php">

            <input
                type="hidden"
                name="action"
                value="login"
            >

            <label for="loginEmail">Email:</label>

            <input
                type="email"
                id="loginEmail"
                name="email"
                required
            >

            <label for="loginPassword">Password:</label>

            <input
                type="password"
                id="loginPassword"
                name="password"
                required
            >

            <button type="submit">
                Login
            </button>

        </form>

        <p>
            Don't have an account?

            <button
                type="button"
                onclick="showSignup()"
            >
                Sign Up
            </button>
        </p>

    </div>


    <!-- =========================
         SIGNUP FORM
         ========================= -->

    <div id="signupForm" hidden>

        <h2>Sign Up</h2>

        <form method="POST" action="login.php">

            <input
                type="hidden"
                name="action"
                value="signup"
            >

            <label for="signupName">Name:</label>

            <input
                type="text"
                id="signupName"
                name="name"
                required
            >

            <label for="signupEmail">Email:</label>

            <input
                type="email"
                id="signupEmail"
                name="email"
                required
            >

            <label for="signupPassword">Password:</label>

            <input
                type="password"
                id="signupPassword"
                name="password"
                required
            >

            <button type="submit">
                Sign Up
            </button>

        </form>

        <p>
            Already have an account?

            <button
                type="button"
                onclick="showLogin()"
            >
                Login
            </button>
        </p>

    </div>

</main>


<script>

function showSignup() {

    document.getElementById("loginForm").hidden = true;

    document.getElementById("signupForm").hidden = false;
}


function showLogin() {

    document.getElementById("signupForm").hidden = true;

    document.getElementById("loginForm").hidden = false;
}

<?php if ($error !== "" && $action === "signup"): ?>
// A signup error occurred, so keep the signup form visible
showSignup();
<?php endif; ?>

</script>

</body>
</html>