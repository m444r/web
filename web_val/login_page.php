<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login Page</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:400,700&display=swap">
</head>
  <link rel="stylesheet" href="css/login.css">
<body>
  <form class="login-container" id="loginForm" novalidate>
    <div class="login-icon">
      
      <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M12 14c-4.418 0-8 1.79-8 4v2h16v-2c0-2.21-3.582-4-8-4z"/></svg>
    </div>
    <div class="roles-label">Roles:</div>
    <div class="roles">
      <label><input type="radio" name="role" value="student" checked> Student</label>
      <label><input type="radio" name="role" value="teacher"> Teacher</label>
      <label><input type="radio" name="role" value="secretary"> Secretary</label>
    </div>
    <div class="form-group">
      <input type="email" id="email" name="email" class="form-input" placeholder=" " required autocomplete="username">
      <label for="email" class="form-label">Email Adress</label>
    </div>
    <div class="form-group">
      <input type="password" id="password" name="password" class="form-input" placeholder=" " required autocomplete="current-password" minlength="4">
      <label for="password" class="form-label">Password</label>
    </div>
    <button type="submit" class="login-btn">LOGIN</button>
    <div class="error-message" id="errorMsg"></div>
  </form>
  <script>
    const form = document.getElementById('loginForm');
    const errorMsg = document.getElementById('errorMsg');
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