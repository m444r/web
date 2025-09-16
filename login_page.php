<?php
require_once "config.php";
require_once "session.php";

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    if (empty($email)) {
        $error .= '<p class="error">Please enter email.</p>';
    }
    if (empty($password)) {
        $error .= '<p class="error">Please enter your password.</p>';
    }

    if (empty($error)) {
        $sql = "SELECT * FROM users WHERE email = ?";
        $query = $db->prepare($sql);
        $query->bind_param("s", $email);
        $query->execute();
        $result = $query->get_result();
        $row = $result->fetch_assoc();

        if ($row) {
            if (password_verify($password, $row['password'])) {
                $_SESSION["userid"] = $row['id'];
                $_SESSION["user"] = $row;
                $_SESSION["role"] = $row['role'];

                if ($role === $row['role']) {
                    if ($role == 'student') {
                        header("Location: StudentDashboard.php");
                    } elseif ($role == 'teacher') {
                        header("Location: TeacherDashboard.php");
                    } elseif ($role == 'secretary') {
                        header("Location: SecretaryDataInput.html");
                    }
                    exit;
                } else {
                    $error .= '<p class="error">Your selected role does not match your account role.</p>';
                }
            } else {
                $error .= '<p class="error">The password is not valid.</p>';
            }
        } else {
            $error .= '<p class="error">No User exists with that email address.</p>';
        }
        $query->close();
    }
    mysqli_close($db);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login Page</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:400,700&display=swap">
  <link rel="stylesheet" href="css/login.css">
</head>
<body>
  <form class="login-container" id="loginForm" method="post" action="">
    <div class="login-icon">
      <img src="icons/account.png" alt="Profile" class="profile-icon">
    </div>

    <div class="roles">
      <label><input type="radio" name="role" value="student" checked> Student</label>
      <label><input type="radio" name="role" value="teacher"> Teacher</label>
      <label><input type="radio" name="role" value="secretary"> Secretary</label>
    </div>

    <div class="form-group">
      <input type="email" id="email" name="email" class="form-input" placeholder=" " required autocomplete="username">
      <label for="email" class="form-label">Email Address</label>
    </div>

    <div class="form-group">
      <input type="password" id="password" name="password" class="form-input" placeholder=" " required autocomplete="current-password" minlength="4">
      <label for="password" class="form-label">Password</label>
      <button type="button" class="password-toggle" id="passwordToggle" onclick="togglePassword()">
        <img src="icons/hidden.png" alt="Show Password" class="toggle-icon" id="toggleIcon">
      </button>
    </div>

    <button type="submit" name="submit" class="login-btn">LOGIN</button>

    <!-- Εμφάνιση PHP errors -->
    <div class="error-message"><?php echo $error; ?></div>
  </form>

  <script>
    function togglePassword() {
      const passwordInput = document.getElementById('password');
      const toggleIcon = document.getElementById('toggleIcon');
      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.src = 'icons/show.png';
        toggleIcon.alt = 'Hide Password';
      } else {
        passwordInput.type = 'password';
        toggleIcon.src = 'icons/hidden.png';
        toggleIcon.alt = 'Show Password';
      }
    }
  </script>
</body>
</html>
