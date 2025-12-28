<?php
session_start();
require 'db.php';

/* =======================
   AUTH CHECK
======================= */
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized access"]);
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
        echo json_encode(["error" => "Please complete all fields correctly."]);
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
        echo json_encode(["error" => "Insufficient balance."]);
        exit();
    }

    $mysqli->begin_transaction();

    try {
        // Deduct balance
        $stmt = $mysqli->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
        $stmt->bind_param("di", $amount, $userId);
        $stmt->execute();
        $stmt->close();

        // Save transaction
        $reference = uniqid("TRF-");
        $stmt = $mysqli->prepare("
            INSERT INTO transactions
            (user_id, type, amount, reference_number, target_bank, target_account)
            VALUES (?, 'TRANSFER', ?, ?, ?, ?)
        ");
        $stmt->bind_param("idsss", $userId, $amount, $reference, $bankName, $targetAccount);
        $stmt->execute();
        $stmt->close();

        $mysqli->commit();

        echo json_encode([
            "success" => true,
            "amount" => number_format($amount, 2),
            "ref" => $reference,
            "bank" => $bankName,
            "account" => $targetAccount,
            "time" => date("Y-m-d H:i:s")
        ]);
        exit();

    } catch (Exception $e) {
        $mysqli->rollback();
        echo json_encode(["error" => "Transaction failed. Please try again."]);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Transfer to Another Bank</title>
<script src="https://kit.fontawesome.com/b99e675b6e.js"></script>
<style>
*{margin:0; padding:0; box-sizing:border-box; font-family:'Josefin Sans',sans-serif;}
body{background:#f3f5f9;}
.wrapper{display:flex;}
.sidebar{width:300px; height:100vh; background:#e2688a; position:fixed;}
.sidebar img{width:180px; margin:20px auto; display:block;}
.sidebar ul li{padding:20px; list-style:none;}
.sidebar ul li a{color:#fff; text-decoration:none;}
.sidebar ul li:hover{background:#d85375;}
.main_content{margin-left:300px; width:100%;}
.main_content h2{margin:50px; color:#e2688a;}
.textbox, select{width:90%; margin:20px 5%; padding:12px; border-radius:5px; border:1px solid #ccc;}
.submit-button{margin:20px 5%; padding:12px 20px; background:#d85375; color:#fff; border:none; border-radius:5px; cursor:pointer;}
.modal{display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); background:#e2688a; color:#fff; padding:25px; border-radius:10px; max-width:400px; text-align:center;}
.modal button{margin-top:15px; padding:8px 15px; background:#d85375; border:none; color:#fff; border-radius:5px; cursor:pointer;}
.transfer h3{color:#e2688a; margin-left:5%;}
.transfer_amount{background:#e2688a; padding:20px; border-radius:5px; margin-left:5%; margin-right:5%;}
.transfer_amount h3{color:#fff;}
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
    <h2>TRANSFER TO ANOTHER BANK ACCOUNT</h2>

    <div class="transfer">
      <form id="transferForm" onsubmit="sendTransfer(event)">
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
               maxlength="16" minlength="16" oninput="this.value=this.value.replace(/\D/g,'')" required>

        <div class="transfer_amount">
          <h3>Transfer Amount</h3>
          <input type="text" id="withdrawAmount" placeholder="Enter amount" 
                 oninput="this.value=this.value.replace(/\D/g,'')" required>
        </div>

        <button class="submit-button">TRANSFER</button>
      </form>
    </div>
  </div>
</div>

<div class="modal" id="modal">
  <div id="modalContent"></div>
  <button onclick="closeModal()">Close</button>
</div>

<script>
function sendTransfer(e){
  e.preventDefault();
  const bank = document.getElementById('bankName').value;
  const account = document.getElementById('targetAccount').value;
  const amount = document.getElementById('withdrawAmount').value;

  if(!bank || !account || !amount){
    alert('Please complete all fields.');
    return;
  }

  const confirmTransfer = confirm(`Confirm Transfer?\nBank: ${bank}\nAccount: ${account}\nAmount: ₱${amount}`);
  if(!confirmTransfer) return;

  fetch('transfer_to_another.php', {
    method:'POST',
    body:new URLSearchParams({bankName:bank, targetAccount:account, withdrawAmount:amount})
  })
  .then(res=>res.json())
  .then(data=>{
    if(data.error){
      document.getElementById('modalContent').innerHTML=`<h3>ERROR</h3><p>${data.error}</p>`;
    } else {
      document.getElementById('modalContent').innerHTML=
        `<h3>TRANSFER SUCCESSFUL</h3>
        <p>Bank: ${data.bank}</p>
        <p>Account: ${data.account}</p>
        <p>Amount: ₱${data.amount}</p>
        <p>Reference: ${data.ref}</p>
        <p>Date: ${data.time}</p>`;
      document.getElementById('transferForm').reset();
    }
    document.getElementById('modal').style.display='block';
  })
  .catch(err=>{
    document.getElementById('modalContent').innerHTML=`<h3>ERROR</h3><p>Network error.</p>`;
    document.getElementById('modal').style.display='block';
  });
}

function closeModal(){
  document.getElementById('modal').style.display='none';
}
</script>

</body>
</html>
