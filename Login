<?php
session_start();

function require_role($role) {
    if (!isset($_SESSION['user'])) {
        header("Location: login.php");
        exit;
    }
    if ($_SESSION['user']['role'] !== $role) {
        echo "Try again";
        exit;
    }
}
