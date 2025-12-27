<?php
session_start();

// Redirect to login page if not logged in
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
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Checkout</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<style>
body { font-family: Arial; font-size: 17px; padding: 8px; background-color: #f3f5f9; }
h2 { text-align: center; color: #d85375; padding: 10px; }
* { box-sizing: border-box; }
.row { display: flex; flex-wrap: wrap; margin: 0 -16px; }
.col-25 { flex: 25%; }
.col-50 { flex: 50%; }
.col-75 { flex: 75%; }
.col-25, .col-50, .col-75 { padding: 0 16px; }
.container { background-color: #fefefe; padding: 20px; border: 1px solid #ccc; border-radius: 5px; }
input[type=text] { width: 100%; margin-bottom: 20px; padding: 12px; border: 1px solid #ccc; border-radius: 3px; }
label { margin-bottom: 10px; display: block; }
.btn { background-color: #d85375; color: #f3f5f9; padding: 12px; margin: 10px 0; border: none; width: 100%; border-radius: 3px; cursor: pointer; font-size: 17px; }
.btn:hover { background-color: pink; }
a { color: #fefefa; }
hr { border: 1px solid lightgrey; }
span.price { float: right; color: grey; }

/* Responsive */
@media (max-width: 800px) {
  .row { flex-direction: column-reverse; }
  .col-25 { margin-bottom: 20px; }
}

/* Modal Styles */
.modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); padding-top: 60px; }
.modal-content { background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 80%; border-radius: 5px; }
.close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
.close:hover, .close:focus { color: black; text-decoration: none; }
.modal-content p { margin: 5px 0; }
#logo { display:block; margin:auto; max-width:150px; margin-bottom:20px; }
</style>
</head>
<body>

<h2>BANKO NI DIANNE CHECKOUT FORM</h2>
<hr>

<!-- Optional Logo -->
<img src="<?php echo $s3_base; ?>bnd%20vertical%20transparent.png" alt="BND Logo" id="logo">

<div class="row">
  <!-- Checkout Form -->
  <div class="col-75">
    <div class="container">
      <form id="checkoutForm">
        <div class="row">
          <div class="col-50">
            <h3>Billing Address</h3>
            <label for="fname">Full Name</label>
            <input type="text" id="fname" name="firstname" placeholder="John M. Doe" required>
            <label for="email">Email</label>
            <input type="text" id="email" name="email" placeholder="john@example.com" required>
            <label for="adr">Address</label>
            <input type="text" id="adr" name="address" placeholder="Sagpon, Guevara Street, Albay" required>
            <label for="city">City/Municipality</label>
            <input type="text" id="city" name="city" placeholder="Legazpi" required>

            <div class="row">
              <div class="col-50">
                <label for="state">Region</label>
                <input type="text" id="state" name="state" placeholder="Bicol" required>
              </div>
              <div class="col-50">
                <label for="zip">Zip Code</label>
                <input type="text" id="zip" name="zip" placeholder="1001" required maxlength="4" oninput="validateZipCode(this)">
              </div>
            </div>
          </div>
        </div>
        <input type="submit" value="Proceed to checkout" class="btn">
      </form>
    </div>
  </div>

  <!-- Cart Summary -->
  <div class="col-25">
    <div class="container">
      <h4>Cart <span class="price" style="color:black"><b>[1]</b></span></h4>
      <p>Limited Edition Pink Mastercard <span class="price">Php. 10,500.00</span></p>
      <hr>
      <p>Total: <span class="price" style="color:black"><b>Php. 10,500.00</b></span></p>
    </div>
  </div>
</div>

<!-- Receipt Modal -->
<div id="receiptModal" class="modal">
  <div class="modal-content">
    <span class="close" id="modalClose">&times;</span>
    <h2>BND Receipt</h2>
    <hr>
    <p id="receiptTime">Date and Time: <span id="currentDateTime"></span></p>
    <p>Product: Limited Edition Pink Mastercard</p>
    <p id="receiptFullName"></p>
    <p id="receiptEmail"></p>
    <p id="receiptAddress"></p>
    <p id="receiptTotal">Total Amount: Php. 10,500.00</p>
  </div>
</div>

<script>
function validateZipCode(inputField) {
  inputField.value = inputField.value.replace(/\D/g, '').slice(0,4);
}

// Open Receipt Modal on form submit
document.getElementById('checkoutForm').addEventListener('submit', function(e) {
  e.preventDefault();
  var fullName = document.getElementById('fname').value;
  var email = document.getElementById('email').value;
  var address = document.getElementById('adr').value;
  var city = document.getElementById('city').value;
  var region = document.getElementById('state').value;
  var zip = document.getElementById('zip').value;

  if(fullName && email && address && city && region && zip) {
    var modal = document.getElementById('receiptModal');
    document.getElementById('currentDateTime').innerText = new Date().toLocaleString();
    document.getElementById('receiptFullName').innerText = 'Full Name: ' + fullName;
    document.getElementById('receiptEmail').innerText = 'Email: ' + email;
    document.getElementById('receiptAddress').innerText = 'Address: ' + address + ', ' + city + ', ' + region + ', ' + zip;
    modal.style.display = 'block';
  } else {
    alert('Please fill in all required fields.');
  }
});

// Close modal
document.getElementById('modalClose').onclick = function() {
  document.getElementById('receiptModal').style.display = 'none';
}
window.onclick = function(event) {
  var modal = document.getElementById('receiptModal');
  if (event.target == modal) modal.style.display = 'none';
}
</script>

</body>
</html>
