
<?php
session_start();

// Ensure user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// Generate random 12-digit reference
function generateReferenceNumber() {
    return str_pad(mt_rand(1, 999999999999), 12, '0', STR_PAD_LEFT);
}

// RDS configuration
$host = "bnd-db.clkymsu642nu.ap-southeast-1.rds.amazonaws.com"; // e.g., mydb.abcdefg123.us-east-1.rds.amazonaws.com
$dbname = "bank_db";
$username = "admin";
$password = "admin123";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
}

// Get logged-in user
$user_id = $_SESSION['id'] ?? 1;

// Handle POST withdrawal/transfer
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $amount = floatval($_POST['amount'] ?? 0);
    $toBank = $_POST['toBank'] ?? '';
    $toAccount = $_POST['toAccount'] ?? '';

    // Input validation
    if ($amount <= 0) {
        echo json_encode(['error' => 'Invalid transfer amount']);
        exit();
    }
    if (!preg_match('/^\d{16}$/', $toAccount)) {
        echo json_encode(['error' => 'Account number must be 16 digits']);
        exit();
    }

    try {
        // Begin transaction
        $pdo->beginTransaction();

        // Get user's balance
        $stmt = $pdo->prepare("SELECT id, account_number, balance FROM users WHERE id = ? FOR UPDATE");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new Exception("User not found");
        }

        if ($user['balance'] < $amount) {
            throw new Exception("Insufficient funds");
        }

        // Deduct balance
        $newBalance = $user['balance'] - $amount;
        $stmt = $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?");
        $stmt->execute([$newBalance, $user['id']]);

        // Record transaction
        $refNumber = generateReferenceNumber();
        $stmt = $pdo->prepare("INSERT INTO transactions 
            (sender_id, recipient_bank, recipient_account, type, amount, reference, timestamp)
            VALUES (?, ?, ?, 'External Transfer', ?, ?, NOW())");
        $stmt->execute([$user['id'], $toBank, $toAccount, $amount, $refNumber]);

        $pdo->commit();

        echo json_encode([
            'receipt' => "Transfer, Php " . number_format($amount, 2) . ",$toBank,$toAccount," . date("Y-m-d H:i:s") . ",$refNumber",
            'refNumber' => $refNumber,
            'newBalance' => number_format($newBalance, 2)
        ]);
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['error' => $e->getMessage()]);
        exit();
    }
}

// Load user's account number for display
$stmt = $pdo->prepare("SELECT account_number, balance FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$accountNumber = $user['account_number'] ?? 'Not Available';
$balance = $user['balance'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Withdraw / Transfer</title>
<link rel="stylesheet" href="styles.css">
<!-- Include your styles and JS (unchanged) -->
</head><style>
    
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
    justify-content: flex-end;
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

.modal {
  display: none;
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  padding: 20px;
  background-color: #e2688a; 
  color: #fff;
  border-radius: 10px;
  box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
  text-align: center;
}

.modal-content {
  text-align: center;
}

.modal-close {
  background-color: #d85375; 
  text-align: center;
  color: #fff;
  padding: 8px 12px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  margin-top: 10px; 
}

</style>

<body>
<div class="wrapper">
    <div class="sidebar">
        <!-- Sidebar content unchanged -->
    </div>
    <div class="main_content">
        <div class="header">
            <h2>TRANSFER / WITHDRAW</h2>
            <p>Banko ni Dianne ang Best For You!</p>
        </div>
        <div class="transfer">
            <form id="transferForm" onsubmit="submitTransfer(event)">
                <div class="transfer_to">
                    <h3>Transfer From</h3>
                    <p>Account Number: <?php echo htmlspecialchars($accountNumber); ?> (Balance: Php <?php echo number_format($balance,2); ?>)</p>
                </div>
                <div class="transfer_from">
                    <h3>Transfer To</h3>
                    <input type="text" id="outputTextField" placeholder="Select Other Bank" readonly required>
                    <div class="dropdown">
                        <button>Select Bank</button>
                        <div class="dropdown-content">
                            <a href="#" onclick="selectBank('Banko ni Yunise')">Banko ni Yunise</a>
                            <a href="#" onclick="selectBank('Banko ni Vim')">Banko ni Vim</a>
                            <a href="#" onclick="selectBank('Banko ni Mea')">Banko ni Mea</a>
                            <a href="#" onclick="selectBank('Banko ni Ellaine')">Banko ni Ellaine</a>
                            <a href="#" onclick="selectBank('Banko na Debunk')">Banko na Debunk</a>
                        </div>
                    </div>
                    <input type="text" class="accountnum" placeholder="Enter Account Number here" id="toAccount" oninput="allowDigitsOnly(this)" maxlength="16" minlength="16" required>
                </div>
                <div class="transfer_amount">
                    <h3>Transfer Amount</h3>
                    <input type="text" class="amount" placeholder="Enter amount here" name="amount" id="transferAmount" oninput="allowDigitsOnly(this)" required>
                </div>
                <button class="submit-button">TRANSFER</button>
            </form>
        </div>
    </div>
</div>

<div id="transferModal" class="modal">
    <div id="transferModalContent" class="modal-content"></div>
    <button class="modal-close" onclick="hideTransferModal()">Close</button>
</div>

<script>
function allowDigitsOnly(input) {
    input.value = input.value.replace(/\D/g,'');
}
function selectBank(bankName) {
    document.getElementById('outputTextField').value = bankName;
}
function submitTransfer(event) {
    event.preventDefault();
    var amount = document.getElementById("transferAmount").value;
    var toBank = document.getElementById("outputTextField").value;
    var toAccount = document.getElementById("toAccount").value;

    var formData = new FormData();
    formData.append('amount', amount);
    formData.append('toBank', toBank);
    formData.append('toAccount', toAccount);

    fetch('withdraw.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        if (data.error) showTransferModalError(data.error);
        else {
            showTransferModal(amount, toBank, toAccount, data.refNumber, data.newBalance);
            document.getElementById("transferAmount").value = "";
            document.getElementById("toAccount").value = "";
            document.getElementById("outputTextField").value = "";
        }
    })
    .catch(err => showTransferModalError('Transfer failed. Please try again.'));
}

function showTransferModal(amount, bank, account, refNumber, newBalance) {
    var modal = document.getElementById("transferModal");
    var content = document.getElementById("transferModalContent");
    content.innerHTML = `<h3>TRANSFER SUCCESSFUL!</h3>
                         <p>Amount: Php ${parseFloat(amount).toFixed(2)}</p>
                         <p>To Bank: ${bank}</p>
                         <p>To Account: ${account}</p>
                         <p>Reference Number: ${refNumber}</p>
                         <p>New Balance: Php ${parseFloat(newBalance).toFixed(2)}</p>`;
    modal.style.display = "block";
}

function showTransferModalError(message) {
    var modal = document.getElementById("transferModal");
    var content = document.getElementById("transferModalContent");
    content.innerHTML = "<h3>TRANSFER FAILED</h3><p>" + message + "</p>";
    modal.style.display = "block";
}

function hideTransferModal() {
    var modal = document.getElementById("transferModal");
    modal.style.display = "none";
    document.getElementById("transferModalContent").innerHTML = '';
}
</script>
</body>
</html>
