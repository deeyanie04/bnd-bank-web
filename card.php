<?php
session_start();
include 'db.php';

// Ensure user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Fetch user info
$stmt = $mysqli->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($userName);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Debit Cards</title>
<script src="https://kit.fontawesome.com/b99e675b6e.js"></script>
<style>
/* ===== Reset & Font ===== */
*{margin:0;padding:0;box-sizing:border-box;font-family:'Josefin Sans',sans-serif;}
body{background:#f3f5f9;}
a{text-decoration:none;color:inherit;}
ul{list-style:none;}

/* ===== Wrapper & Sidebar ===== */
.wrapper{display:flex;position:relative;}
.wrapper .sidebar{
    width:300px;
    height:100%;
    background:#e2688a;
    padding:30px 0;
    position:fixed;
}
.wrapper .sidebar #logo{
    display:block;
    margin:0 auto 20px;
    max-width:200px;
}
.wrapper .sidebar ul li{
    padding:25px;
    border-bottom:1px solid rgba(0,0,0,0.05);
    border-top:1px solid rgba(255,255,255,0.05);
    text-indent:10px;
}
.wrapper .sidebar ul li img{width:20px;float:left;margin-right:10px;}
.wrapper .sidebar ul li a{color:#fff;display:block;}
.wrapper .sidebar ul li:hover{background:#d85375;}
.wrapper .sidebar ul li:hover a{color:#fff;}
.wrapper .sidebar .social_media{
    position:absolute;
    bottom:0;
    left:50%;
    transform:translateX(-50%);
    display:flex;
}
.wrapper .sidebar .social_media a{
    display:block;width:40px;height:40px;line-height:45px;text-align:center;margin:0 5px;background:#d85375;color:#fff;border-radius:5px 5px 0 0;
}

/* ===== Main Content ===== */
.wrapper .main_content{width:100%;margin-left:300px;}
.wrapper .main_content .header{
    margin:5%;padding:30px;color:#717171;border-bottom:1px solid #e0e4e8;
}
.wrapper .main_content .header .name{color:#d85375;font-size:25px;font-weight:bold;}
.wrapper .main_content .header #greeting{font-size:18px;margin-bottom:5px;}
.wrapper .main_content .table{margin:2%;width:80%;text-align:center;color:#717171;}
.wrapper .main_content .table table{width:100%;}
.wrapper .main_content .table table td img{
    display:block;margin:0 auto;width:60%;border-radius:10px;cursor:pointer;transition: transform 0.2s;
}
.wrapper .main_content .table table td img:hover{transform:scale(1.05);}
.wrapper .main_content .table table td p{padding:10px;cursor:pointer;color:#717171;font-weight:bold;font-size:16px;}

/* ===== Responsive ===== */
@media (max-width:768px){
    .wrapper{flex-direction:column;}
    .wrapper .sidebar{width:100%;position:relative;height:auto;}
    .wrapper .main_content{margin-left:0;}
    .wrapper .main_content .table{width:90%;}
    .wrapper .main_content .table table td img{width:100%;}
}
</style>
</head>
<body>

<div class="wrapper">
    <!-- Sidebar -->
    <div class="sidebar">
        <img src="https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/bnd-bank.png" alt="Logo" id="logo">
        <ul>
            <li><img src="https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/ICONS/HOME.png"><a href="bank.php">HOME</a></li>
            <li><img src="https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/ICONS/DEPOSIT.png"><a href="deposit.php">DEPOSIT</a></li>
            <li><img src="https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/ICONS/WITHDRAW.png"><a href="withdraw.php">WITHDRAW</a></li>
            <li><img src="https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/ICONS/TRANSFER.png"><a href="transfer.php">TRANSFER</a></li>
            <li><img src="https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/ICONS/MORE.png"><a href="more.php">MORE</a></li>
            <li><img src="https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/ICONS/LOG%20OUT.png"><a href="login.php">LOG OUT</a></li>
        </ul>
        <div class="social_media">
            <a href="#"><i class="fab fa-facebook-f"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main_content">
        <div class="header">
            <div id="greeting"></div>
            <div class="name"><?php echo htmlspecialchars($userName); ?></div>
            <p>Make your life easier with BND Mastercards</p>
        </div>

        <div class="info">
            <div class="table">
                <table>
                    <tr>
                        <td>
                            <a href="receipt.php">
                                <img src="https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/other-bank.png" alt="">
                                <p>Beginner's BND Debit Card <br><i>Php 500.00</i></p>
                            </a>
                        </td>
                        <td>
                            <a href="receipt2.php">
                                <img src="https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/bnd-card-3.png" alt="">
                                <p>Limited Edition Pink Mastercard <br><i>Php 10,500.00</i></p>
                            </a>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const greetingDiv = document.getElementById('greeting');
    const hour = new Date().getHours();
    greetingDiv.textContent = hour < 12 ? 'Good Morning, ' :
                               hour < 18 ? 'Good Afternoon, ' : 'Good Evening, ';
});
</script>

</body>
</html>
