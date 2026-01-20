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
  <title>Register - BlogPublish</title>
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
    .register-container {
      display: flex;
      width: 100%;
      min-height: 100vh;
    }
    .register-left {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 60px;
      background: white;
    }
    .register-form-container {
      width: 100%;
      max-width: 480px;
    }
    .back-link {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: #6b7280;
      text-decoration: none;
      font-size: 0.9rem;
      margin-bottom: 32px;
      transition: color 0.2s;
    }
    .back-link:hover {
      color: #1a1a2e;
    }
    .register-form-container h2 {
      font-size: 1.75rem;
      font-weight: 700;
      color: #1f2937;
      margin-bottom: 8px;
    }
    .register-form-container > p {
      color: #6b7280;
      margin-bottom: 32px;
    }
    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
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
    .form-input, .form-select {
      width: 100%;
      padding: 14px 16px;
      border: 2px solid #e5e7eb;
      border-radius: 10px;
      font-size: 0.95rem;
      transition: all 0.2s ease;
      font-family: inherit;
      background: white;
    }
    .form-input:focus, .form-select:focus {
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
    .role-cards {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 12px;
      margin-bottom: 24px;
    }
    .role-card {
      padding: 16px;
      border: 2px solid #e5e7eb;
      border-radius: 12px;
      cursor: pointer;
      transition: all 0.2s ease;
      text-align: center;
    }
    .role-card:hover {
      border-color: #1a1a2e;
    }
    .role-card.selected {
      border-color: #e94560;
      background: #fef2f4;
    }
    .role-card input {
      display: none;
    }
    .role-card i {
      font-size: 1.5rem;
      margin-bottom: 8px;
      color: #6b7280;
    }
    .role-card.selected i {
      color: #e94560;
    }
    .role-card h4 {
      font-size: 0.95rem;
      font-weight: 600;
      color: #1f2937;
      margin-bottom: 4px;
    }
    .role-card p {
      font-size: 0.8rem;
      color: #6b7280;
    }
    .terms-check {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      margin-bottom: 24px;
    }
    .terms-check input {
      width: 20px;
      height: 20px;
      margin-top: 2px;
      accent-color: #1a1a2e;
    }
    .terms-check label {
      font-size: 0.9rem;
      color: #6b7280;
      line-height: 1.5;
    }
    .terms-check a {
      color: #e94560;
      text-decoration: none;
    }
    .terms-check a:hover {
      text-decoration: underline;
    }
    .register-btn {
      width: 100%;
      padding: 14px;
      background: linear-gradient(135deg, #e94560 0%, #ff6b6b 100%);
      color: white;
      border: none;
      border-radius: 10px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s ease;
      font-family: inherit;
    }
    .register-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(233, 69, 96, 0.3);
    }
    .login-link {
      text-align: center;
      margin-top: 28px;
      color: #6b7280;
      font-size: 0.95rem;
    }
    .login-link a {
      color: #e94560;
      text-decoration: none;
      font-weight: 600;
    }
    .login-link a:hover {
      text-decoration: underline;
    }
    .register-right {
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
    .register-right::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(233, 69, 96, 0.15) 0%, transparent 50%);
      animation: pulse 15s ease-in-out infinite;
    }
    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.1); }
    }
    .register-right-content {
      position: relative;
      z-index: 1;
      text-align: center;
      max-width: 400px;
    }
    .register-logo {
      font-size: 2.5rem;
      font-weight: 800;
      margin-bottom: 24px;
    }
    .register-logo span {
      color: #e94560;
    }
    .register-right h1 {
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 16px;
      line-height: 1.3;
    }
    .register-right p {
      font-size: 1rem;
      opacity: 0.8;
      line-height: 1.6;
    }
    .testimonial {
      margin-top: 48px;
      background: rgba(255,255,255,0.1);
      padding: 24px;
      border-radius: 16px;
      text-align: left;
    }
    .testimonial p {
      font-style: italic;
      margin-bottom: 16px;
      font-size: 0.95rem;
    }
    .testimonial-author {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .testimonial-avatar {
      width: 40px;
      height: 40px;
      background: linear-gradient(135deg, #e94560, #ff9a9e);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
    }
    .testimonial-info h4 {
      font-size: 0.9rem;
      margin-bottom: 2px;
    }
    .testimonial-info span {
      font-size: 0.8rem;
      opacity: 0.7;
    }
    @media (max-width: 900px) {
      .register-right {
        display: none;
      }
      .register-left {
        padding: 40px 24px;
      }
      .form-row {
        grid-template-columns: 1fr;
      }
      .role-cards {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <div class="register-container">
    <div class="register-left">
      <div class="register-form-container">
        <a href="index.php" class="back-link">
          <i class="fas fa-arrow-left"></i> Back to login
        </a>
        
        <h2>Create an account ✨</h2>
        <p>Start your blogging journey today</p>
        
        <form action="register_process.php" method="POST">
          <div class="form-row">
            <div class="form-group">
              <label>Full Name</label>
              <div class="input-icon-wrapper">
                <i class="fas fa-user"></i>
                <input type="text" name="fullname" class="form-input" placeholder="John Doe" required>
              </div>
            </div>
            
            <div class="form-group">
              <label>Email Address</label>
              <div class="input-icon-wrapper">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" class="form-input" placeholder="you@example.com" required>
              </div>
            </div>
          </div>
          
          <div class="form-group">
            <label>Password</label>
            <div class="input-icon-wrapper">
              <i class="fas fa-lock"></i>
              <input type="password" name="password" class="form-input" placeholder="Create a strong password" required>
            </div>
          </div>
          
          <div class="form-group">
            <label>Select your role</label>
            <div class="role-cards">
              <label class="role-card selected">
                <input type="radio" name="role" value="reader" checked>
                <i class="fas fa-book-reader"></i>
                <h4>Reader</h4>
                <p>Read & comment on posts</p>
              </label>
              <label class="role-card">
                <input type="radio" name="role" value="author">
                <i class="fas fa-pen-nib"></i>
                <h4>Author</h4>
                <p>Write and publish blogs</p>
              </label>
              <label class="role-card">
                <input type="radio" name="role" value="editor">
                <i class="fas fa-edit"></i>
                <h4>Editor</h4>
                <p>Review & approve content</p>
              </label>
              <label class="role-card">
                <input type="radio" name="role" value="admin">
                <i class="fas fa-shield-alt"></i>
                <h4>Admin</h4>
                <p>Manage entire platform</p>
              </label>
            </div>
          </div>
          
          <div class="terms-check">
            <input type="checkbox" id="terms" required>
            <label for="terms">I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></label>
          </div>
          
          <button type="submit" class="register-btn">Create Account</button>
        </form>
        
        <p class="login-link">
          Already have an account? <a href="index.php">Sign in</a>
        </p>
      </div>
    </div>
    
    <div class="register-right">
      <div class="register-right-content">
        <div class="register-logo">Blog<span>Publish</span></div>
        <h1>Join Our Growing Community</h1>
        <p>Connect with thousands of writers, readers, and creators. Share your unique perspective with the world.</p>
        
        <div class="testimonial">
          <p>"BlogPublish transformed my writing career. The collaboration features and community support are incredible!"</p>
          <div class="testimonial-author">
            <div class="testimonial-avatar">S</div>
            <div class="testimonial-info">
              <h4>Sarah Johnson</h4>
              <span>Featured Author</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <script>
    // Role card selection
    document.querySelectorAll('.role-card').forEach(card => {
      card.addEventListener('click', function() {
        document.querySelectorAll('.role-card').forEach(c => c.classList.remove('selected'));
        this.classList.add('selected');
      });
    });
  </script>
</body>
</html>