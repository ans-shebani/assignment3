<?php
session_start();
session_destroy();
header("Location:../public/main.php");
exit();
?>