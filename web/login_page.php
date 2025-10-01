<?php
require_once "config.php";
require_once "session.php";

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {

    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    // validate if email is empty
    if (empty($email)) {
        $error = 'Please enter email.';
    }

    // validate if password is empty
    if (empty($password)) {
        $error = 'Please enter your password.';
    }

    if (empty($error)) {
        // Παίρνουμε τον χρήστη από τη βάση
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        if ($row) {
            if (password_verify($password, $row['password'])) {
                $_SESSION["userid"] = $row['id'];
                $_SESSION["user"] = $row;
                $_SESSION["role"] = $row['role'];
                
                if ($role == $row['role']) {
                    $_SESSION["userid"] = $row['id'];  // Store user ID in session

                    // Redirect based on the user's role
                    if ($role == 'student') {
                        header("Location: StudentPage/StudentDashboard.php"); // Redirect to student's page
                        exit;
                    } elseif ($role == 'teacher') {
                        header("Location: TeacherPage/TeacherDashboard.php"); // Redirect to teacher's page
                        exit;
                    } elseif ($role == 'secretary') {
                        header("Location: SecretaryPage/SecretaryDashboard.php"); // Redirect to secretary's page
                        exit;
                    }
                    else {
                        $error = 'Your selected role does not match your account role.';
                    }

                } else {
                    $error = 'Selected role (' . $role . ') does not match account role (' . $row['role'] . ').';
                } 
     
            } else {
                $error = 'The password is not valid.';
            }
            
            
        } else {
            $error = 'No User exist with that email address.';
        }
    }
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
  <form class="login-container" id="loginForm" method="POST" novalidate>
    <div class="login-icon">
      <img src="icons/account.png" alt="Profile" class="profile-icon">
    </div>
    <div class="roles">
      <label><input type="radio" name="role" value="student" <?php echo (!isset($_POST['role']) || $_POST['role'] == 'student') ? 'checked' : ''; ?>> Student</label>
      <label><input type="radio" name="role" value="teacher" <?php echo (isset($_POST['role']) && $_POST['role'] == 'teacher') ? 'checked' : ''; ?>> Teacher</label>
      <label><input type="radio" name="role" value="secretary" <?php echo (isset($_POST['role']) && $_POST['role'] == 'secretary') ? 'checked' : ''; ?>> Secretary</label>
    </div>
    <div class="form-group">
      <input type="email" id="email" name="email" class="form-input" placeholder=" " required autocomplete="username" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
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
    <?php if (!empty($error)): ?>
      <div class="error-message" id="errorMsg"><?php echo htmlspecialchars($error); ?></div>
    <?php else: ?>
      <div class="error-message" id="errorMsg"></div>
    <?php endif; ?>
  </form>
  <script>
    const form = document.getElementById('loginForm');
    const errorMsg = document.getElementById('errorMsg');
    
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
    
    form.addEventListener('submit', function(e) {
      errorMsg.textContent = '';
      if (!form.checkValidity()) {
        e.preventDefault();
        if (!form.email.value) {
          errorMsg.textContent = 'Please enter your email address.';
        } else if (!form.password.value) {
          errorMsg.textContent = 'Please enter your password.';
        } else if (!form.email.validity.valid) {
          errorMsg.textContent = 'Please enter a valid email address.';
        } else {
          errorMsg.textContent = 'Please fill in all fields correctly.';
        }
      }
    });
  </script>
</body>
</html>
