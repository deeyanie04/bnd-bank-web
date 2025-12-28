<?php
session_start();

/* ==========================
   DATABASE CONFIG
========================== */
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
    http_response_code(500);
    echo json_encode(["error" => "DB Connection Failed"]);
    exit;
}

/* ==========================
   AUTH CHECK
========================== */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* ==========================
   AJAX TRANSFER HANDLER
========================== */
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

        $stmt = $pdo->prepare(
            "SELECT id, balance FROM users WHERE id = ? FOR UPDATE"
        );
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

/* ==========================
   PAGE LOAD (HTML)
========================== */
$stmt = $pdo->prepare(
    "SELECT account_number, balance FROM users WHERE id = ?"
);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$accountNumber = $user['account_number'];
$balance = $user['balance'];
?>
