<?php
session_start();

/* =========================
   DATABASE CONFIG
========================= */
$host = "bnd-db.clkymsu642nu.ap-southeast-1.rds.amazonaws.com";
$dbname = "bank_db";
$dbuser = "admin";
$dbpass = "admin123";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $dbuser,
        $dbpass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Database connection failed");
}

/* =========================
   LOGIN CHECK
========================= */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* =========================
   HANDLE AJAX POST
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    header("Content-Type: application/json");

    $amount = floatval($_POST['amount'] ?? 0);
    $toBank = trim($_POST['toBank'] ?? '');
    $toAccount = trim($_POST['toAccount'] ?? '');

    if ($amount <= 0) {
        echo json_encode(["error" => "Invalid amount"]);
        exit;
    }

    if (!preg_match('/^\d{16}$/', $toAccount)) {
        echo json_encode(["error" => "Account number must be 16 digits"]);
        exit;
    }

    if ($toBank === "") {
        echo json_encode(["error" => "Please select a bank"]);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ? FOR UPDATE");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new Exception("User not found");
        }

        if ($user['balance'] < $amount) {
            throw new Exception("Insufficient balance");
        }

        $newBalance = $user['balance'] - $amount;

        $pdo->prepare(
            "UPDATE users SET balance = ? WHERE id = ?"
        )->execute([$newBalance, $user_id]);

        $ref = str_pad(mt_rand(1, 999999999999), 12, "0", STR_PAD_LEFT);

        $pdo->prepare(
            "INSERT INTO transactions 
            (sender_id, recipient_bank, recipient_account, type, amount, reference, timestamp)
            VALUES (?, ?, ?, 'External Transfer', ?, ?, NOW())"
        )->execute([$user_id, $toBank, $toAccount, $amount, $ref]);

        $pdo->commit();

        echo json_encode([
            "success" => true,
            "refNumber" => $ref,
            "newBalance" => number_format($newBalance, 2)
        ]);
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(["error" => $e->getMessage()]);
        exit;
    }
}

/* =========================
   LOAD USER INFO
========================= */
$stmt = $pdo->prepare(
    "SELECT account_number, balance FROM users WHERE id = ?"
);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$accountNumber = $user['account_number'];
$balance = $user['balance'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Withdraw / Transfer</title>

<style>
*{
  margin:0;
  padding:0;
  box-sizing:border-box;
  font-family:Arial, sans-serif;
}
body{
  background:#f3f5f9;
}
.container{
  max-width:700px;
  margin:50px auto;
  background:#fff;
  padding:30px;
  border-radius:10px;
  box-shadow:0 0 15px rgba(0,0,0,0.1);
}
h2{
  color:#e2688a;
  margin-bottom:10px;
}
p{
  margin-bottom:20px;
}
input, button{
  width:100%;
  padding:12px;
  margin-bottom:15px;
  border-radius:5px;
  border:1px solid #ccc;
}
button{
  background:#d85375;
  color:white;
  font-size:16px;
  border:none;
  cursor:pointer;
}
button:hover{
  background:#c44565;
}
.modal{
  display:none;
  position:fixed;
  top:50%;
  left:50%;
  transform:translate(-50%,-50%);
  background:#e2688a;
  color:white;
  padding:20px;
  border-radius:10px;
  text-align:center;
}
.modal button{
  background:#fff;
  color:#e2688a;
  margin-top:10px;
}
</style>
</head>

<body>

<div class="container">
<h2>TRANSFER / WITHDRAW</h2>

<p>
Account Number: <b><?= htmlspecialchars($accountNumber) ?></b><br>
Balance: <b>Php <?= number_format($balance,2) ?></b>
</p>

<form onsubmit="submitTransfer(event)">
<input type="text" id="toBank" placeholder="Bank Name" required>
<input type="text" id="toAccount" placeholder="16-digit Account Number" maxlength="16" required>
<input type="text" id="amount" placeholder="Amount" required>
<button type="submit">TRANSFER</button>
</form>
</div>

<div id="modal" class="modal">
<div id="modalContent"></div>
<button onclick="closeModal()">Close</button>
</div>

<script>
function submitTransfer(e){
    e.preventDefault();

    const formData = new FormData();
    formData.append("toBank", document.getElementById("toBank").value);
    formData.append("toAccount", document.getElementById("toAccount").value);
    formData.append("amount", document.getElementById("amount").value);

    fetch("withdraw.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        const modal = document.getElementById("modal");
        const content = document.getElementById("modalContent");

        if(data.error){
            content.innerHTML = "<h3>FAILED</h3><p>"+data.error+"</p>";
        }else{
            content.innerHTML = `
                <h3>SUCCESS</h3>
                <p>Reference: ${data.refNumber}</p>
                <p>New Balance: Php ${data.newBalance}</p>
            `;
        }
        modal.style.display="block";
    });
}

function closeModal(){
    document.getElementById("modal").style.display="none";
}
</script>

</body>
</html>
