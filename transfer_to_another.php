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
  color: #fff;
  text-transform: uppercase;
  text-align: center;
  margin-bottom: 30px;
}

.wrapper .sidebar ul li{
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

        .dropdown {
            width: 20.6%;
            text-decoration: none;
            margin: auto;
            padding: 10px;
            font-size: 16px;
            background-color: #d85375;
            border: none;
            color: #f3f5f9;
            border-radius: 5px;
            cursor: pointer;
            position: relative;
            display: inline-block;

        }

        .dropdown button{
          text-decoration: none;
          background-color: #d85375;
          text-align: center;
          border: none;
          color: #f3f5f9;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #d85375;
            min-width: 200px;
            max-width: 80%;
            z-index: 1;
        }

        .dropdown-content a {
            color: #f3f5f9;
            padding: 12px 12px;
            text-decoration: none;
            display: block;
        }

        .dropdown-content a:hover {
            background-color: pink;
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }

        #outputTextField {
                  align-items: right;
                  justify-content: right;
                  margin-top: 0.6%;
                  padding: 10px;
                  font-size: 16px;
                  border: 1px solid #ccc;
                  border-radius: 5px;
                  width: 78.9%;
                  box-sizing: border-box;
                  background-color: #fefefa;
                  color: #717171;
        }


</style>
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
