<?php
session_start();

// Ensure user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// RDS / MySQL configuration
$host = "YOUR_RDS_ENDPOINT";
$dbname = "bank_db";
$username = "db_username";
$password = "db_password";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get logged-in user info
$user_id = $_SESSION['user_id'] ?? 1;
$stmt = $pdo->prepare("SELECT id, account_number, balance FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
$accountNumber = $accounts[0]['account_number'] ?? 'Not Available';
$accountId = $accounts[0]['id'] ?? 0;

// Handle POST transfer
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $bankName = $_POST['bankName'] ?? '';
    $recipientAccount = $_POST['recipientAccount'] ?? '';
    $withdrawAmount = floatval($_POST['withdrawAmount'] ?? 0);

    // Validate input
    if (empty($bankName) || empty($recipientAccount) || $withdrawAmount <= 0) {
        $errorMessage = "All fields are required and amount must be greater than 0.";
    } else {
        // Check balance
        $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
        $stmt->execute([$accountId]);
        $currentBalance = floatval($stmt->fetchColumn());

        if ($currentBalance < $withdrawAmount) {
            $errorMessage = "Insufficient balance.";
        } else {
            // Begin transaction
            $pdo->beginTransaction();
            try {
                // Deduct from user's account
                $stmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
                $stmt->execute([$withdrawAmount, $accountId]);

                // Log transfer to another bank
                $reference = str_pad(mt_rand(1, 999999999999), 12, '0', STR_PAD_LEFT);
                $stmt = $pdo->prepare("INSERT INTO transactions (sender_id, recipient_bank, recipient_account, type, amount, reference, timestamp)
                                       VALUES (?, ?, ?, 'External Transfer', ?, ?, NOW())");
                $stmt->execute([$accountId, $bankName, $recipientAccount, $withdrawAmount, $reference]);

                $pdo->commit();
                $successMessage = "Successfully transferred Php " . number_format($withdrawAmount, 2) .
                                  " to $bankName Account: $recipientAccount. Reference: $reference";

                // Refresh balance
                $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
                $stmt->execute([$accountId]);
                $accounts[0]['balance'] = floatval($stmt->fetchColumn());

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
<title>Transfer to Another Bank</title>
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
#logo{margin-left:15%;margin-bottom:5%;max-width:200px;}
.transfer{max-width:100%;margin:5%;}
.transfer h3{color:#e2688a;margin-bottom:5px;}
.transfer input{width:100%;padding:10px;margin-bottom:15px;border-radius:5px;border:1px solid #ccc;font-size:16px;}
.submit-button{margin:auto;padding:15px;font-size:16px;background-color:#d85375;color:#fff;border:none;border-radius:5px;cursor:pointer;}
.submit-button:hover{background:pink;}
.message{text-align:center;margin-bottom:15px;font-weight:bold;color:green;}
.error{text-align:center;margin-bottom:15px;font-weight:bold;color:red;}
.dropdown{position:relative;display:inline-block;width:100%;}
.dropdown-content{display:none;position:absolute;background:#d85375;width:100%;z-index:1;}
.dropdown-content a{color:#f3f5f9;padding:12px;text-decoration:none;display:block;}
.dropdown-content a:hover{background:pink;}
.dropdown:hover .dropdown-content{display:block;}
</style>
</head>
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
            <h2>TRANSFER TO ANOTHER BANK</h2>
            <p>Banko ni Dianne, the Best For You!</p>
        </div>

        <div class="transfer">
            <?php if(isset($successMessage)) echo "<div class='message'>$successMessage</div>"; ?>
            <?php if(isset($errorMessage)) echo "<div class='error'>$errorMessage</div>"; ?>

            <form method="post" id="transferForm">
                <h3>Transfer From</h3>
                <p>Account Number: <?php echo htmlspecialchars($accountNumber) . " (Balance: Php " . number_format($accounts[0]['balance'],2) . ")"; ?></p>

                <h3>Transfer To</h3>
                <input type="text" name="bankName" id="bankName" placeholder="Select Other Bank" readonly required>
                <div class="dropdown">
                    <button type="button">Select Bank</button>
                    <div class="dropdown-content">
                        <?php
                        $banks = ["Banko ni Yunise","Banko ni Vim","Banko ni Mea","Banko ni Ellaine","Banko na Debunk","Branch of BND Savings","Gkacrush","Bank of DaV","Just Bank","Pay Meyuh"];
                        foreach ($banks as $bank) {
                            echo "<a href='#' onclick=\"document.getElementById('bankName').value='$bank'\">$bank</a>";
                        }
                        ?>
                    </div>
                </div>

                <input type="text" name="recipientAccount" placeholder="Enter Account Number" oninput="allowDigitsOnly(this)" maxlength="16" minlength="16" required>

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
