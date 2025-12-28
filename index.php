<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Loading Page</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: 'Arial', sans-serif;
      background-color: #e2688a;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100vh;
    }



    #progress-container {
      margin: auto;
      width: 90%;
      height: 20px;
      background-color: #ddd;
      border-radius: 10px;
      overflow: hidden;
    }

    #progress-bar {
      height: 100%;
      width: 0;
      background-color: #f294b4;
      border-radius: 10px;
      transition: width 0.3s ease;
    }

    #loading-logo {
       max-width: 60%; 
       margin-left: 20%;
       margin-bottom: 20px;
       margin-top: -5%;
    }

  </style>
</head>
<body>

<div id="loading-container">
<img src="https://bnd-s3-bucket.s3.ap-southeast-1.amazonaws.com/banko.png" alt="Logo" id="loading-logo">
  <div id="progress-container">
    <div id="progress-bar"></div>
  </div>
</div>

<!-- Your page content goes here -->

<script>
  const progressBar = document.getElementById('progress-bar');
  const loadingContainer = document.getElementById('loading-container');

  // Simulate loading progress
  function simulateLoading() {
    let progress = 0;
    const interval = setInterval(() => {
      progress += 1;
      progressBar.style.width = `${progress}%`;

      if (progress >= 100) {
        clearInterval(interval);
        setTimeout(() => {
          // Redirect to login.php after 4 seconds
          window.location.href = 'login.php';
        });
      }
    }, 40);
  }

  // Call the loading simulation function
  simulateLoading();
</script>

</body>
</html>
