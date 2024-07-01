<?php
$conn = mysqli_connect(
    '',
    '',
    '',
    '');

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

error_reporting(E_ERROR | E_PARSE);

session_start();

if (empty($_GET['id'])) {
    header('location: /digiworld');
} else {
    $productType = $_GET['id'];
}

if (empty($_SESSION['DG_username'])) {
    header('location: /digiworld/login');
} else {
    $user = $_SESSION['DG_username'];
}

$underlyingAsset = "";
$userCapital = 0;
$datetime = "";

$checkProduct = "SELECT * FROM products_test WHERE productType = '".$productType."'";
if ($checkedProduct = mysqli_query($conn, $checkProduct)) {
    if (mysqli_num_rows($checkedProduct) != 1) {
        header('location: /digiworld');
    } else {
        if ($row = mysqli_fetch_assoc($checkedProduct)) {
            $underlyingAsset = $row['english_desc'];
        }
    }
}

$getUserCapital = "SELECT * FROM invest WHERE userId = '".$user."' AND productType = '".$productType."'";
    if ($gotUserCapital = mysqli_query($conn, $getUserCapital)) {
        $row = mysqli_fetch_assoc($gotUserCapital);
        
        $userCapital += $row['userKRW'];
        $datetime = $row['datetime'];
    }

    $array_to_print = array();
    $count = 0;
    $count_finalPnl = 0;
    
    if (isset($_POST['filter_btn'])) {
        $beginning_date = $_POST['beginning_date'];
        $ending_date = $_POST['ending_date'];
        $ticker = $_POST['ticker'];
        
        if ($beginning_date != "") {
            if ($beginning_date < $datetime) {
                $bd = "AND datetime >= '".$datetime."'";
            } else {
                $bd = "AND datetime >= '".$beginning_date."'";
            }
        } else {
            $bd = "AND datetime >= '".$datetime."'";
        }
        
        if ($ending_date != "") {
            $ed = "AND datetime <= '".$ending_date."'";
        } else {
            $ed = "";
        }
        
        if ($ticker != "") {
            $t = "AND LOWER(underlyingAsset) = '".strtolower($ticker)."'";
        } else {
            $t = "";
        }
    } else {
        $bd = "AND datetime >= '".$datetime."'";
        $ed = "";
        $t = "";
    }
    
    $getPerformance = "SELECT * FROM performance WHERE productType = '".$productType."'".$bd.$ed.$t." ORDER BY datetime DESC";
    if ($gotPerformance = mysqli_query($conn, $getPerformance)) {
        while ($row = mysqli_fetch_assoc($gotPerformance)) {
            
            $phpdate = strtotime($row['datetime']);
            $mysqldate = date('Y-m-d H:i', $phpdate);

            $finalConsumption = number_format((float)$row['consumption'], 1, '.', '');
            
            $tradingVolume = (($row['totalPrice'] * $row['holding']) / $row['consumption']) * 100;
            
            $userPie = ($userCapital / $row['balance']);
            $userPnl = $row['tradePnl'] * ($userPie);
            
            if (strpos($row['row_id'], "dkt") === false) {
                $sumPnl += $userPnl;
            }
            
            if ($row['action'] == 1) {
                $action = 'buy';
                $mypnl = 0;
            } else {
                $action = 'sell';
                $mypnl = number_format($userPnl, 1, '.', '');
            }

            array_push($array_to_print, array('datetime'=>$mysqldate, 'underlyingAsset'=>$row['underlyingAsset'], 'action'=>$action, 'totalPrice'=>$row['totalPrice'], 'finalConsumption'=>$finalConsumption, 'finalTpl'=>$mypnl, 'barePrice'=>$row['barePrice'], 'row_id'=>$row['row_id']));
            
            $count += 1;
        }

            $count_finalPnl = number_format($sumPnl, 2, '.', ',');
            $average = ($sumPnl / $userCapital) * 100;
            $average = number_format($average, 1, '.', ',');
            
            array_push($array_to_print, array("average"=>$average, "aggregate_total"=>$count_finalPnl));
    }

$last = end($array_to_print);

$daily_data = array();
$daily_pnl = 0;
foreach ($array_to_print as $index=>$value) {
    if (strpos($value['row_id'], 'dkt') !== false) {
        continue;
    }
    
    $phpdate = strtotime($value['datetime']);
    $mysqldate = date('Y-m-d', $phpdate);
    
    $pnl = $value['finalTpl'];

    if ($index == 0) {
        $latest_date = $mysqldate;
        $daily_pnl+=$pnl;
    } else {
        if ($latest_date < "2023-06-01") {
            continue;
        }
        
        if ($latest_date == $mysqldate) {
            $daily_pnl+=$pnl;
        } else {
            if ($latest_date != "" && $daily_pnl != "") {
                array_push($daily_data, array('date'=>$latest_date, 'pnl'=>number_format($daily_pnl, 1, ".", "")));
            }
            $daily_pnl = $pnl;
            $latest_date = $mysqldate;
        }
    }
    
    if ($index == count($array_to_print)-2) {
        if ($latest_date != "" && $daily_pnl != "") {
            array_push($daily_data, array('date'=>$latest_date, 'pnl'=>number_format($daily_pnl, 1, ".", "")));
        }
        break;
    }
}
$daily_data = array_reverse($daily_data);
?>
<html>
    <head>
        <link rel="stylesheet" href="styles.css" />
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.6.0/Chart.min.js"></script>
        <link rel="icon" href="/src/appLogo.png" />
        <title><?php echo $productType; ?></title>
    </head>
    <body onload="drawCharts()">
        <header class="header">
            <div class="header-content responsive-wrapper">
                <div class="header-logo">
                        <div>
                            <img src="/src/appLogo.png" width="40" height="40" />
                        </div>
                </div>
                <div class="upper-right-menu">
                <?php
                if (empty($_SESSION['DG_username'])) {
                    echo '<a href="../login" class="button">
                    <span>Login</span>
                    </a>';
                } else {
                    echo '<a href="../logout" class="button">
                    <span>Logout</span>
                    </a>';
                }
                ?>
                <a href="../Korean/myproduct?id=Classic U" class="button">
                    <span>한국어</span>
                </a>
                </div>
            </div>
        </header>
        <main class="main">
            <div class="responsive-wrapper">
                <div class="main-header">
                    <h1><?php echo $productType; ?></h1>
                </div>
                <div class="horizontal-tabs">
                    <a><?php echo $underlyingAsset; ?></a>
                </div>
                <div class="content-header" style="display: none;">
                    <div class="content-header-intro">
                    </div>
                    <div class="content-header-actions">
                    </div>
                </div>
                <div class="content-header" style="display: <?php echo $display; ?>">
                    <div class="content-header-intro">
                        <p>WARN: This content is made for PC users.</p>
                    </div>
                </div>
                <div class="content">
                    <div class="content-panel">
                        <div class="vertical-tabs">
                            <a>Overview</a>
                            <a>Filter</a>
                            <a>Records</a>
                        </div>
                    </div>
                    <div class="content-main">
                        <div class="card-grid" id="overview">
                            <article class="card">
                                <div class="card-header">
                                    <div>
                                        <?php
                                        $count = 0;
                                        foreach ($last as $key=>$value) {
                                            if ($count == 1) {
                                                echo '<h3>'.$value.'KRW</h3>';
                                            }
                                            $count += 1;
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <a>My Net Realized Profit</a>
                                </div>
                            </article>
                            <article class="card">
                                <div class="card-header">
                                    <div>
                                        <?php
                                        $count = 0;
                                        $display = "block;";
                                        foreach ($last as $key=>$value) {
                                            if ($count == 0) {
                                                if ($value == "nan") {
                                                    echo '<h3>0%</h3>';
                                                    $display = "none;";
                                                } else {
                                                    echo '<h3>'.$value.'%</h3>';
                                                }
                                            }
                                            $count += 1;
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <a>My Average Monthly ROI</a>
                                </div>
                            </article>
                        </div>
                        
                        <div style="display: <?php echo $display; ?>">
                          <div><small>※ There may be data obsruction for visual purposes. </small></div>
                          <br/>
                          <canvas id="lineChartBlueGreen" width="400px" height="300px"></canvas>
                        </div>
                        <script>
                        var results = <?php echo json_encode($daily_data); ?>;

                        
                        function drawLineChart(div_id, results, yColumn, yLabel, xAxes, firstColour, secondColour, thirdColour, fourthColour) {
                          var ctx = document.getElementById(div_id).getContext("2d");
                          var width = window.innerWidth || document.body.clientWidth;
                          var gradientStroke = ctx.createLinearGradient(0, 0, width, 0);
                          gradientStroke.addColorStop(0, firstColour);
                          gradientStroke.addColorStop(0.3, secondColour);
                          gradientStroke.addColorStop(0.6, thirdColour);
                          gradientStroke.addColorStop(1, fourthColour);
                        
                          var labels = results.map(function(item) {
                            return item[xAxes];
                          });
                          var data = results.map(function(item) {
                            return item[yColumn];
                          });
                        
                          var myChart = new Chart(ctx, {
                            type: "line",
                            data: {
                              labels: labels,
                              datasets: [
                                {
                                  label: yLabel,
                                  borderColor: gradientStroke,
                                  pointBorderColor: gradientStroke,
                                  pointBackgroundColor: gradientStroke,
                                  pointHoverBackgroundColor: gradientStroke,
                                  pointHoverBorderColor: gradientStroke,
                                  pointBorderWidth: 8,
                                  pointHoverRadius: 8,
                                  pointHoverBorderWidth: 1,
                                  pointRadius: 3,
                                  fill: false,
                                  borderWidth: 4,
                                  data: data
                                }
                              ]
                            },
                            options: {
                              responsive: true,
                              maintainAspectRatio: false,
                              legend: {
                                position: "none"
                              },
                              scales: {
                                yAxes: [
                                  {
                                    ticks: {
                                      fontFamily: "Roboto Mono",
                                      fontColor: "#556F7B",
                                      fontStyle: "bold",
                                      beginAtZero: true,
                                      maxTicksLimit: 5,
                                      padding: 20
                                    },
                                    gridLines: {
                                      drawTicks: false,
                                      display: false,
                                      drawBorder: false
                                    }
                                  }
                                ],
                                xAxes: [
                                  {
                                    gridLines: {
                                      zeroLineColor: "transparent"
                                    },
                                    ticks: {
                                      padding: 20,
                                      fontColor: "#556F7B",
                                      fontStyle: "bold",
                                      fontFamily: "Roboto Mono"
                                    },
                                    gridLines: {
                                      drawTicks: false,
                                      display: false,
                                      drawBorder: false
                                    }
                                  }
                                ]
                              }
                            }
                          });
                        }
                        
                        function drawCharts() {
                          drawLineChart(
                            "lineChartBlueGreen",
                            results,
                            "pnl",
                            "PnL",
                            "date",
                            "#09005c",
                            "#095379",
                            "#00ffa2",
                            "#00ffa2"
                          );
                        }
                        </script>
                        
                        
                        <article class="card" id="filter">
                            <form method="post">
                            <div class="card-header">
                                <div>
                                    <h3>Filter</h3>
                                </div>
                            </div>
                            <div class="card-body">
                                <label>Start Date</label><br/>
                                <input type="date" name="beginning_date" value="<?php if(isset($_POST['beginning_date'])) { echo $_POST['beginning_date']; } ?>" /><br/>
                                <label>End Date</label><br/>
                                <input type="date" name="ending_date" value="<?php if(isset($_POST['ending_date'])) { echo $_POST['ending_date']; } ?>" /><br/>
                                <label>Underlying Tickers</label><br/>
                                <input type="text" placeholder="BTC" name="ticker" value="<?php if(isset($_POST['ticker'])) { echo $_POST['ticker']; } ?>" />
                            </div>
                            <div class="card-footer">
                                <input type="submit" id="filter_btn" style="display: none;" name="filter_btn" />
                                <label for="filter_btn" style="cursor: pointer;">
                                    <a>Run Filter</a>
                                </label>
                            </div>
                            </form>
                        </article>
                        <article class="card" id="results">
                            <div class="card-header">
                                <div>
                                    <h3>Real Fake Trading Data</h3>
                                </div>
                                <div>
                                    <h5>Table includes data obstruction for prevention of information misuse.</h5>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php
                                echo '<table>
                                
                                <col>
                              <colgroup span="5"></colgroup>
                              <colgroup span="5"></colgroup>
                              <tr>
                                <th colspan="1" scope="colgroup" class="sticky1"></th>
                                <th colspan="1" scope="colgroup"></th>
                                
                                <th colspan="2" scope="colgroup">Market Info</th>
                                <th colspan="2" scope="colgroup">Trading Info</th>
                              </tr>
                                <tr>
                                  <th scope="col" class="sticky1">Datetime</th>
                                  <th scope="col">Ticker</th>
                                  <th scope="col">Position</th>
                                  <th scope="col">Spot</th>
                                  <th scope="col">Portfolio Weight</th>
                                </tr>';
                                
                                $count = 0;
                                foreach ($array_to_print as $key=>$row) {
                                    if ($count == count($array_to_print)-1) {
                                        continue;
                                    }
                                    
                                    echo '<td class="sticky1">'.$row['datetime'].'</td>';
                                    echo '<td>'.$row['underlyingAsset'].'</td>';
                                    
                                    if ($row['action'] == 'buy') {
                                        $action = 'Buy';
                                        $actionColor = '#fa4659';
                                    } else {
                                        $action = 'Sell';
                                        $actionColor = '#22267b';
                                    }
                                
                                    echo '<td style="color: '.$actionColor.';">'.$action.'</td>';
                                    
                                    if ($row['barePrice'] == 0) {
                                        echo '<td>'.$row['totalPrice'].'KRW</td>';
                                    } else {
                                        echo '<td>'.$row['barePrice'].'KRW</td>';
                                    }
                                    
                                    if ($action == "Buy") {
                                        echo '<td></td>';
                                    } else {
                                        echo '<td>'.$row['finalConsumption'].'%</td>';
                                    }
                                    
                                    echo '</tr>';
                                    
                                    $count += 1;
                                }
                                ?>
                            </div>
                        </article>
                    </div>
                </div>
            </div>
        </main>
    </body>
</html>