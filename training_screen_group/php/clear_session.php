<?php
// clear_session.php
session_start();

// ワークアウトのトレーニングリストをクリア
unset($_SESSION['workout_trainings']);

// training_select.phpにリダイレクト
header('Location: training_select.php');
exit;
?>