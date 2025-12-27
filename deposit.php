<?php
session_start();
include 'db.php'; // RDS connection

// Ensure user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Function to generate a random 12-digit reference number
function generateReferenceNumber() {
    return str_pad(mt_rand(1, 999999999999), 12, '0', STR_PAD_LEFT);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $depositAmount = isset($_POST["amount"]) ? floatval($_POST["amount"]) : 0;

    if ($depositAmount <= 0) {
        echo json_encode(['error' => 'Invalid deposit amount.']);
        exit();
    }

    $mysqli->begin_transaction();

    try {
        // Update user balance
        $stmt = $mysqli->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
        $stmt->bind_param("di", $depositAmount, $userId);
        $stmt->execute();
        $stmt->close();

        // Insert transaction record
        $refNumber = generateReferenceNumber();
        $stmt = $mysqli->prepare("INSERT INTO transactions (user_id, type, amount, reference_number, created_at) VALUES (?, 'Deposit', ?, ?, NOW())");
        $stmt->bind_param("ids", $userId, $depositAmount, $refNumber);
        $stmt->execute();
        $stmt->close();

        $mysqli->commit();

        echo json_encode(['refNumber' => $refNumber]);
    } catch (Exception $e) {
        $mysqli->rollback();
        echo json_encode(['error' => 'Transaction failed. Please try again.']);
    }

    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Deposit</title>
<link rel="stylesheet" href="styles.css">
<script src="https://kit.fontawesome.com/b99e675b6e.js"></script>
</head>
<body>

<div class="wrapper">
    <div class="sidebar">
        <img src="https://https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/bnd-vertical-transparent.png" alt="Logo" id="logo">
        <ul>
            <li><img src="https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/ICONS/HOME.png"><a href="bank.php">HOME</a></li>
            <li><img src="https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/ICONS/DEPOSIT.png"><a href="deposit.php">DEPOSIT</a></li>
            <li><img src="https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/ICONS/WITHDRAW.png"><a href="withdraw.php">WITHDRAW</a></li>
            <li><img src="https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/ICONS/TRANSFER.png"><a href="transfer.php">TRANSFER</a></li>
            <li><img src="https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/ICONS/MORE.png"><a href="more.php">MORE</a></li>
            <li><img src="https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/ICONS/LOG-OUT.png"><a href="login.php">LOG OUT</a></li>
        </ul>
    </div>

    <div class="main_content" id="main_content">
        <div class="info">
            <h2>DEPOSIT</h2>
            <form method="post" onsubmit="deposit(event)">
                <input type="text" class="textbox" placeholder="Enter amount here" id="depositAmount" required oninput="allowDigitsOnly(this)">
                <br><br>
                <button class="submit-button">DEPOSIT</button>
            </form>
        </div>
    </div>
</div>

<!-- Custom Modal -->
<div id="customModal" class="modal">
    <div id="modalContent" class="modal-content"></div>
    <button class="modal-close" onclick="hideModal()">Close</button>
</div>

<script>
function allowDigitsOnly(input) {
    input.value = input.value.replace(/\D/g, '');
}

function deposit(event) {
    event.preventDefault();
    var depositAmount = document.getElementById("depositAmount").value;

    fetch('deposit.php', {
        method: 'POST',
        body: new URLSearchParams({'amount': depositAmount})
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert(data.error);
            return;
        }
        showModal(depositAmount, data.refNumber);
        document.getElementById("depositAmount").value = '';
    })
    .catch(error => console.error('Error:', error));
}

function showModal(amount, refNumber) {
    var modal = document.getElementById("customModal");
    var content = document.getElementById("modalContent");
    content.innerHTML =
        `<h3>DEPOSIT SUCCESSFUL!</h3>
         <p>Amount: Php ${parseFloat(amount).toFixed(2)}</p>
         <p>Date and Time: ${new Date().toLocaleString()}</p>
         <p>Reference Number: ${refNumber}</p>`;
    modal.style.display = "block";
}

function hideModal() {
    document.getElementById("customModal").style.display = "none";
}
</script>

</body>
</html>
