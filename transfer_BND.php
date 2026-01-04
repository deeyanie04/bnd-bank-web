<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// Base S3 URL
$s3_base = "https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/";

// RDS configuration
$host = "bnd-db.clkymsu642nu.ap-southeast-1.rds.amazonaws.com"; // Your RDS endpoint
$dbname = "bank_db";       // Your database name
$username = "admin";       // Your RDS username
$password = "admin123";
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get logged-in user's account info
$user_id = $_SESSION['user_id'] ?? 1;
$stmt = $pdo->prepare("SELECT account_number, balance FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$accountNumber = $user['account_number'] ?? 'Not Available';
$currentBalance = $user['balance'] ?? 0.00;

// Handle transfer POST request
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $recipientAccount = $_POST['recipientAccount'] ?? '';
    $withdrawAmount = floatval($_POST['withdrawAmount'] ?? 0);

    if ($withdrawAmount <= 0 || empty($recipientAccount)) {
        $errorMessage = "Invalid amount or account number.";
    } else if ($withdrawAmount > $currentBalance) {
        $errorMessage = "Insufficient balance.";
    } else {
        $stmt = $pdo->prepare("SELECT id, balance FROM users WHERE account_number = ?");
        $stmt->execute([$recipientAccount]);
        $recipient = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($recipient) {
            $recipientId = $recipient['id'];

            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
                $stmt->execute([$withdrawAmount, $user_id]);

                $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                $stmt->execute([$withdrawAmount, $recipientId]);

                $refNumber = str_pad(mt_rand(1, 999999999999), 12, '0', STR_PAD_LEFT);
                $stmt = $pdo->prepare("INSERT INTO transactions (sender_id, recipient_id, type, amount, reference, timestamp) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$user_id, $recipientId, 'Transfer', $withdrawAmount, $refNumber]);

                $pdo->commit();
                $successMessage = "Successfully transferred Php " . number_format($withdrawAmount,2) . " to account $recipientAccount. Reference: $refNumber";
                $currentBalance -= $withdrawAmount;
            } catch (Exception $e) {
                $pdo->rollBack();
                $errorMessage = "Transaction failed: " . $e->getMessage();
            }
        } else {
            $errorMessage = "Recipient account not found.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<title>Transfer BND</title>
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
#logo{margin-left:15%;margin-bottom:5%;max-width:200px;display:block;margin:auto;}
.transfer{max-width:600px;margin:5% auto;padding:20px;background:#fff;border-radius:10px;}
.transfer h3{color:#e2688a;margin-bottom:10px;}
.transfer p{margin-bottom:10px;}
.transfer input{width:100%;padding:10px;margin-bottom:15px;border-radius:5px;border:1px solid #ccc;font-size:16px;}
.submit-button{background:#d85375;color:#fff;padding:15px;font-size:16px;border:none;border-radius:5px;cursor:pointer;width:100%;}
.submit-button:hover{background:pink;}
.message{text-align:center;margin-bottom:15px;font-weight:bold;color:green;}
.error{text-align:center;margin-bottom:15px;font-weight:bold;color:red;}
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
  color: #f3f5f9;
  text-transform: uppercase;
  text-align: center;
  margin-bottom: 30px;
}

.wrapper .sidebar ul li{
  color: #f3f5f9;
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

#logo {
  margin-left: 15% ;
  margin-bottom: 5%;
  max-width: 200px; 
}


.wrapper .main_content .transfer{
  max-width: 100%;
  margin: 5%;
}

.wrapper .main_content .transfer .transfer_to{
  margin-bottom: 3%;
  color: #e2688a;
}

.wrapper .main_content .transfer .transfer_from{
  margin-bottom: 3%;
  color: #e2688a;
}


.wrapper .main_content .transfer .transfer_amount{
  margin-bottom: 3%;
  color: #f3f5f9;
  background: #e2688a;
  border: 1px solid #e2688a;
  padding: 3%;
}

.wrapper .main_content .transfer .transfer_button{
  height: 10vh;
  display: flex;
  justify-content:center;
  align-items: center;
}

.submit-button {
    margin: auto;
    padding: 15px;
    font-size: 16px;
    background-color:  #d85375;
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.accountnum, .amount {
      margin-top: 0.6%;
      padding: 10px;
      font-size: 16px;
      border: 1px solid #ccc;
      border-radius: 5px;
      width: 100%;
      box-sizing: border-box;
      display: flex;
      background-color: #f3f3f3;
      color: #717171;
    }


</style>
<body>
<div class="wrapper">
    <div class="sidebar">
        <img src="<?php echo $s3_base; ?>bnd-bank.png" alt="Logo" id="logo">
        <ul>
            <li><img src="<?php echo $s3_base; ?>ICONS/HOME.png"><a href="bank.php">HOME</a></li>
            <li><img src="<?php echo $s3_base; ?>ICONS/DEPOSIT.png"><a href="deposit.php">DEPOSIT</a></li>
            <li><img src="<?php echo $s3_base; ?>ICONS/WITHDRAW.png"><a href="withdraw.php">WITHDRAW</a></li>
            <li><img src="<?php echo $s3_base; ?>ICONS/TRANSFER.png"><a href="transfer.php">TRANSFER</a></li>
            <li><img src="<?php echo $s3_base; ?>ICONS/MORE.png"><a href="more.php">MORE</a></li>
            <li><img src="<?php echo $s3_base; ?>ICONS/LOG%20OUT.png"><a href="login.php">LOG OUT</a></li>
        </ul>
        <div class="social_media">
            <a href="#"><i class="fab fa-facebook-f"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
        </div>
    </div>

    <div class="main_content">
        <div class="header">
            <h2>TRANSFER TO ANOTHER BND ACCOUNT</h2>
            <p>Banko ng Distrito, the Best For You!</p>
        </div>

        <div class="transfer">
            <?php if(isset($successMessage)) echo "<div class='message'>$successMessage</div>"; ?>
            <?php if(isset($errorMessage)) echo "<div class='error'>$errorMessage</div>"; ?>

            <form method="post" action="">
                <h3>Transfer From</h3>
                <p>Account Number: <?php echo htmlspecialchars($accountNumber); ?></p>
                <p>Balance: Php <?php echo number_format($currentBalance,2); ?></p>

                <h3>Transfer To</h3>
                <input type="text" name="recipientAccount" placeholder="Enter Account Number" maxlength="16" minlength="16" oninput="allowDigitsOnly(this)" required>

                <h3>Amount</h3>
                <input type="text" name="withdrawAmount" placeholder="Enter Amount" oninput="allowDigitsOnly(this)" required>

                <button type="submit" class="submit-button">TRANSFER</button>
            </form>
        </div>
    </div>
</div>

<script>
function allowDigitsOnly(input) {
    input.value = input.value.replace(/\D/g,'');
}
</script>
</body>
</html>
