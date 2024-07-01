<?php
$conn = mysqli_connect(
    '',
    '',
    '',
    '');

session_start();

error_reporting(E_ERROR | E_PARSE);

if (empty($_SESSION['DG_username'])) {
    header('location: /digiworld/aditus2');
}

function isMobileDevice() {
    return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo
|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i"
, $_SERVER["HTTP_USER_AGENT"]);
}
if(isMobileDevice()){
    $display = "block;";
} else {
    $display = "none;";
}

if (isset($_POST['saveForm'])) {
    $newPassword = hash("sha256", $_POST['newPassword']);
    $password = hash("sha256", $_POST['password']);
    $checkPass = "SELECT * FROM users WHERE username = '".$_SESSION['DG_username']."' AND password = '".$password."'";
    if ($checkedPass = mysqli_query($conn, $checkPass)) {
        if (mysqli_num_rows($checkedPass) == 1) {
            $update = "UPDATE users SET password = '".$newPassword."' WHERE username = '".$_SESSION['DG_username']."'";
            if (mysqli_query($conn, $update)) {
                $result = "Saved successfully.";
            } else {
                $result = "Temporary service error.";
            }
        }
    }
}

if (isset($_POST['save_freezeAccount'])) {
    $password = hash("sha256", $_POST['password']);
    $text = $_POST['freeze_account'];
    $username = $_SESSION['DG_username'];
    if ($text == "Freeze my account.") {
        $checkPass = "SELECT * FROM users WHERE username = '".$username."' AND password = '".$password."'";
        if ($checkedPass = mysqli_query($conn, $checkPass)) {
            if (mysqli_num_rows($checkedPass) == 1) {
                $checkRowExists = "SELECT * FROM special_accounts WHERE username = '".$username."'";
                if ($checkedRowExists = mysqli_query($conn, $checkRowExists)) {
                    if (mysqli_num_rows($checkedRowExists) == 0) {
                        $freeze = "INSERT INTO special_accounts (username, status) VALUES ('$username', 'freeze')";
                        if (mysqli_query($conn, $freeze)) {
                            $result = "Freezed successfully.";
                        } else {
                            $result = "Temporary service error.";
                        }
                    } else {
                        $result = "Your account has already been freezed.";
                    }
                }
            }
        }
    } else {
        $result = "Check the form.";
    }
}

if (isset($_POST['save_unfreezeAccount'])) {
    $password = hash("sha256", $_POST['password']);
    $username = $_SESSION['DG_username'];
    $checkPass = "SELECT * FROM users WHERE username = '".$username."' AND password = '".$password."'";
    if ($checkedPass = mysqli_query($conn, $checkPass)) {
        if (mysqli_num_rows($checkedPass) == 1) {
            $checkRowExists = "SELECT * FROM special_accounts WHERE username = '".$username."'";
            if ($checkedRowExists = mysqli_query($conn, $checkRowExists)) {
                if (mysqli_num_rows($checkedRowExists) > 0) {
                    $deleteData = "DELETE FROM special_accounts WHERE username = '".$username."'";
                    if (mysqli_query($conn, $deleteData)) {
                        $result = "Unfreezed successfully.";
                    } else {
                        $result = "Temporary service error.";
                    }
                } else {
                    $result = "This account is not currently freezed.";
                }
            }
        }
    } else {
        $result = "Temporary service error.";
    }
}
?>
<html>
    <head>
        <link rel="stylesheet" href="styles.css" />
        <link rel="icon" href="/src/appLogo.png" />
        <title>Privacy</title>
    </head>
    <body>
        <header class="header">
            <div class="header-content responsive-wrapper">
                <div class="header-logo">
                    <div>
                        <img src="/src/appLogo.png" width="40" height="40" />
                    </div>
                </div>
                <div class="right-upper-menu">
                <a href="../logout" class="button">
                    <span>Logout</span>
                </a>
                <a href="../Korean/privacy" class="button">
                    <span>한국어</span>
                </a>
                </div>
            </div>
        </header>
        <main class="main">
            <div class="responsive-wrapper">
                <div class="main-header">
                    <h1>Privacy</h1>
                </div>
                <div class="horizontal-tabs">
                    <?php
                    if ($result == "") {
                        echo '<a>Feel free to temporarily freezing your account.</a>';
                    } else if ($result == "Saved successfully.") {
                        echo '<a style="color: blue;">'.$result.'</a>';
                    } else {
                        echo '<a style="color: red;">'.$result.'</a>';
                    }
                    ?>
                </div>
                <div class="content-header" style="display: <?php echo $display; ?>">
                    <div class="content-header-intro">
                        <p>WARN: This content is made for PC users.</p>
                    </div>
                </div>
                <div class="content">
                    <div class="content-panel">
                        <div class="vertical-tabs">
                            <?php
                            if (empty($_GET['task'])) {
                                echo '<a>Change password</a>';
                                echo '<a href="?task=freeze_account">Freeze account</a>';
                                echo '<a href="?task=account_unfreeze">Unfreeze account</a>';
                            } else {
                                if ($_GET['task'] == "change-password") {
                                    echo '<a>Change password</a>';
                                    echo '<a href="?task=account-freeze">Freeze account</a>';
                                    echo '<a href="?task=account_unfreeze">Unfreeze account</a>';
                                } else if ($_GET['task'] == "account-freeze") {
                                    echo '<a href="?task=change-password">Change password</a>';
                                    echo '<a>Freeze account</a>';
                                    echo '<a href="?task=account_unfreeze">Unfreeze account</a>';
                                } else {
                                    echo '<a href="?task=change-password">Change password</a>';
                                    echo '<a href="?task=account-freeze">Freeze account</a>';
                                    echo '<a>Unfreeze account</a>';
                                }
                            }
                            ?>
                        </div>
                    </div>
                    <div class="content-main">
                        <?php
                        if (empty($_GET['task'])) {
                            echo '
                            <form method="post">
                                <article class="card">
                                    <div class="card-header">
                                        <h3>Change password</h3>
                                    </div>
                                    <div class="card-body">
                                        <p>Type in your new password.</p>
                                        <input type="password" name="newPassword" required placeholder="••••••••" class="button" />
                                        <br/>
                                        <br/>
                                        <p>Type in your current password to proceed.</p>
                                        <input type="password" required name="password" placeholder="••••••••" class="button" />
                                        <p></p>
                                        <input type="submit" class="button" value="Save" id="submit_btn" name="saveForm" />
                                    </div>
                                </article>
                            </form>';
                        } else if ($_GET['task'] == "change-password") {
                            echo '
                            <form method="post">
                                <article class="card">
                                    <div class="card-header">
                                        <h3>Change password</h3>
                                    </div>
                                    <div class="card-body">
                                        <p>Type in your new password.</p>
                                        <input type="password" name="newPassword" required placeholder="••••••••" class="button" />
                                        <br/>
                                        <br/>
                                        <p>Type in your current password to proceed.</p>
                                        <input type="password" required name="password" placeholder="••••••••" class="button" />
                                        <p></p>
                                        <input type="submit" class="button" value="Save" id="submit_btn" name="saveForm" />
                                    </div>
                                </article>
                            </form>';
                        } else if ($_GET['task'] == "account-freeze") {
                            echo '
                            <form method="post">
                                <article class="card">
                                    <div class="card-header">
                                        <h3>Freeze account</h3>
                                    </div>
                                    <div class="card-body">
                                        <p>Any subscribed payments will be proceeded regardlessly.</p>
                                        <br/>
                                        <br/>
                                        <p>Re-type the text below.</p>
                                        <input type="text" name="freeze_account" placeholder="Freeze my account." class="button" />
                                        <br/>
                                        <br/>
                                        <p>Type in your password.</p>
                                        <input type="password" required name="password" placeholder="••••••••" class="button" />
                                        <p></p>
                                        <input type="submit" class="button" id="submit_btn" name="save_freezeAccount" value="Freeze" />
                                    </div>
                                </article>
                            </form>';
                        } else {
                            echo '
                            <form method="post">
                                <article class="card">
                                    <div class="card-header">
                                        <h3>Unfreeze account</h3>
                                    </div>
                                    <div class="card-body">
                                        <p>Unfreeze a currently freezed account.</p>
                                        <br/>
                                        <br/>
                                        <p>Type in your password to proceed.</p>
                                        <input type="password" required name="password" placeholder="••••••••" class="button" />
                                        <p></p>
                                        <input type="submit" class="button" id="submit_btn" name="save_unfreezeAccount" value="Unfreeze" />
                                    </div>
                                </article>
                            </form>';
                        }
                        ?>
                    </div>
                </div>
            </div>

        </main>
    </body>
</html>