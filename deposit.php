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


.wrapper .main_content .info{
  margin: 20px;
  color: #717171;
  line-height: 25px;
}

.wrapper .main_content .info div{
  margin-bottom: 20px;
}

#logo {
  margin-left: 15% ;
  margin-bottom: 5%;
  max-width: 200px; 
}

.wrapper .main_content h2 {
  color:  #e2688a;
  margin-top: 10%;
  margin-left: 5%;
}

.textbox {
  margin-top: 0.6%;
  margin-left: 5%;
  padding: 10px;
  font-size: 16px;
  border: 1px solid #ccc;
  border-radius: 5px;
  width: 90%;
  box-sizing: border-box;
  display: inline-block;
  background-color: #f3f3f3;
  color: #717171;
}

.submit-button {
  position: absolute;
  justify-content:flex-end;
  bottom: 0;
  right: 0;
  margin-top: 30px;
  margin-right: 5%;
  padding: 10px;
  font-size: 16px;
  background-color:  #d85375;
  color: #fff;
  border: none;
  border-radius: 5px;
  cursor: pointer;
}

/* Custom Modal Styles */
.modal {
  display: none;
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  padding: 20px;
  background-color: #e2688a; /* Match the color of your webpage */
  color: #fff;
  border-radius: 10px;
  box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
  text-align: center;
}

.modal-content {
  text-align: center;
}

.modal-close {
  background-color: #d85375; /* Close button color */
  text-align: center;
  color: #fff;
  padding: 8px 12px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  margin-top: 10px; /* Adjusted margin */
}

</style>
</head>
<body>

<div class="wrapper">
    <div class="sidebar">
        <img src="https://https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/bnd-bank.png" alt="Logo" id="logo">
        <ul>
            <li><img src="https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/ICONS/HOME.png"><a href="bank.php">HOME</a></li>
            <li><img src="https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/ICONS/DEPOSIT.png"><a href="deposit.php">DEPOSIT</a></li>
            <li><img src="https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/ICONS/WITHDRAW.png"><a href="withdraw.php">WITHDRAW</a></li>
            <li><img src="https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/ICONS/TRANSFER.png"><a href="transfer.php">TRANSFER</a></li>
            <li><img src="https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/ICONS/MORE.png"><a href="more.php">MORE</a></li>
            <li><img src="https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/ICONS/LOG OUT.png"><a href="login.php">LOG OUT</a></li>
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
