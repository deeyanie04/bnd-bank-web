<?php
session_start();
include 'db.php';

// Redirect if not logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Generate 12-digit reference number
function generateReferenceNumber() {
    return str_pad(mt_rand(1, 999999999999), 12, '0', STR_PAD_LEFT);
}

// Handle withdrawal request
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    header('Content-Type: application/json');

    $amount = floatval($_POST['amount']);

    if ($amount <= 0) {
        echo json_encode(['error' => 'Invalid amount']);
        exit();
    }

    // Get balance
    $stmt = $mysqli->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($balance);
    $stmt->fetch();
    $stmt->close();

    if ($amount > $balance) {
        echo json_encode(['error' => 'Insufficient funds']);
        exit();
    }

    $newBalance = $balance - $amount;
    $ref = generateReferenceNumber();

    $mysqli->begin_transaction();
    try {
        // Update balance
        $stmt = $mysqli->prepare("UPDATE users SET balance = ? WHERE id = ?");
        $stmt->bind_param("di", $newBalance, $userId);
        $stmt->execute();
        $stmt->close();

        // Insert transaction
        $stmt = $mysqli->prepare(
            "INSERT INTO transactions (user_id, reference_number, type, amount)
             VALUES (?, ?, 'WITHDRAW', ?)"
        );
        $stmt->bind_param("isd", $userId, $ref, $amount);
        $stmt->execute();
        $stmt->close();

        $mysqli->commit();

        echo json_encode([
            'success' => true,
            'amount' => number_format($amount, 2),
            'ref' => $ref,
            'time' => date("Y-m-d H:i:s")
        ]);
        exit();

    } catch (Exception $e) {
        $mysqli->rollback();
        echo json_encode(['error' => 'Transaction failed']);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Withdraw</title>
<script src="https://kit.fontawesome.com/b99e675b6e.js"></script>

<style>
*{
  margin:0; padding:0; box-sizing:border-box;
  font-family:'Josefin Sans', sans-serif;
}
body{ background:#f3f5f9; }

.wrapper{ display:flex; }
.sidebar{
  width:300px; height:100vh;
  background:#e2688a; position:fixed;
}
.sidebar img{ width:180px; margin:20px auto; display:block; }
.sidebar ul li{
  padding:20px; list-style:none;
}
.sidebar ul li a{
  color:#fff; text-decoration:none;
}
.sidebar ul li:hover{ background:#d85375; }

.main_content{
  margin-left:300px; width:100%;
}
.main_content h2{
  margin:50px; color:#e2688a;
}
.textbox{
  width:90%; margin:20px 5%;
  padding:12px; border-radius:5px;
  border:1px solid #ccc;
}
.submit-button{
  margin:20px 5%;
  padding:12px 20px;
  background:#d85375; color:#fff;
  border:none; border-radius:5px;
  cursor:pointer;
}

.modal{
  display:none; position:fixed;
  top:50%; left:50%;
  transform:translate(-50%,-50%);
  background:#e2688a; color:#fff;
  padding:25px; border-radius:10px;
}
.modal button{
  margin-top:15px;
  padding:8px 15px;
  background:#d85375;
  border:none; color:#fff;
  border-radius:5px;
  cursor:pointer;
}
</style>
</head>

<body>

<div class="wrapper">
  <div class="sidebar">
    <img src="https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/bnd-vertical-transparent.png">
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
    <h2>WITHDRAW</h2>
    <form onsubmit="withdraw(event)">
      <input type="text" id="amount" class="textbox" placeholder="Enter amount" required oninput="this.value=this.value.replace(/\D/g,'')">
      <button class="submit-button">WITHDRAW</button>
    </form>
  </div>
</div>

<div class="modal" id="modal">
  <div id="modalContent"></div>
  <button onclick="closeModal()">Close</button>
</div>

<script>
function withdraw(e){
  e.preventDefault();
  const amount=document.getElementById('amount').value;
  fetch('withdraw.php',{
    method:'POST',
    body:new URLSearchParams({amount})
  })
  .then(r=>r.json())
  .then(d=>{
    if(d.error){ alert(d.error); return; }
    document.getElementById('modalContent').innerHTML=
      `<h3>WITHDRAW SUCCESS</h3>
      <p>(visit nearest BND branch and show </p>
      <p> reference number to withdraw) </p>
       <p>Amount: Php ${d.amount}</p>
       <p>Date: ${d.time}</p>
       <p>Ref: ${d.ref}</p>`;
    document.getElementById('modal').style.display='block';
    document.getElementById('amount').value='';
  });
}
function closeModal(){
  document.getElementById('modal').style.display='none';
}
</script>

</body>
</html>
