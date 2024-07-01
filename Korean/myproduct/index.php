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
    header('location: /digiworld/Korean');
} else {
    $user = $_SESSION['DG_username'];
}

// variables
$underlyingAsset = "";
$userCapital = 0;
$datetime = "";

$checkProduct = "SELECT * FROM products_test WHERE productType = '".$productType."'";
if ($checkedProduct = mysqli_query($conn, $checkProduct)) {
    if (mysqli_num_rows($checkedProduct) != 1) {
        header('location: /digiworld');
    } else {
        if ($row = mysqli_fetch_assoc($checkedProduct)) {
            $underlyingAsset = $row['description'];
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
        if ($latest_date < "2023-04-01") {
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
        <title><?php echo $productType; ?></title>
    <link rel="icon" href="/src/appLogo.png" />
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
                    <span>로그인</span>
                    </a>';
                } else {
                    echo '<a href="../logout" class="button">
                    <span>로그아웃</span>
                    </a>';
                }
                ?>
                <a href="/digiworld/myproduct?id=<?php echo $productType; ?>" class="button">
                    <span>English</span>
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
                        <p>경고: 본 컨텐츠는 PC 이용자를 위해 제작되었습니다.</p>
                    </div>
                </div>
                <div class="content">
                    <div class="content-panel">
                        <div class="vertical-tabs">
                            <a>상품개요</a>
                            <a>조회조건</a>
                            <a>상세내역</a>
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
                                    <a>나의 누적실현이익</a>
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
                                    <a>나의 평균월이익</a>
                                </div>
                            </article>
                        </div>
                        
                        <div style="display: <?php echo $display; ?>">
                          <div><small>※ 미관상 약간의 데이터 왜곡이 있을 수 있습니다</small></div>
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
                                    <h3>조회조건</h3>
                                </div>
                            </div>
                            <div class="card-body">
                                <label>조회시작시점</label><br/>
                                <input type="date" name="beginning_date" value="<?php if(isset($_POST['beginning_date'])) { echo $_POST['beginning_date']; } ?>" /><br/>
                                <label>조회종료시점</label><br/>
                                <input type="date" name="ending_date" value="<?php if(isset($_POST['ending_date'])) { echo $_POST['ending_date']; } ?>" /><br/>
                                <label>조회상품</label><br/>
                                <input type="text" placeholder="BTC" name="ticker" value="<?php if(isset($_POST['ticker'])) { echo $_POST['ticker']; } ?>" />
                            </div>
                            <div class="card-footer">
                                <input type="submit" id="filter_btn" style="display: none;" name="filter_btn" />
                                <label for="filter_btn" style="cursor: pointer;">
                                    <a>조회하기</a>
                                </label>
                            </div>
                            </form>
                        </article>
                        <article class="card" id="results">
                            <div class="card-header">
                                <div>
                                    <h3>상세가짜내역</h3>
                                </div>
                                <div>
                                    <h5>정보 악용을 우려하여 상세 가짜 데이터를 내포합니다</h5>
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
                                
                                <th colspan="2" scope="colgroup">시장정보</th>
                                <th colspan="2" scope="colgroup">운용정보</th>
                              </tr>
                                <tr>
                                  <th scope="col" class="sticky1">거래일시</th>
                                  <th scope="col">대상</th>
                                  <th scope="col">매수/매도</th>
                                  <th scope="col">시장가</th>
                                  <th scope="col">비중</th>
                                </tr>';
                                
                                $count = 0;
                                foreach ($array_to_print as $key=>$row) {
                                    if ($count == count($array_to_print)-1) {
                                        continue;
                                    }
                                    
                                    echo '<td class="sticky1">'.$row['datetime'].'</td>';
                                    echo '<td>'.$row['underlyingAsset'].'</td>';
                                    
                                    if ($row['action'] == 'buy') {
                                        $action = '매수';
                                        $actionColor = '#fa4659';
                                    } else {
                                        $action = '매도';
                                        $actionColor = '#22267b';
                                    }
                                
                                    echo '<td style="color: '.$actionColor.';">'.$action.'</td>';
                                    
                                    if ($row['barePrice'] == 0) {
                                        echo '<td>'.$row['totalPrice'].'KRW</td>';
                                    } else {
                                        echo '<td>'.$row['barePrice'].'KRW</td>';
                                    }
                                    
                                    if ($action == "매수") {
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