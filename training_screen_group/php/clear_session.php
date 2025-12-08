<?php
session_start();
unset($_SESSION['workout_trainings']);
header('Location: training_list.php');
exit;
?>