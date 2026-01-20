<?php
session_start();
if(isset($_SESSION['user_id'])){
    header('Location: dashboard.php'); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - BlogPublish</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
      min-height: 100vh;
      display: flex;
      background: #f5f7fa;
    }
    .login-container {
      display: flex;
      width: 100%;
      min-height: 100vh;
    }
    .login-left {
      flex: 1;
      background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 60px;
      color: white;
      position: relative;
      overflow: hidden;
    }
    .login-left::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(233, 69, 96, 0.1) 0%, transparent 50%);
      animation: pulse 15s ease-in-out infinite;
    }
    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.1); }
    }
    .login-left-content {
      position: relative;
      z-index: 1;
      text-align: center;
      max-width: 400px;
    }
    .login-logo {
      font-size: 2.5rem;
      font-weight: 800;
      margin-bottom: 24px;
    }
    .login-logo span {
      color: #e94560;
    }
    .login-left h1 {
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 16px;
      line-height: 1.3;
    }
    .login-left p {
      font-size: 1rem;
      opacity: 0.8;
      line-height: 1.6;
    }
    .features-list {
      margin-top: 40px;
      text-align: left;
    }
    .feature-item {
      display: flex;
      align-items: center;
      gap: 16px;
      margin-bottom: 20px;
    }
    .feature-icon {
      width: 48px;
      height: 48px;
      background: rgba(233, 69, 96, 0.2);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #e94560;
    }
    .feature-text h4 {
      font-size: 0.95rem;
      margin-bottom: 4px;
    }
    .feature-text p {
      font-size: 0.85rem;
      opacity: 0.7;
    }
    .login-right {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 60px;
      background: white;
    }
    .login-form-container {
      width: 100%;
      max-width: 400px;
    }
    .login-form-container h2 {
      font-size: 1.75rem;
      font-weight: 700;
      color: #1f2937;
      margin-bottom: 8px;
    }
    .login-form-container p {
      color: #6b7280;
      margin-bottom: 32px;
    }
    .form-group {
      margin-bottom: 20px;
    }
    .form-group label {
      display: block;
      font-size: 0.9rem;
      font-weight: 600;
      color: #1f2937;
      margin-bottom: 8px;
    }
    .form-input {
      width: 100%;
      padding: 14px 16px;
      border: 2px solid #e5e7eb;
      border-radius: 10px;
      font-size: 0.95rem;
      transition: all 0.2s ease;
      font-family: inherit;
    }
    .form-input:focus {
      outline: none;
      border-color: #1a1a2e;
      box-shadow: 0 0 0 4px rgba(26, 26, 46, 0.1);
    }
    .form-input::placeholder {
      color: #9ca3af;
    }
    .input-icon-wrapper {
      position: relative;
    }
    .input-icon-wrapper i {
      position: absolute;
      left: 16px;
      top: 50%;
      transform: translateY(-50%);
      color: #9ca3af;
    }
    .input-icon-wrapper .form-input {
      padding-left: 46px;
    }
    .remember-forgot {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 24px;
    }
    .remember-me {
      display: flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
    }
    .remember-me input {
      width: 18px;
      height: 18px;
      accent-color: #1a1a2e;
    }
    .remember-me span {
      font-size: 0.9rem;
      color: #6b7280;
    }
    .forgot-link {
      font-size: 0.9rem;
      color: #e94560;
      text-decoration: none;
      font-weight: 500;
    }
    .forgot-link:hover {
      text-decoration: underline;
    }
    .login-btn {
      width: 100%;
      padding: 14px;
      background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
      color: white;
      border: none;
      border-radius: 10px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s ease;
      font-family: inherit;
    }
    .login-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(26, 26, 46, 0.3);
    }
    .divider {
      display: flex;
      align-items: center;
      margin: 28px 0;
    }
    .divider::before, .divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background: #e5e7eb;
    }
    .divider span {
      padding: 0 16px;
      color: #9ca3af;
      font-size: 0.85rem;
    }
    .social-login {
      display: flex;
      gap: 12px;
    }
    .social-btn {
      flex: 1;
      padding: 12px;
      border: 2px solid #e5e7eb;
      border-radius: 10px;
      background: white;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      font-size: 0.9rem;
      font-weight: 500;
      color: #1f2937;
      transition: all 0.2s ease;
    }
    .social-btn:hover {
      border-color: #1a1a2e;
      background: #f9fafb;
    }
    .signup-link {
      text-align: center;
      margin-top: 28px;
      color: #6b7280;
      font-size: 0.95rem;
    }
    .signup-link a {
      color: #e94560;
      text-decoration: none;
      font-weight: 600;
    }
    .signup-link a:hover {
      text-decoration: underline;
    }
    @media (max-width: 900px) {
      .login-left {
        display: none;
      }
      .login-right {
        padding: 40px 24px;
      }
    }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="login-left">
      <div class="login-left-content">
        <div class="login-logo">Blog<span>Publish</span></div>
        <h1>Welcome to the Best Blogging Platform</h1>
        <p>Create, collaborate, and share your stories with the world. Join thousands of writers today.</p>
        
        <div class="features-list">
          <div class="feature-item">
            <div class="feature-icon"><i class="fas fa-pen-fancy"></i></div>
            <div class="feature-text">
              <h4>Easy Writing</h4>
              <p>Intuitive editor for seamless content creation</p>
            </div>
          </div>
          <div class="feature-item">
            <div class="feature-icon"><i class="fas fa-users"></i></div>
            <div class="feature-text">
              <h4>Collaboration</h4>
              <p>Work together with co-authors in real-time</p>
            </div>
          </div>
          <div class="feature-item">
            <div class="feature-icon"><i class="fas fa-trophy"></i></div>
            <div class="feature-text">
              <h4>Earn Points</h4>
              <p>Get rewarded for your engagement</p>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="login-right">
      <div class="login-form-container">
        <h2>Welcome back! 👋</h2>
        <p>Enter your credentials to access your account</p>
        
        <form action="login_process.php" method="POST">
          <div class="form-group">
            <label>Email Address</label>
            <div class="input-icon-wrapper">
              <i class="fas fa-envelope"></i>
              <input type="email" name="email" class="form-input" placeholder="you@example.com" required>
            </div>
          </div>
          
          <div class="form-group">
            <label>Password</label>
            <div class="input-icon-wrapper">
              <i class="fas fa-lock"></i>
              <input type="password" name="password" class="form-input" placeholder="Enter your password" required>
            </div>
          </div>
          
          <div class="remember-forgot">
            <label class="remember-me">
              <input type="checkbox">
              <span>Remember me</span>
            </label>
            <a href="#" class="forgot-link">Forgot password?</a>
          </div>
          
          <button type="submit" class="login-btn">Sign In</button>
        </form>
        
        <div class="divider"><span>or continue with</span></div>
        
        <div class="social-login">
          <button type="button" class="social-btn">
            <i class="fab fa-google"></i> Google
          </button>
          <button type="button" class="social-btn">
            <i class="fab fa-github"></i> GitHub
          </button>
        </div>
        
        <p class="signup-link">
          Don't have an account? <a href="register.php">Sign up for free</a>
        </p>
      </div>
    </div>
  </div>
</body>
</html>