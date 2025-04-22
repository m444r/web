<?php
session_start();

// Ελέγχουμε αν υπάρχει το session και αν ο χρήστης είναι φοιτητής
if (!isset($_SESSION['userid']) || $_SESSION['role'] != 'student') {
    // Αν δεν είναι φοιτητής ή δεν είναι συνδεδεμένος, ανακατευθύνουμε στην κεντρική σελίδα ή σελίδα login
    header("Location: register.php");
    exit;
}
?>



<!DOCTYPE html>
<html lang="el">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<head>
    <meta charset="UTF-8">
    <title>Πίνακας Ελέγχου Φοιτητή</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8f8f8;
            margin: 0;
            padding: 0;
            text-align: center;
        }

        h1 {
            margin: 30px 0;
        }

        .container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            text-align: left;
        }

        .card h2 {
            font-size: 20px;
            margin-bottom: 10px;
            color: #003366;
        }

        .card p {
            font-size: 15px;
            color: #333;
        }

        .btn {
            display: inline-block;
            margin-top: 15px;
            padding: 8px 15px;
            background-color: #0057b7;
            color: white;
            text-decoration: none;
            border-radius: 8px;
        }

        .btn:hover {
            background-color: #004099;
        }

        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <h1>Καλωσήρθατε, Φοιτητή/Φοιτήτρια</h1>

    <div class="container">

        <!-- 1. Προβολή Θέματος -->
        <div class="card">
            <h2>1. Προβολή Θέματος</h2>
            <p>Δείτε τις λεπτομέρειες της διπλωματικής σας εργασίας, την τρέχουσα κατάσταση και τα μέλη της τριμελούς επιτροπής (αν υπάρχουν).</p>
            <a class="btn" href="view_topic.php">Προβολή Θέματος</a>
        </div>
<!-- Μικρό κουμπί πάνω δεξιά -->
<div style="position: absolute; top: 20px; right: 20px;">
    <!-- Κουμπί με εικονίδιο ρυθμίσεων -->
    <div style="position: relative;">
        <button onclick="toggleDropdown()" style="
            background: none;
            border: none;
            color: #007BFF;
            font-size: 20px;
            cursor: pointer;
        ">
            <i class="fas fa-cog"></i>
        </button>

        <!-- Dropdown μενού -->
        <div id="settingsDropdown" style="
            display: none;
            position: absolute;
            right: 0;
            margin-top: 5px;
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 1000;
        ">
            <a href="edit_profile.php" style="
                display: block;
                padding: 10px 15px;
                color: #333;
                text-decoration: none;
                font-size: 14px;
            ">Επεξεργασία Προφίλ</a>
            <a href="logout.php" style="
                display: block;
                padding: 10px 15px;
                color: #dc3545;
                text-decoration: none;
                font-size: 14px;
            ">Αποσύνδεση</a>
        </div>
    </div>
</div>

<script>
    function toggleDropdown() {
        const dropdown = document.getElementById("settingsDropdown");
        dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
    }

    // Κλείσιμο dropdown όταν κάνεις κλικ έξω
    document.addEventListener('click', function(event) {
        const dropdown = document.getElementById("settingsDropdown");
        const button = event.target.closest('button');

        if (!event.target.closest('#settingsDropdown') && !button) {
            dropdown.style.display = "none";
        }
    });
</script>





        <!-- 3. Διαχείριση Διπλωματικής Εργασίας -->
        <div class="card">
            <h2>2. Διαχείριση Διπλωματικής Εργασίας</h2>
            <p>Ανάλογα με την κατάσταση της διπλωματικής σας, μπορείτε να κάνετε τις απαραίτητες ενέργειες .</p> 
            <a class="btn" href="manage_thesis.php">Διαχείριση Διπλωματικής</a>
        </div>

    </div>

</body>
</html>
