<?php
// Start session and check if user is logged in
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// Base S3 URL
$s3_base = "https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transfer</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://kit.fontawesome.com/b99e675b6e.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Josefin Sans', sans-serif; list-style: none; text-decoration: none; }
        body { background-color: #f3f5f9; }
        .wrapper { display: flex; position: relative; }
        .sidebar { width: 300px; height: 100%; background: #e2688a; padding: 30px 0; position: fixed; }
        .sidebar h2 { color: #fff; text-transform: uppercase; text-align: center; margin-bottom: 30px; }
        .sidebar ul li { padding: 25px; border-bottom: 1px solid rgba(0,0,0,0.05); border-top: 1px solid rgba(255,255,255,0.05); text-indent: 10px; }
        .sidebar ul li a { color: white; display: block; }
        .sidebar ul li:hover { background-color: #d85375; }
        .sidebar ul li:hover a { color: #fff; }
        .sidebar .social_media { position: absolute; bottom: 0; left: 50%; transform: translateX(-50%); display: flex; }
        .sidebar .social_media a { display: block; width: 40px; background: #d85375; height: 40px; line-height: 45px; text-align: center; margin: 0 5px; color: #fff; border-top-left-radius: 5px; border-top-right-radius: 5px; }
        .main_content { width: 100%; margin-left: 300px; }
        .main_content .header { margin: 5%; background: #f3f5f9; color: #d85375; border-bottom: 1px solid #e0e4e8; }
        .header p { color: #717171; font-style: italic; font-size: 13px; }
        .main_content .table { margin: 2%; width: 80%; text-align: center; color: #717171; }
        .main_content .table table td img { display: block; margin: auto; width: 80%; border: 2px solid #e2688a; border-radius: 5%; }
        .main_content .table table td p { padding: 10px; cursor: pointer; color: #717171; }
        #logo { margin-left: 15%; margin-bottom: 5%; max-width: 200px; display:block; margin:auto; }
    </style>
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
}

.header p{
  color: #717171;
  font-style: italic;
  font-size: 13px;
}
.wrapper .main_content .table{
  margin: 2%;
  width: 60%;
  text-align: center;
  color: #717171;
}

.wrapper .main_content .table table{
  align-content: center;
}

.wrapper .main_content .table table td img{
  display: block;
  margin-left: auto;
  margin-right: auto;
  width: 80%;
  border: 2px solid #e2688a;
  align-content: center;
  border-radius: 5%;
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
<body>

<div class="wrapper">
    <div class="sidebar">
        <img src="<?php echo $s3_base; ?>bnd-bank.png" alt="BND Logo" id="logo">
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

    <div class="main_content">
        <div class="header">
            <h2>TRANSFER</h2>
            <p>Choose the type of transfer you want to perform</p>
            <br>
        </div>

        <div class="table">
            <table>
                <tr>
                    <td>
                        <a href="transfer_own.php">
                            <img src="<?php echo $s3_base; ?>ICONS/PERSON.png" alt="Transfer To Own Bank">
                            <p>Transfer To Own Bank</p>
                        </a>
                    </td>
                    <td>
                        <a href="transfer_to_another.php">
                            <img src="<?php echo $s3_base; ?>ICONS/BANK.png" alt="Transfer To Another Bank">
                            <p>Transfer To Another Bank</p>
                        </a>
                    </td>
                    <td>
                        <a href="transfer_BND.php">
                            <img src="<?php echo $s3_base; ?>ICONS/PIGGY%20BANK%201.png" alt="Transfer To Another BND Bank">
                            <p>Transfer To Another BND Bank</p>
                        </a>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>

</body>
</html>
