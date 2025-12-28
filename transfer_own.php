<?php
session_start();

// Ensure user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// RDS configuration
$host = "bnd-db.clkymsu642nu.ap-southeast-1.rds.amazonaws.com"; // Your RDS endpoint
$dbname = "bank_db";       // Your database name
$username = "admin";       // Your RDS username
$password = "admin123";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get logged-in user accounts
$user_id = $_SESSION['user_id'] ?? 1; // Ensure user_id is in session
$stmt = $pdo->prepare("SELECT id, account_number, balance FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle transfer POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fromAccountId = $_POST['fromAccount'] ?? '';
    $toAccountId = $_POST['toAccount'] ?? '';
    $amount = floatval($_POST['amount'] ?? 0);

    if ($fromAccountId === $toAccountId) {
        $errorMessage = "Cannot transfer to the same account.";
    } else if ($amount <= 0) {
        $errorMessage = "Invalid amount.";
    } else {
        // Fetch balances
        $stmt = $pdo->prepare("SELECT id, balance FROM users WHERE id IN (?, ?)");
        $stmt->execute([$fromAccountId, $toAccountId]);
        $selectedAccounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $fromAcc = $selectedAccounts[0]['id'] == $fromAccountId ? $selectedAccounts[0] : $selectedAccounts[1];
        $toAcc = $selectedAccounts[0]['id'] == $toAccountId ? $selectedAccounts[0] : $selectedAccounts[1];

        if ($fromAcc['balance'] < $amount) {
            $errorMessage = "Insufficient balance in source account.";
        } else {
            // Begin transaction
            $pdo->beginTransaction();
            try {
                // Deduct from source
                $stmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
                $stmt->execute([$amount, $fromAccountId]);

                // Add to destination
                $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                $stmt->execute([$amount, $toAccountId]);

                // Log transaction
                $refNumber = str_pad(mt_rand(1, 999999999999), 12, '0', STR_PAD_LEFT);
                $stmt = $pdo->prepare("INSERT INTO transactions (sender_id, recipient_id, type, amount, reference, timestamp) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$fromAccountId, $toAccountId, 'Own Transfer', $amount, $refNumber]);

                $pdo->commit();
                $successMessage = "Successfully transferred Php " . number_format($amount,2) . " from account $fromAccountId to $toAccountId. Reference: $refNumber";

                // Refresh accounts
                $stmt = $pdo->prepare("SELECT id, account_number, balance FROM users WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            } catch (Exception $e) {
                $pdo->rollBack();
                $errorMessage = "Transfer failed: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Transfer to Own Account</title>
<link rel="stylesheet" href="styles.css">
<script src="https://kit.fontawesome.com/b99e675b6e.js"></script>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Josefin Sans',sans-serif;}
body{background:#f3f5f9;}
.wrapper{display:flex;position:relative;}
.sidebar{width:300px;height:100%;background:#e2688a;padding:30px 0;position:fixed;}
.sidebar ul li{padding:25px;border-top:1px solid rgba(255,255,255,.05);border-bottom:1px solid rgba(0,0,0,.05);}
.sidebar ul li a{color:#fff;display:block;}
.sidebar ul li:hover{background:#d85375;}
.sidebar .social_media{position:absolute;bottom:0;left:50%;transform:translateX(-50%);display:flex;}
.sidebar .social_media a{width:40px;height:40px;margin:0 5px;text-align:center;line-height:45px;color:#fff;background:#d85375;border-radius:5px 5px 0 0;}
.main_content{width:100%;margin-left:300px;}
.header{margin:5%;background:#f3f5f9;color:#d85375;border-bottom:1px solid #e0e4e8;text-align:center;}
.header p{color:#717171;font-style:italic;font-size:13px;}
.transfer{max-width:600px;margin:5% auto;padding:20px;background:#fff;border-radius:10px;}
.transfer h3{color:#e2688a;margin-bottom:10px;}
.transfer input, .transfer select{width:100%;padding:10px;margin-bottom:15px;border-radius:5px;border:1px solid #ccc;font-size:16px;}
.submit-button{background:#d85375;color:#fff;padding:15px;font-size:16px;border:none;border-radius:5px;cursor:pointer;width:100%;}
.submit-button:hover{background:pink;}
.message{text-align:center;margin-bottom:15px;font-weight:bold;color:green;}
.error{text-align:center;margin-bottom:15px;font-weight:bold;color:red;}
#logo{margin-left:15%; margin-bottom:5%; max-width:200px;}
</style>
</head>
    <style>
    
*{
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  list-style: none;
  text-decoration: none;
  font-family: 'Josefin Sans', sans-serif;
}

body{
   background-color: #f3f5f9;
}

.wrapper{
  display: flex;
  position: relative;
}

.wrapper .sidebar{
  width: 300px;
  height: 100%;
  background:   #e2688a;
  padding: 30px 0px;
  position: fixed;
}

.wrapper .sidebar ul li img{
  width: 20px;
  float: left;
}

.wrapper .sidebar h2{
  color: #fff;
  text-transform: uppercase;
  text-align: center;
  margin-bottom: 30px;
}

.wrapper .sidebar ul li{
  padding: 25px;
  border-bottom: 1px solid #bdb8d7;
  border-bottom: 1px solid rgba(0,0,0,0.05);
  border-top: 1px solid rgba(255,255,255,0.05);
  text-indent: 10px;
}    
  

.wrapper .sidebar ul li a{
  color: white;
  display: block;
}

.wrapper .sidebar ul li a .fas{
  width: 25px;
}

.wrapper .sidebar ul li:hover{
  background-color: #d85375;
}
    
.wrapper .sidebar ul li:hover a{
  color: #fff;
}
 
.wrapper .sidebar .social_media{
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
}

.wrapper .sidebar .social_media a{
  display: block;
  width: 40px;
  background: #d85375;
  height: 40px;
  line-height: 45px;
  text-align: center;
  margin: 0 5px;
  color: #fff;
  border-top-left-radius: 5px;
  border-top-right-radius: 5px;
}

.wrapper .main_content{
  width: 100%;
  margin-left: 300px;
}

.wrapper .main_content .header{
  margin: 5%;
  background: #f3f5f9;
  color: #d85375;
  border-bottom: 1px solid #e0e4e8;
  text-align: center;
}

.header p{
  color: #717171;
  font-style: italic;
  font-size: 13px;
}

.wrapper .main_content .content{
  margin: 15%;
  background: #f3f5f9;
  color: #e2688a;
  text-align: center;
}

.content p{
  color: #717171;
  font-style: italic;
  font-size: 13px;
}

#logo {
  margin-left: 15% ;
  margin-bottom: 5%;
  max-width: 200px; 
}

</style>
<body>
<div class="wrapper">
    <div class="sidebar">
        <img src="https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/bnd%20vertical%20transparent.png" alt="Logo" id="logo">
        <ul>
            <li><img src="https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/ICONS/HOME.png"><a href="bank.php">HOME</a></li>
            <li><img src="https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/ICONS/DEPOSIT.png"><a href="deposit.php">DEPOSIT</a></li>
            <li><img src="https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/ICONS/WITHDRAW.png"><a href="withdraw.php">WITHDRAW</a></li>
            <li><img src="https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/ICONS/TRANSFER.png"><a href="transfer.php">TRANSFER</a></li>
            <li><img src="https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/ICONS/MORE.png"><a href="more.php">MORE</a></li>
            <li><img src="https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/ICONS/LOG%20OUT.png"><a href="login.php">LOG OUT</a></li>
        </ul>
        <div class="social_media">
            <a href="#"><i class="fab fa-facebook-f"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
        </div>
    </div>


    <div class="main_content">
        <div class="header">
            <h2>TRANSFER TO OWN ACCOUNT</h2>
            <p>Banko ni Dianne, Bank na Dito!</p>
        </div>

        <div class="transfer">
            <?php if(isset($successMessage)) echo "<div class='message'>$successMessage</div>"; ?>
            <?php if(isset($errorMessage)) echo "<div class='error'>$errorMessage</div>"; ?>

            <?php if(count($accounts) < 2): ?>
                <p>No other accounts available for transfer.</p>
            <?php else: ?>
            <form method="post" action="">
                <h3>From Account</h3>
                <select name="fromAccount" required>
                    <?php foreach($accounts as $acc): ?>
                        <option value="<?php echo $acc['id']; ?>">
                            <?php echo htmlspecialchars($acc['account_number']) . " (Balance: Php " . number_format($acc['balance'],2) . ")"; ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <h3>To Account</h3>
                <select name="toAccount" required>
                    <?php foreach($accounts as $acc): ?>
                        <option value="<?php echo $acc['id']; ?>">
                            <?php echo htmlspecialchars($acc['account_number']) . " (Balance: Php " . number_format($acc['balance'],2) . ")"; ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <h3>Amount</h3>
                <input type="text" name="amount" placeholder="Enter Amount" oninput="allowDigitsOnly(this)" required>

                <button type="submit" class="submit-button">TRANSFER</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function allowDigitsOnly(input){
    input.value = input.value.replace(/\D/g,'');
}
</script>
</body>
</html>
