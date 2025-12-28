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

<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        list-style: none;
        text-decoration: none;
        font-family: 'Josefin Sans', sans-serif;
    }

    body {
        background-color: #f3f5f9;
    }

    .wrapper {
        display: flex;
        position: relative;
    }

    .wrapper .sidebar {
        width: 300px;
        height: 100%;
        background: #e2688a;
        padding: 30px 0px;
        position: fixed;
    }

    .wrapper .sidebar ul li img {
        width: 20px;
        float: left;
    }

    .wrapper .sidebar h2 {
        color: #fff;
        text-transform: uppercase;
        text-align: center;
        margin-bottom: 30px;
    }

    .wrapper .sidebar ul li {
        padding: 25px;
        border-bottom: 1px solid #bdb8d7;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        border-top: 1px solid rgba(255, 255, 255, 0.05);
        text-indent: 10px;
    }

    .wrapper .sidebar ul li a {
        color: white;
        display: block;
    }

    .wrapper .sidebar ul li a .fas {
        width: 25px;
    }

    .wrapper .sidebar ul li:hover {
        background-color: #d85375;
    }

    .wrapper .sidebar ul li:hover a {
        color: #fff;
    }

    .wrapper .sidebar .social_media {
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
    }

    .wrapper .sidebar .social_media a {
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

    .wrapper .main_content {
        width: 100%;
        margin-left: 300px;
    }

    .wrapper .main_content .header {
        margin: 5%;
        padding: 30px;
        color: #717171;
        border-bottom: 1px solid #e0e4e8;
    }


    .wrapper .main_content .header .name{
        width: 100%;
        color:#d85375;
        justify-content: left;
         font-size: 25px;
         font-weight: bold;
    }

    .wrapper .main_content .info {
        margin: 20px;
        color: #717171;
        line-height: 25px;
    }

    .wrapper .main_content .info div {
        margin-bottom: 20px;
    }

    table {
        margin: 5%;
        width: 90%;
       
    }

    th, td {
        border: 1px solid whitesmoke;
        background-color: #e2688a;
        margin: 5%;
        padding: 5%;
        text-align: left;
    }

    .wrapper .main_content .info .transaction_box {
        margin: 5%;
        margin-top: 0%;
        padding: 4%;
        border: 2px solid #e2688a;
        display: flex;
        justify-content: space-between; /* Align to the space between elements */
        align-items: center; /* Align vertically centered */
    }

    .transaction_box .transaction-text {
        padding-top: 1%;
        margin-right: 30%; /* Adjust the margin as needed */
        font-size: 20px;
    }

    .transaction_box .submit-button {
        padding: 10px;
        font-size: 16px;
        background-color: #d85375;
        color: #fff;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }

    #logo {
        margin-left: 15%;
        margin-bottom: 5%;
        max-width: 200px;
    }

    .transaction_box #accountNumber {
        margin-right:5%;
    }

    #accountNumber {
        padding-top: 5%;
        color:#f3f5f9;
        font-size: 16px;
        font-weight: bold;
        margin-bottom: 0%;
    }

    .number {
        font-size: 16px;
        color:#f3f5f9;
        margin-top: -10%;
        margin-left: 35%;
    }

    .balance {
        color:#f3f5f9;
        font-size: 16px;
        font-weight: bold;

    }
    
    .hide-transaction-history {
        color: #d85375;
        cursor: pointer;
        float: right;
        margin-right: 20px;
        margin-top: 10px;
    }

    .transaction-history {
        display: none;
        margin-top: 20px;
    }

    .transaction-history table {
        width: 70%; /* Adjust the width as needed */
        border-collapse: collapse;
        margin-top: 10px;
    }

    .transaction-history th, .transaction-history td {
        border: 1px solid whitesmoke;
        background-color: #e2688a;
        padding: 15px;
        text-align: left;
        color: #fff;
    }

    .transaction-history th {
        background-color: #d85375;
    }

    .transaction-history tr:nth-child(even) {
        background-color: #fff;
    }

</style>
</style>
<div class="wrapper">
    <div class="sidebar">
        <img src="https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/bnd-vertical-transparent.png" alt="Logo" id="logo">
        <ul>
            <li><img src="https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/ICONS/HOME.png"><a href="#main_content">HOME</a></li>
            <li><img src="https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/DEPOSIT.png"><a href="deposit.php">DEPOSIT</a></li>
            <li><img src="https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/ICONS/WITHDRAW.png"><a href="withdraw.php">WITHDRAW</a></li>
            <li><img src="https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/ICONS/TRANSFER.png"><a href="transfer.php">TRANSFER</a></li>
            <li><img src="https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/ICONS/MORE.png"><a href="more.php">MORE</a></li>
            <li><img src="https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/ICONS/LOG-OUT.png"><a href="login.php">LOG OUT</a></li>
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
