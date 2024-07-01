<?php
$conn = mysqli_connect(
    '',
    '',
    '',
    '');

session_start();

error_reporting(E_ERROR | E_PARSE);

if (empty($_SESSION['DG_username'])) {
    header('location: /digiworld/paginas/coreanus1/aditus2');
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
                $result = "성공적으로 저장되었습니다";
            } else {
                $result = "일시적인 오류가 발생했습니다";
            }
        }
    }
}

if (isset($_POST['save_freezeAccount'])) {
    $password = hash("sha256", $_POST['password']);
    $text = $_POST['freeze_account'];
    $username = $_SESSION['DG_username'];
    if ($text == "제 계정의 사용을 무한정 중지합니다") {
        $checkPass = "SELECT * FROM users WHERE username = '".$username."' AND password = '".$password."'";
        if ($checkedPass = mysqli_query($conn, $checkPass)) {
            if (mysqli_num_rows($checkedPass) == 1) {
                $checkRowExists = "SELECT * FROM special_accounts WHERE username = '".$username."'";
                if ($checkedRowExists = mysqli_query($conn, $checkRowExists)) {
                    if (mysqli_num_rows($checkedRowExists) == 0) {
                        $freeze = "INSERT INTO special_accounts (username, status) VALUES ('$username', 'freeze')";
                        if (mysqli_query($conn, $freeze)) {
                            $result = "성공적으로 처리되었습니다";
                        } else {
                            $result = "일시적인 오류가 발생했습니다";
                        }
                    } else {
                        $result = "이미 정지된 계정입니다";
                    }
                }
            }
        }
    } else {
        $result = "내용을 확인하세요";
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
                        $result = "성공적으로 처리되었습니다";
                    } else {
                        $result = "일시적인 오류가 발생했습니다";
                    }
                } else {
                    $result = "해당 계정은 정지된 상태가 아닙니다";
                }
            }
        }
    } else {
        $result = "일시적인 오류가 발생했습니다";
    }
}
?>
<html>
    <head>
        <link rel="stylesheet" href="styles.css" />
        <title>보안설정</title>
    <link rel="icon" href="/src/appLogo.png" />
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
                    <span>로그아웃</span>
                </a>
                <a href="/digiworld/privacy" class="button">
                    <span>English</span>
                </a>
                </div>
            </div>
        </header>
        <main class="main">
            <div class="responsive-wrapper">
                <div class="main-header">
                    <h1>보안설정</h1>
                </div>
                <div class="horizontal-tabs">
                    <?php
                    if ($result == "") {
                        echo '<a>개인 기기 분실 시 계정을 일시적으로 정지할 수 있습니다</a>';
                    } else if ($result == "성공적으로 저장되었습니다") {
                        echo '<a style="color: blue;">'.$result.'</a>';
                    } else {
                        echo '<a style="color: red;">'.$result.'</a>';
                    }
                    ?>
                </div>
                <div class="content-header" style="display: <?php echo $display; ?>">
                    <div class="content-header-intro">
                        <p>경고: 본 컨텐츠는 PC 이용자를 위해 제작되었습니다.</p>
                    </div>
                </div>
                <div class="content">
                    <div class="content-panel">
                        <div class="vertical-tabs">
                            <?php
                            if (empty($_GET['task'])) {
                                echo '<a>비밀번호 변경</a>';
                                echo '<a href="?task=account-freeze">계정 정지</a>';
                                echo '<a href="?task=account-unfreeze">계정 정지 해제</a>';
                            } else {
                                if ($_GET['task'] == "change-password") {
                                    echo '<a>비밀번호 변경</a>';
                                    echo '<a href="?task=account-freeze">계정 정지</a>';
                                    echo '<a href="?task=account-unfreeze">계정 정지 해제</a>';
                                } else if ($_GET['task'] == "account-freeze") {
                                    echo '<a href="?task=change-password">비밀번호 변경</a>';
                                    echo '<a>계정 정지</a>';
                                    echo '<a href="?task=account-unfreeze">계정 정지 해제</a>';
                                } else {
                                    echo '<a href="?task=change-password">비밀번호 변경</a>';
                                    echo '<a href="?task=account-freeze">계정 정지</a>';
                                    echo '<a>계정 정지 해제</a>';
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
                                        <h3>비밀번호 변경</h3>
                                    </div>
                                    <div class="card-body">
                                        <p>신규 비밀번호를 입력하세요.</p>
                                        <input type="password" name="newPassword" required placeholder="••••••••" class="button" />
                                        <br/>
                                        <br/>
                                        <p>변경 사항을 저장하기 위해 현재 비밀번호를 입력하세요.</p>
                                        <input type="password" required name="password" placeholder="••••••••" class="button" />
                                        <p></p>
                                        <input type="submit" class="button" id="submit_btn" name="saveForm" value="수정사항 저장하기" />
                                    </div>
                                </article>
                            </form>';
                        } else if ($_GET['task'] == "change-password") {
                            echo '
                            <form method="post">
                                <article class="card">
                                    <div class="card-header">
                                        <h3>비밀번호 변경</h3>
                                    </div>
                                    <div class="card-body">
                                        <p>신규 비밀번호를 입력하세요.</p>
                                        <input type="password" name="newPassword" required placeholder="••••••••" class="button" />
                                        <br/>
                                        <br/>
                                        <p>변경 사항을 저장하기 위해 현재 비밀번호를 입력하세요.</p>
                                        <input type="password" required name="password" placeholder="••••••••" class="button" />
                                        <p></p>
                                        <input type="submit" class="button" id="submit_btn" name="saveForm" value="수정사항 저장하기" />
                                    </div>
                                </article>
                            </form>';
                        } else if ($_GET['task'] == "account-freeze") {
                            echo '
                            <form method="post">
                                <article class="card">
                                    <div class="card-header">
                                        <h3>계정 정지</h3>
                                    </div>
                                    <div class="card-body">
                                        <p>등록되어 있는 정기 결제는 예외없이 진행됩니다.</p>
                                        <br/>
                                        <br/>
                                        <p>아래 나와 있는 글자대로 입력하세요.</p>
                                        <input type="text" name="freeze_account" placeholder="제 계정의 사용을 무한정 중지합니다" class="button" />
                                        <br/>
                                        <br/>
                                        <p>변경 사항을 저장하기 위해 계정 비밀번호를 입력하세요.</p>
                                        <input type="password" required name="password" placeholder="••••••••" class="button" />
                                        <p></p>
                                        <input type="submit" class="button" id="submit_btn" name="save_freezeAccount" value="정지하기" />
                                    </div>
                                </article>
                            </form>';
                        } else {
                            echo '
                            <form method="post">
                                <article class="card">
                                    <div class="card-header">
                                        <h3>계정 정지 해제</h3>
                                    </div>
                                    <div class="card-body">
                                        <p>정지된 계정을 정지해제합니다.</p>
                                        <br/>
                                        <br/>
                                        <p>변경 사항을 저장하기 위해 계정 비밀번호를 입력하세요.</p>
                                        <input type="password" required name="password" placeholder="••••••••" class="button" />
                                        <p></p>
                                        <input type="submit" class="button" id="submit_btn" name="save_unfreezeAccount" value="해제하기" />
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