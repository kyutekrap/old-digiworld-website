<?php
session_start();
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

if (empty($_GET['id'])) {
    header('location: /digiworld');
} else {
    $productType = $_GET['id'];
}

$underlyingAsset = "";

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

if (isset($_POST['filter_btn'])) {
    $beginning_date = $_POST['beginning_date'];
    $ending_date = $_POST['ending_date'];
    $ticker = $_POST['ticker'];
    
    if ($beginning_date != "") {
        $bd = "AND datetime >= '".$beginning_date."'";
    } else {
        $bd = "";
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
}

$array_to_print = array();

$getPerformance = "SELECT * FROM performance WHERE productType = '".$productType."'".$bd.$ed.$t." UNION ALL SELECT * FROM uppa WHERE productType = '".$productType."'".$bd.$ed.$t." ORDER BY datetime DESC LIMIT 200";
if ($gotPerformance = mysqli_query($conn, $getPerformance)) {
    $count = 0;
    while ($row = mysqli_fetch_assoc($gotPerformance)) {
        
        $phpdate = strtotime($row['datetime']);
        $mysqldate = date('Y-m-d H:i', $phpdate);

        $finalConsumption = number_format((float)$row['consumption'], 1, '.', '');
        
        if ($row['action'] == 1) {
            $finalTpl = 0;
        } else {
            $finalTpl = number_format($row['tradePnl'], 1, '.', '');
        }

        array_push($array_to_print, array('datetime'=>$mysqldate, 'underlyingAsset'=>$row['underlyingAsset'], 'action'=>$row['action'], 'totalPrice'=>$row['totalPrice'], 'finalConsumption'=>$finalConsumption, 'finalTpl'=>$finalTpl, 'barePrice'=>$row['barePrice']));
        
        $count++;
        
        if ($count == mysqli_num_rows($gotPerformance)) {
            $last_datetime = $row['datetime'];
        }
    }
}

$daily_data = array();
$weekly_data = array();
$net_realized_profit = 0;

$getAggregates = "SELECT * FROM uppa_aggregates ORDER BY id ASC";
if ($gotAggregates = mysqli_query($conn, $getAggregates)) {
    $weekly_pnl = 0;
    $push_indicator = 0;
    $index = 0;
    while ($row = mysqli_fetch_assoc($gotAggregates)) {
        
        $net_realized_profit += $row['price'];
        
        if ($row['datetime'] > '2023-05-31') {
            array_push($daily_data, array('date'=>$row['datetime'], 'pnl'=>number_format($row['price'], 1, '.', '')));
        }
        
        $phpdate = strtotime($row['datetime']);
        $dateString = date('Y-m-d', $phpdate);
        $dateTime = new DateTime($dateString);
        $dateTime->setTimezone(new DateTimeZone('Asia/Seoul'));
        $dayOfWeek = $dateTime->format('N');
        if ($index == 0) {
            $latestDate = $dateString;
            $weekly_pnl = $pnl;
        } else {
            if ($dayOfWeek == 7 && $push_indicator == 0) {
                array_push($weekly_data, array('date'=>$latestDate, 'pnl'=>number_format($weekly_pnl, 1, ".", "")));
                $latestDate = $dateString;
                $weekly_pnl = $row['price'];
                $push_indicator = 1;
            } else {
                $weekly_pnl += $row['price'];
                if ($dayOfWeek != 7) {
                    $push_indicator = 0;
                }
            }
        }
        if ($index == mysqli_num_rows($gotAggregates)-1) {
            if ($latest_date != "" && $daily_pnl != "") {
                array_push($weekly_data, array('date'=>$latestDate, 'pnl'=>number_format($weekly_pnl, 1, ".", "")));
            }
        }
        
        $index++;
    }
}

$ave_pnl = 0;
$getPnl = "SELECT SUM(pnl) / COUNT(*) AS ave_pnl FROM uppa_monthly";
if ($gotPnl = mysqli_query($conn, $getPnl)) {
    if ($row = mysqli_fetch_assoc($gotPnl)) {
        $ave_pnl = number_format($row['ave_pnl'], 2, ".", "");
    }
}

$getPerformance = "SELECT * FROM performance WHERE productType = '".$productType."'".$bd.$ed.$t." AND datetime < '".$last_datetime."' UNION ALL SELECT * FROM uppa WHERE productType = '".$productType."'".$bd.$ed.$t." AND datetime < '".$last_datetime."' ORDER BY datetime DESC LIMIT 200";
$dummy = "SELECT * FROM performance WHERE productType = '".$productType."'".$bd.$ed.$t." AND datetime < 'last_datetime' UNION ALL SELECT * FROM uppa WHERE productType = '".$productType."'".$bd.$ed.$t." AND datetime < 'last_datetime' ORDER BY datetime DESC LIMIT 200";
?>
<html>
    <head>
        <link rel="stylesheet" href="styles_v02.css" />
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.6.0/Chart.min.js"></script>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
                <a href="../Korean/product?id=Classic U" class="button">
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
                                        echo '<h3>'.number_format($net_realized_profit, 2, ".", ",").'</h3>';
                                        ?>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <a>Net Realized Profit</a>
                                </div>
                            </article>
                            <article class="card">
                                <div class="card-header">
                                    <div>
                                        <?php
                                        echo '<h3>'.$ave_pnl.'%</h3>';
                                        ?>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <a>Average Monthly ROI</a>
                                </div>
                            </article>
                        </div>
                        <div>
                          <div><small>※ There may be data obstruction for purpose of visualization.</small></div>
                          <br/>
                          <div style="float: right; margin-bottom: 10px;">
                              <a id="daily_btn" class="button active" style="margin-right: 5px;" onclick="daily_data();">Day</a>
                              <a id="weekly_btn" class="button" onclick="weekly_data();">Week</a>
                          </div>
                          <canvas id="lineChartBlueGreen" width="400px" height="300px"></canvas>
                        </div>
                        <script>
                        var results = <?php echo json_encode($daily_data); ?>;
                        
                        let myChart = null;
                        
                        function drawLineChart(div_id, results, yColumn, yLabel, xAxes, firstColour, secondColour, thirdColour, fourthColour) {
                            if (myChart) {
                                myChart.destroy();
                            }
                            
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
                        
                          myChart = new Chart(ctx, {
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
                                  pointBorderWidth: 4,
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
                        
                        function weekly_data() {
                            document.getElementById("weekly_btn").classList.add("active");
                            document.getElementById("daily_btn").classList.remove("active");
                            
                            var results = <?php echo json_encode($weekly_data); ?>;
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
                        function daily_data() {
                            document.getElementById("weekly_btn").classList.remove("active");
                            document.getElementById("daily_btn").classList.add("active");
                            
                            var results = <?php echo json_encode($daily_data); ?>;
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
                                <label>Underlying Ticker</label><br/>
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
                                    <h3>Real Fake Data</h3>
                                </div>
                                <div>
                                    <h5>Table includes data obstruction for prevention of information misuse.</h5>
                                </div>
                            </div>
                            <div class="card-body">
                                <table id="table">
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
                                </tr>
                                <?php
                                $count = 0;
                                foreach ($array_to_print as $key=>$row) {
                                    if ($count == count($array_to_print)-1) {
                                        continue;
                                    }
                                    
                                    echo '<tr>';
                                    
                                    echo '<td class="sticky1">'.$row['datetime'].'</td>';
                                    echo '<td>'.$row['underlyingAsset'].'</td>';
                                    
                                    if ($row['action'] == 1) {
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
                                </table>
                                
                                <div class="load_more button" onclick="load_more(this)">Load More</div>
                                
                            </div>
                        </article>
                    </div>
                </div>
            </div>
        </main>

        <script>
            var sql = <?php echo json_encode($getPerformance); ?>;
            var dummy_sql = <?php echo json_encode($dummy); ?>;

            function load_more(x) {
              x.classList.add('active');
              x.innerHTML = 'Loading..';

              var formData = {
                sql: sql,
                dummy_sql: dummy_sql
              };
        
              $.post({
                url: 'load_more.php',
                data: formData,
                success: function(response) {
                    x.classList.remove('active');
                    x.innerHTML = 'Load More';
                    
                    var data = JSON.parse(response);
                    
                    var table = document.getElementById("table");
                    
                    for (var i = 0; i < data.length-1; i++) {
                      var row = table.insertRow();
                      
                      var cell = row.insertCell();
                      var datetime = JSON.stringify(data[i]['datetime']);
                      datetime = datetime.substring(1, datetime.length - 1);
                      cell.textContent = datetime;
                      cell.classList.add("sticky1");
                      
                      var cell = row.insertCell();
                      var underlyingAsset = JSON.stringify(data[i]['underlyingAsset']);
                      underlyingAsset = underlyingAsset.substring(1, underlyingAsset.length - 1);
                      cell.textContent = underlyingAsset;
                      
                      var cell = row.insertCell();
                      if (data[i]['action'] === "1") {
                          var action = "Buy";
                          var actionColor = '#fa4659';
                      } else {
                          var action = "Sell";
                          var actionColor = '#22267b';
                      }
                      cell.textContent = action;
                      cell.style.color = actionColor;
                      
                      var cell = row.insertCell();
                      if (data[i]['barePrice'] === "0") {
                          var price = JSON.stringify(data[i]['totalPrice']);
                      } else {
                          var price = JSON.stringify(data[i]['barePrice']);
                      }
                      price = price.substring(1, price.length - 1);
                      cell.textContent = price + "KRW";
                      
                      var cell = row.insertCell();
                      if (action === "Buy") {
                          var consumption = "";
                      } else {
                          var consumption = JSON.stringify(data[i]['finalConsumption']);
                          consumption = consumption.substring(1, consumption.length - 1) + "%";
                      }
                      cell.textContent = consumption;
                    }
                    
                    sql = JSON.stringify(data[i]);
                    sql = sql.substring(1, sql.length - 1);
                
                },
                error: function(error) {
                  x.classList.remove('active');
                  x.innerHTML = 'Load More';
                }
              });
                
            }
        </script>
    </body>
</html>