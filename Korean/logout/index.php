<?php
$conn = mysqli_connect(
  '',
  '',
  '',
  '');

session_start();
session_destroy();

header('location: /digiworld/Korean');

error_reporting(E_ERROR | E_PARSE);
?>