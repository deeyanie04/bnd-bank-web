<?php
session_start();
include 'db.php'; // your RDS connection file

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $enteredUsername = trim($_POST['uname']);
    $enteredPassword = trim($_POST['password']);

    // Prepare SQL to prevent SQL injection
    $stmt = $mysqli->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $enteredUsername);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $stmt->bind_result($userId, $hashedPassword);
        $stmt->fetch();

        // For demo: plain text password, for production: use password_verify
        if ($enteredPassword === $hashedPassword) {
            $_SESSION['username'] = $enteredUsername;
            $_SESSION['user_id'] = $userId;
            $_SESSION['loggedin'] = true;

            header("Location: bank.php");
            exit();
        } else {
            $loginError = "Incorrect username or password.";
        }
    } else {
        $loginError = "Incorrect username or password.";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login Page</title>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
body { background: #e2688a; margin: 0; display:flex; align-items:center; justify-content:center; height:100vh; font-family: Verdana, sans-serif; }
.container { display: flex; width: 70%; border-radius: 10px; overflow: hidden; height: 80vh; box-shadow: 0 0 5px rgba(0,0,0,0.5); }
.left-section { flex: 1; display: flex; align-items: center; justify-content: center; background: linear-gradient(-45deg,#FFFDD0,#FAAAC9,#F294B4,#E2688A,#D85375,#E2688A,#F294B4,#FAAAC9,#FFFDD0); background-size: 400% 400%; animation: gradientChange 7s infinite; }
@keyframes gradientChange { 0% {background-position:0% 50%} 50%{background-position:100% 50%} 100% {background-position:0% 50%} }
.left-content { color:white; text-align:center; }
.right-section { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:20px; background:#f3f5f9; }
h1 { color:#d85375; text-align:center; }
input[type=text], input[type=password] { width:100%; padding:10px; margin:8px 0; border:2px solid #D85375; border-radius:5px; }
input[type=submit] { background-color:#d85375; color:white; padding:10px; width:100%; font-size:20px; border-radius:50px; border:2px solid #D85375; cursor:pointer; }
.login-image { display:block; margin: 0 auto 10px auto; width:110px; height:110px; }
</style>
</head>
<body>

<div class="container">
    <div class="left-section">
        <div class="left-content">
            <img src="https://https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/cover-photo.png" alt="Cover Photo">
        </div>
    </div>
    <div class="right-section">
        <img class="login-image" src="https://https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/bndlogo.png" alt="BND Logo">
        <h1>LOGIN</h1>
        <form method="post">
            <input type="text" name="uname" placeholder="Enter Username" required><br>
            <input type="password" name="password" id="password" placeholder="Enter Password" required><br>
            <label><input type="checkbox" onchange="togglePassword()"> Show Password</label><br><br>
            <input type="submit" value="LOGIN">
        </form>
    </div>
</div>

<script>
function togglePassword() {
    var pw = document.getElementById("password");
    pw.type = pw.type === "password" ? "text" : "password";
}

<?php if (!empty($loginError)) : ?>
Swal.fire({
    icon: 'error',
    title: 'Login Failed',
    text: '<?php echo $loginError; ?>'
});
<?php endif; ?>
</script>

</body>
</html>
