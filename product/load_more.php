<?php
session_start();
$conn = mysqli_connect(
    '',
    '',
    '',
    '');
  
  $response = array();
  
  $last_datetime = "";
  
  $getPerformance = $_POST['sql'];
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
    
            array_push($response, array('datetime'=>$mysqldate, 'underlyingAsset'=>$row['underlyingAsset'], 'action'=>$row['action'], 'totalPrice'=>$row['totalPrice'], 'finalConsumption'=>$finalConsumption, 'finalTpl'=>$finalTpl, 'barePrice'=>$row['barePrice']));
            
            $count++;
            
            if ($count == mysqli_num_rows($gotPerformance)) {
                $last_datetime = $row['datetime'];
            }
      }
  }
  
  $dummy = $_POST['dummy_sql'];
  $dummy = str_replace("last_datetime", $last_datetime, $dummy);
  array_push($response, $dummy);
  
  echo json_encode($response);
?>