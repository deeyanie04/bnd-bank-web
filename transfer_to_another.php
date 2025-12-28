<?php
session_start();
require 'db.php';

/* =======================
   AUTH CHECK
======================= */
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    exit("Unauthorized access");
}

/* =======================
   HANDLE POST (TRANSFER)
======================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $userId = $_SESSION['user_id'];
    $amount = floatval($_POST['withdrawAmount'] ?? 0);
    $bankName = trim($_POST['bankName'] ?? '');
    $targetAccount = trim($_POST['targetAccount'] ?? '');

    if ($amount <= 0 || empty($bankName) || empty($targetAccount)) {
        http_response_code(400);
        exit("Invalid input");
    }

    /* Get current balance */
    $stmt = $mysqli->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($currentBalance);
    $stmt->fetch();
    $stmt->close();

    if ($currentBalance < $amount) {
        http_response_code(400);
        exit("Insufficient balance");
    }

    /* Begin transaction */
    $mysqli->begin_transaction();

    try {
        /* Deduct balance */
        $stmt = $mysqli->prepare(
            "UPDATE users SET balance = balance - ? WHERE id = ?"
        );
        $stmt->bind_param("di", $amount, $userId);
        $stmt->execute();
        $stmt->close();

        /* Insert transaction record */
        $reference = uniqid("TRF-");

        $stmt = $mysqli->prepare("
            INSERT INTO transactions
            (user_id, type, amount, reference_number, target_bank, target_account)
            VALUES (?, 'transfer', ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "idsss",
            $userId,
            $amount,
            $reference,
            $bankName,
            $targetAccount
        );
        $stmt->execute();
        $stmt->close();

        $mysqli->commit();
        echo "Transfer successful";

    } catch (Exception $e) {
        $mysqli->rollback();
        http_response_code(500);
        echo "Transfer failed";
    }

    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transfer to Another Bank</title>
    <link rel="stylesheet" href="/styles.css">
    <script src="https://kit.fontawesome.com/b99e675b6e.js"></script>
</head>

<body>

<div class="wrapper">
    <div class="sidebar">
        <img src="https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/bnd-vertical-transparent.png" id="logo">
        <ul>
            <li><a href="bank.php">HOME</a></li>
            <li><a href="deposit.php">DEPOSIT</a></li>
            <li><a href="withdraw.php">WITHDRAW</a></li>
            <li><a href="transfer.php">TRANSFER</a></li>
            <li><a href="more.php">MORE</a></li>
            <li><a href="logout.php">LOG OUT</a></li>
        </ul>
    </div>

    <div class="main_content">
        <div class="header">
            <h2>TRANSFER TO ANOTHER BANK ACCOUNT</h2>
            <p>Banko ni Dianne ang Best For You!</p>
        </div>

        <div class="transfer">
            <form id="transferForm">

                <div class="transfer_from">
                    <h3>Transfer To</h3>

                    <input type="text"
                           id="bankName"
                           name="bankName"
                           placeholder="Select Bank"
                           readonly
                           required>

                    <select onchange="document.getElementById('bankName').value=this.value">
                        <option value="">-- Select Bank --</option>
                        <option>Banko ni Yunise</option>
                        <option>Banko ni Vim</option>
                        <option>Banko ni Mea</option>
                        <option>Banko ni Ellaine</option>
                        <option>Banko na Debunk</option>
                        <option>Branch of BND Savings</option>
                        <option>Gkacrush</option>
                        <option>Bank of DaV</option>
                        <option>Just Bank</option>
                        <option>Pay Meyuh</option>
                    </select>

                    <input type="text"
                           class="accountnum"
                           id="targetAccount"
                           name="targetAccount"
                           placeholder="Enter Account Number"
                           maxlength="16"
                           minlength="16"
                           oninput="digitsOnly(this)"
                           required>
                </div>

                <div class="transfer_amount">
                    <h3>Transfer Amount</h3>
                    <input type="text"
                           id="withdrawAmount"
                           name="withdrawAmount"
                           placeholder="Enter amount"
                           oninput="digitsOnly(this)"
                           required>
                </div>

                <div class="transfer_button">
                    <button class="submit-button" onclick="sendTransfer(event)">
                        TRANSFER
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
function digitsOnly(input) {
    input.value = input.value.replace(/\D/g, '');
}

function sendTransfer(event) {
    event.preventDefault();

    const amount = document.getElementById("withdrawAmount").value;
    const bank = document.getElementById("bankName").value;
    const target = document.getElementById("targetAccount").value;

    const xhr = new XMLHttpRequest();
    xhr.open("POST", "transfer_to_another.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    xhr.onload = function () {
        alert(xhr.responseText);
        if (xhr.status === 200) {
            window.location.href = "bank.php";
        }
    };

    xhr.send(
        "withdrawAmount=" + encodeURIComponent(amount) +
        "&bankName=" + encodeURIComponent(bank) +
        "&targetAccount=" + encodeURIComponent(target)
    );
}
</script>

</body>
</html>
