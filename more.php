<?php
session_start();

// Redirect to login page if not logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// RDS / Database connection
$host = "your-rds-endpoint"; 
$dbname = "bank_db";
$username = "your_db_username";
$password = "your_db_password";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Base S3 URL
$s3_base = "https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/";

// Define services
$services = [
    ['link'=>'card.php', 'img'=>$s3_base.'ICONS/BANK.png', 'name'=>'Card Management'],
    ['link'=>'piggy.php', 'img'=>$s3_base.'ICONS/PIGGY%20BANK%202.png', 'name'=>'BND Piggy Bank']
];

// Optional: log service clicks via AJAX
if (isset($_POST['service_name'])) {
    $serviceName = $_POST['service_name'];
    $userId = $_SESSION['user_id'] ?? 0;
    $stmt = $pdo->prepare("INSERT INTO user_services (user_id, service_name, clicked_at) VALUES (?, ?, NOW())");
    $stmt->execute([$userId, $serviceName]);
    echo json_encode(['status'=>'ok']);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>More Services</title>
<link rel="stylesheet" href="styles.css">
<script src="https://kit.fontawesome.com/b99e675b6e.js"></script>
<style>
*{margin:0;padding:0;box-sizing:border-box;list-style:none;text-decoration:none;font-family:'Josefin Sans',sans-serif;}
body{background-color:#f3f5f9;}
.wrapper{display:flex;position:relative;}
.wrapper .sidebar{width:300px;height:100%;background:#e2688a;padding:30px 0;position:fixed;}
.wrapper .sidebar ul li img{width:20px;float:left;}
.wrapper .sidebar h2{color:#fff;text-transform:uppercase;text-align:center;margin-bottom:30px;}
.wrapper .sidebar ul li{padding:25px;border-bottom:1px solid rgba(0,0,0,0.05);border-top:1px solid rgba(255,255,255,0.05);text-indent:10px;}
.wrapper .sidebar ul li a{color:#f3f5f9;display:block;}
.wrapper .sidebar ul li:hover{background-color:#d85375;}
.wrapper .sidebar ul li:hover a{color:#fff;}
.wrapper .sidebar .social_media{position:absolute;bottom:0;left:50%;transform:translateX(-50%);display:flex;}
.wrapper .sidebar .social_media a{display:block;width:40px;background:#d85375;height:40px;line-height:45px;text-align:center;margin:0 5px;color:#fff;border-top-left-radius:5px;border-top-right-radius:5px;}
.wrapper .main_content{width:100%;margin-left:300px;}
.wrapper .main_content .header{margin:5%;background:#f3f5f9;color:#d85375;border-bottom:1px solid #e0e4e8;}
.header p{color:#717171;font-style:italic;font-size:13px;}
.wrapper .main_content .services{margin:2%;width:90%;text-align:center;color:#717171;}
.wrapper .main_content .services table{width:100%;}
.wrapper .main_content .services table td{padding:20px;}
.wrapper .main_content .services table td a{text-decoration:none;color:#717171;display:block;}
.wrapper .main_content .services table td img{width:60%;border:2px solid #e2688a;border-radius:5%;transition:0.3s;}
.wrapper .main_content .services table td img:hover{transform:scale(1.05);}
.wrapper .main_content .services table td p{padding:10px;cursor:pointer;color:#717171;}
#logo{margin-left:15%;margin-bottom:5%;max-width:200px;}
</style>
</head>
<body>

<div class="wrapper">
    <div class="sidebar">
        <img src="<?php echo $s3_base; ?>bnd%20vertical%20transparent.png" alt="Logo" id="logo">
        <ul>
            <li><img src="<?php echo $s3_base; ?>ICONS/HOME.png"><a href="bank.php">HOME</a></li>
            <li><img src="<?php echo $s3_base; ?>ICONS/DEPOSIT.png"><a href="deposit.php">DEPOSIT</a></li>
            <li><img src="<?php echo $s3_base; ?>ICONS/WITHDRAW.png"><a href="withdraw.php">WITHDRAW</a></li>
            <li><img src="<?php echo $s3_base; ?>ICONS/TRANSFER.png"><a href="transfer.php">TRANSFER</a></li>
            <li><img src="<?php echo $s3_base; ?>ICONS/MORE.png"><a href="more.php">MORE</a></li>
            <li><img src="<?php echo $s3_base; ?>ICONS/LOG%20OUT.png"><a href="login.php">LOG OUT</a></li>
        </ul> 
        <div class="social_media">
            <a href="#"><i class="fab fa-facebook-f"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
        </div>
    </div>

    <div class="main_content" id="main_content">
        <div class="header"> 
            <h2>SERVICES</h2> 
            <p>What would you like to do?</p> 
            <br>
        </div>  

        <div class="services">
            <table>
                <tr>
                <?php foreach ($services as $index => $service): ?>
                    <td>
                        <a href="<?php echo $service['link']; ?>" onclick="logServiceClick('<?php echo $service['name']; ?>')">
                            <img src="<?php echo $service['img']; ?>" alt="<?php echo $service['name']; ?>">
                            <p><?php echo $service['name']; ?></p>
                        </a>
                    </td>
                    <?php if (($index + 1) % 2 == 0) echo '</tr><tr>'; ?>
                <?php endforeach; ?>
                </tr>
            </table>
        </div>
    </div>
</div>

<script>
// Log service click via AJAX
function logServiceClick(serviceName) {
    fetch('more.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'service_name=' + encodeURIComponent(serviceName)
    }).then(resp => resp.json())
    .then(data => console.log('Service logged:', serviceName))
    .catch(err => console.error('Logging failed:', err));
}
</script>

</body>
</html>
