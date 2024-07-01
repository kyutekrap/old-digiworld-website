<?php
session_start();

$conn = mysqli_connect(
    '',
    '',
    '',
    '');

error_reporting(E_ERROR | E_PARSE);

$error = "";
if (isset($_POST['loginBtn'])) {
    $username = hash("sha256", $_POST['username']);
    $password = hash("sha256", $_POST['password']);
    
    $checkUser = "SELECT * FROM users WHERE username = '".$username."' AND password = '".$password."'";
    if ($checkedUser = mysqli_query($conn, $checkUser)) {
        if (mysqli_num_rows($checkedUser) == 1) {
            if ($row = mysqli_fetch_assoc($checkedUser)) {
                $_SESSION['DG_displayName'] = $row['displayName'];
                $_SESSION['DG_username'] = $row['username'];
                header("Location: /digiworld/Korean");
            }
        } else {
            $error = "이용자 정보가 없습니다";
        }
    } else {
        $error = "일시적인 오류가 발생했습니다.";
    }
}
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>로그인</title>
    <link rel="icon" href="/src/appLogo.png" />
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f2f2f2;
            padding: 0;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh; /* Center vertically on the screen */
        }

        .container {
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            text-align: center;
            min-width: 300px;
        }

        h2 {
            color: #333;
        }

        input[type="text"],
        input[type="password"] {
            width: calc(100% - 20px);
            padding: 10px;
            margin: 8px 0;
            border: 1px solid #ccc;
            border-radius: 3px;
        }

        input[type="submit"] {
            background-color: #666; /* Grey color */
            color: #fff;
            border: none;
            border-radius: 3px;
            padding: 10px 20px;
            cursor: pointer;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        input[type="submit"]:hover {
            background-color: #000;
        }

        /* Responsive Design */
        @media screen and (max-width: 600px) {
            .container {
                width: 90%;
                margin: 10px auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>로그인</h2>
        <form method="post">
            <input type="text" placeholder="아이디" required name="username">
            <input type="password" placeholder="비밀번호" required name="password">
            <input type="submit" value="로그인" name="loginBtn">
            <p style="color: red;"><?php echo $error; ?></p>
          </form>
    </div>
</body>
</html>