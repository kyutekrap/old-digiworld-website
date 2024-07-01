<?php
session_start();

$conn = mysqli_connect(
  '',
  '',
  '',
  '');

error_reporting(E_ERROR | E_PARSE);

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
?>
<html>
    <head>
        <link rel="stylesheet" href="styles_v2.css" />
        <link rel="icon" href="/src/appLogo.png" />
        <title>Digiworld</title>
    </head>
    <body>
        <header class="header">
            <div class="header-content responsive-wrapper">
                <div class="header-logo">
                    <a>
                        <div>
                            <img src="/src/appLogo.png" height="40" width="40" />
                        </div>
                    </a>
                </div>
                <div class="upper-right-menu">
                <?php
                if (empty($_SESSION['DG_username'])) {
                    echo '<a href="./login" class="button">Login</a>';
                } else {
                    echo '<a href="./logout" class="button">Logout</a>
                        <a href="./privacy" class="button">Privacy</a>';
                    }
                ?>
                <a href="./Korean" class="button">한국어</a>
                </div>
            </div>
        </header>
        <main class="main">
            <div class="responsive-wrapper">
                <div class="main-header">
                    <h1>Digiworld</h1>
                </div>
                <div class="horizontal-tabs">
                    <?php
                    if (empty($_SESSION['DG_username'])) {
                        echo '<a>Use after logging in.</a>';
                    } else {
                        echo '<a>Welcome, '.$_SESSION['DG_displayName'].'.</a>';
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
                            if ($_SESSION['DG_username'] == "") {
                                echo '
                                <a href="./product?id=Classic U"; class="text_link">Classic U</a>';
                            } else {
                                echo '
                                <a href="./myproduct?id=Classic U"; class="text_link">Classic U</a>';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="content-main">
                        <div class="card-grid">
                            
                            <article class="card">
                                <div class="card-header">
                                    <div>
                                        <span><img src="/src/upbit.png" /></span>
                                        <h3>Classic U</h3>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <p>Composite fund for cryptocurrencies listed on Upbit</p>
                                </div>
                                <div class="card-footer">
                                    <?php
                                    if ($_SESSION['DG_username'] == "") {
                                        echo '<a href="./product?id=Classic U" class="text_link">See details</a>';
                                    } else {
                                        echo '<a href="./myproduct?id=Classic U" class="text_link">See details</a>';
                                    }
                                    ?>
                                </div>
                            </article>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </body>
</html>