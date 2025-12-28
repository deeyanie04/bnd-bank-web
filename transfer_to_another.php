<?php
session_start();
require 'db.php';

/* =======================
   AUTH CHECK
======================= */
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode([
        "status" => "error",
        "message" => "Unauthorized access"
    ]);
    exit();
}

/* =======================
   HANDLE AJAX TRANSFER
======================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $userId = $_SESSION['user_id'];
    $amount = floatval($_POST['withdrawAmount'] ?? 0);
    $bankName = trim($_POST['bankName'] ?? '');
    $targetAccount = trim($_POST['targetAccount'] ?? '');

    if ($amount <= 0 || empty($bankName) || empty($targetAccount)) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Please complete all fields correctly."
        ]);
        exit();
    }

    /* Get balance */
    $stmt = $mysqli->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($balance);
    $stmt->fetch();
    $stmt->close();

    if ($balance < $amount) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Insufficient balance."
        ]);
        exit();
    }

    $mysqli->begin_transaction();

    try {
        /* Deduct balance */
        $stmt = $mysqli->prepare(
            "UPDATE users SET balance = balance - ? WHERE id = ?"
        );
        $stmt->bind_param("di", $amount, $userId);
        $stmt->execute();
        $stmt->close();

        /* Save transaction */
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

        echo json_encode([
            "status" => "success",
            "message" => "Transfer successful!"
        ]);
        exit();

    } catch (Exception $e) {
        $mysqli->rollback();
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "Transfer failed. Please try again."
        ]);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Transfer to Another Bank</title>

<style>
*{
  margin:0;
  padding:0;
  box-sizing:border-box;
  font-family:'Josefin Sans', sans-serif;
}
body{background:#f3f5f9;}
.wrapper{display:flex}
.sidebar{
  width:300px;
  background:#e2688a;
  height:100vh;
  position:fixed;
  padding:30px 0;
}
.sidebar ul li{
  padding:20px;
}
.sidebar ul li a{
  color:#fff;
  text-decoration:none;
}
.sidebar ul li:hover{
  background:#d85375;
}
#logo{
  width:200px;
  margin:0 auto 20px;
  display:block;
}
.main_content{
  margin-left:300px;
  width:100%;
}
.header{
  margin:40px;
  text-align:center;
  color:#d85375;
}
.transfer{
  margin:40px auto;
  width:60%;
}
.transfer h3{color:#e2688a;}
input, select{
  width:100%;
  padding:12px;
  margin-top:8px;
  margin-bottom:20px;
  border-radius:5px;
  border:1px solid #ccc;
}
.transfer_amount{
  background:#e2688a;
  padding:20px;
  border-radius:5px;
}
.transfer_amount h3{color:#fff;}
.transfer_button{
  text-align:center;
}
button{
  padding:15px 30px;
  background:#d85375;
  border:none;
  color:white;
  font-size:16px;
  border-radius:5px;
  cursor:pointer;
}
button:hover{opacity:.9}
</style>
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

<h3>Transfer To</h3>

<select id="bankName" required>
<option value="">-- Select Bank --</option>
<option>Banko De Oro</option>
<option>BPI</option>
<option>BDO</option>
<option>China Bank</option>
<option>PNB</option>
<option>GCash</option>
<option>Maya</option>
<option>Security Bank</option>
</select>

<input type="text" id="targetAccount" placeholder="Account Number"
maxlength="16" minlength="16" oninput="digitsOnly(this)" required>

<div class="transfer_amount">
<h3>Transfer Amount</h3>
<input type="text" id="withdrawAmount" placeholder="Enter amount"
oninput="digitsOnly(this)" required>
</div>

<div class="transfer_button">
<button onclick="sendTransfer(event)">TRANSFER</button>
</div>

</form>
</div>

</div>
</div>

<script>
function digitsOnly(input){
  input.value = input.value.replace(/\D/g,'');
}

function sendTransfer(e){
  e.preventDefault();

  const amount = withdrawAmount.value;
  const bank = bankName.value;
  const account = targetAccount.value;

  if(!amount || !bank || !account){
    alert("Please complete all fields.");
    return;
  }

  if(!confirm(
    `Confirm Transfer?\n\nBank: ${bank}\nAccount: ${account}\nAmount: ₱${amount}`
  )){
    alert("Transfer cancelled.");
    return;
  }

  const xhr = new XMLHttpRequest();
  xhr.open("POST","transfer_to_another.php",true);
  xhr.setRequestHeader("Content-Type","application/x-www-form-urlencoded");

  xhr.onload = () => {
    const res = JSON.parse(xhr.responseText);
    alert(res.message);
    if(res.status === "success"){
      window.location.href="bank.php";
    }
  };

  xhr.onerror = () => alert("Network error.");

  xhr.send(
    "withdrawAmount="+encodeURIComponent(amount)+
    "&bankName="+encodeURIComponent(bank)+
    "&targetAccount="+encodeURIComponent(account)
  );
}
</script>

</body>
</html>
