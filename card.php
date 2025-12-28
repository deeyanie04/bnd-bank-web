<?php
session_start();
include 'db.php'; // RDS connection

// Ensure user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Optional: Fetch user info for greeting
$stmt = $mysqli->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($userName);
$stmt->fetch();
$stmt->close();

// Fetch available cards
$cards = [];
$result = $mysqli->query("SELECT id, name, image_url, price FROM cards ORDER BY id ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $cards[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Debit Cards</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://kit.fontawesome.com/b99e675b6e.js"></script>
</head>
<style>
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
}

.header p{
  color: #717171;
  font-style: italic;
  font-size: 13px;
}
.wrapper .main_content .table{
  margin: 1%;
  width: 80%;
  text-align: center;
  color: #717171;
}

.wrapper .main_content .table table{
  align-content: left;
  width: 80%;
}

.wrapper .main_content .table table td img{
  display: block;
  margin-left: auto;
  margin-right: auto;
  width: 50%;
}

.wrapper .main_content .table table td p{
  padding: 10px;
  cursor: pointer;
  color: #717171;
}

#logo {
  margin-left: 15% ;
  margin-bottom: 5%;
  max-width: 200px; 
}

</style>
*{margin:0;padding:0;box-sizing:border-box;list-style:none;text-decoration:none;font-family:'Josefin Sans',sans-serif;}
body{background-color:#f3f5f9;}
.wrapper{display:flex;position:relative;}
.wrapper .sidebar{width:300px;height:100%;background:#e2688a;padding:30px 0;position:fixed;}
.wrapper .sidebar ul li img{width:20px;float:left;}
.wrapper .sidebar h2{color:#fff;text-transform:uppercase;text-align:center;margin-bottom:30px;}
.wrapper .sidebar ul li{padding:25px;border-bottom:1px solid rgba(0,0,0,0.05);border-top:1px solid rgba(255,255,255,0.05);text-indent:10px;}
.wrapper .sidebar ul li a{color:white;display:block;}
.wrapper .sidebar ul li:hover{background-color:#d85375;}
.wrapper .sidebar ul li:hover a{color:#fff;}
.wrapper .sidebar .social_media{position:absolute;bottom:0;left:50%;transform:translateX(-50%);display:flex;}
.wrapper .sidebar .social_media a{display:block;width:40px;background:#d85375;height:40px;line-height:45px;text-align:center;margin:0 5px;color:#fff;border-top-left-radius:5px;border-top-right-radius:5px;}
.wrapper .main_content{width:100%;margin-left:300px;}
.wrapper .main_content .header{margin:5%;background:#f3f5f9;color:#d85375;border-bottom:1px solid #e0e4e8;}
.wrapper .main_content .table{margin:1%;width:80%;text-align:center;color:#717171;}
.wrapper .main_content .table table{width:100%;}
.wrapper .main_content .table table td img{display:block;margin:0 auto;width:50%;}
.wrapper .main_content .table table td p{padding:10px;cursor:pointer;color:#717171;}
#logo{margin-left:15%;margin-bottom:5%;max-width:200px;}
</style>
<body>

<div class="wrapper">
    <div class="sidebar">
        <!-- Sidebar images -->
        <img src="https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/bnd%20vertical%20transparent.png" alt="Logo" id="logo">
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

    <div class="main_content" id="main_content">
        <div class="header">
            <h2>NEW DEBIT CARDS</h2>
            <p>Make your life easier with BND Mastercards</p>
        </div>  

        <div class="table">
            <table>
                <tr>
                    <?php foreach($cards as $card): ?>
                        <td>
                            <a href="purchase_card.php?card_id=<?php echo $card['id']; ?>">
                                <img src="<?php echo htmlspecialchars($card['image_url']); ?>" alt="<?php echo htmlspecialchars($card['name']); ?>">
                                <p><?php echo htmlspecialchars($card['name']); ?><br><i>Php. <?php echo number_format($card['price'],2); ?></i></p>
                            </a>
                        </td>
                    <?php endforeach; ?>
                </tr>
            </table>
        </div>
    </div>
</div>

</body>
</html>
