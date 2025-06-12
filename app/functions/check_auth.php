<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: /participantesici/public_html/login");
  exit;
}
