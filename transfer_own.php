<?php
session_start();

// Ensure user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Transfer to Own Account</title>
<script src="https://kit.fontawesome.com/b99e675b6e.js"></script>

<style>
*{
  margin:0;
  padding:0;
  box-sizing:border-box;
  font-family:'Josefin Sans', sans-serif;
}
body{
  background:#f3f5f9;
}

.wrapper{
  display:flex;
}

.sidebar{
  width:300px;
  height:100vh;
  background:#e2688a;
  position:fixed;
}

.sidebar img{
  width:180px;
  margin:20px auto;
  display:block;
}

.sidebar ul li{
  list-style:none;
  padding:20px;
}

.sidebar ul li a{
  color:#fff;
  text-decoration:none;
  display:block;
}

.sidebar ul li:hover{
  background:#d85375;
}

.main_content{
  margin-left:300px;
  width:100%;
}

.header{
  margin:40px;
  text-align:center;
  color:#e2688a;
}

.header p{
  font-size:14px;
  color:#717171;
}

.content{
  margin-top:100px;
  text-align:center;
}

.content h3{
  color:#e2688a;
  margin-bottom:10px;
}

.content p{
  color:#717171;
  font-size:14px;
}
</style>
</head>

<body>

<div class="wrapper">
  <div class="sidebar">
    <img src="https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/bnd-bank.png" alt="Logo">

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
      <h2>TRANSFER TO OWN ACCOUNT</h2>
      <p>Banko ni Dianne, Bank na Dito!</p>
    </div>

    <div class="content">
      <h3>No eligible accounts found</h3>
      <p>
        You currently have only one account registered.<br>
        Transfers to own accounts require multiple accounts under the same user.
      </p>
    </div>
  </div>
</div>

</body>
</html>
