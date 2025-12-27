<?php
session_start();
include 'db.php'; // RDS connection

// Ensure user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Fetch user info
$stmt = $mysqli->prepare("SELECT username, account_number, balance FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($userName, $accountNumber, $balance);
$stmt->fetch();
$stmt->close();

// Fetch transaction history
$transactions = [];
$stmt = $mysqli->prepare("SELECT reference_number, created_at, type, amount FROM transactions WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $transactions[] = [
        'reference' => htmlspecialchars($row['reference_number']),
        'timestamp' => htmlspecialchars($row['created_at']),
        'type' => htmlspecialchars($row['type']),
        'amount' => htmlspecialchars($row['amount'])
    ];
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Banko ni Dianne</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://kit.fontawesome.com/b99e675b6e.js"></script>
</head>
<body>

<div class="wrapper">
    <div class="sidebar">
        <img src="https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/bnd-vertical-transparent.png" alt="Logo" id="logo">
        <ul>
            <li><img src="bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/ICONS/HOME.png"><a href="#main_content">HOME</a></li>
            <li><img src="bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/DEPOSIT.png"><a href="deposit.php">DEPOSIT</a></li>
            <li><img src="bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/ICONS/WITHDRAW.png"><a href="withdraw.php">WITHDRAW</a></li>
            <li><img src="bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/ICONS/TRANSFER.png"><a href="transfer.php">TRANSFER</a></li>
            <li><img src="bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/ICONS/MORE.png"><a href="more.php">MORE</a></li>
            <li><img src="bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/ICONS/LOG-OUT.png"><a href="login.php">LOG OUT</a></li>
        </ul>
    </div>

    <div class="main_content">
        <div class="header">
            <div id="greeting"></div>
            <div class="name"><?php echo htmlspecialchars($userName); ?></div>
        </div>
        <div class="info">
            <table>
                <tr>
                    <td>Account Number: <?php echo htmlspecialchars($accountNumber); ?></td>
                    <td>Balance: Php <?php echo number_format($balance, 2); ?></td>
                </tr>
            </table>

            <div class="transaction_box">
                <div class="transaction-text">Transaction History</div>
                <button type="button" class="submit-button" onclick="toggleTransactionHistory()">View Transaction History</button>
            </div>
            <div id="transactionHistory" class="transaction-history" style="display:none;">
                <table>
                    <thead>
                        <tr>
                            <th>Reference Number</th>
                            <th>Date and Time</th>
                            <th>Type</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($transactions as $t): ?>
                        <tr>
                            <td><?php echo $t['reference']; ?></td>
                            <td><?php echo $t['timestamp']; ?></td>
                            <td><?php echo $t['type']; ?></td>
                            <td><?php echo number_format($t['amount'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const greetingDiv = document.getElementById('greeting');
    const hour = new Date().getHours();
    greetingDiv.textContent = hour < 12 ? 'Good Morning, ' :
                               hour < 18 ? 'Good Afternoon, ' : 'Good Evening, ';
});

function toggleTransactionHistory() {
    const transactionHistory = document.getElementById('transactionHistory');
    const button = document.querySelector('.submit-button');
    if (transactionHistory.style.display === 'block') {
        transactionHistory.style.display = 'none';
        button.textContent = 'View Transaction History';
    } else {
        transactionHistory.style.display = 'block';
        button.textContent = 'Hide Transaction History';
    }
}
</script>

</body>
</html>
