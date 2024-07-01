<?php
$conn = mysqli_connect(
  '',
  '',
  '',
  '');

session_start();
session_destroy();

header('location: /digiworld');

error_reporting(E_ERROR | E_PARSE);
?>