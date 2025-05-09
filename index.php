<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Employee Leave Management System</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary: #3498db;
      --primary-dark: #2980b9;
      --secondary: #2c3e50;
      --accent: #f39c12;
      --light: #ecf0f1;
      --dark: #1a252f;
      --success: #2ecc71;
      --danger: #e74c3c;
      --gray: #95a5a6;
      --text: #333;
      --text-light: #7f8c8d;
      --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      --transition: all 0.3s ease;
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Poppins', sans-serif;
      line-height: 1.6;
      color: var(--text);
      background-color: #fff;
      overflow-x: hidden;
    }
    
    /* Header & Navigation */
    .header {
      background-color: #fff;
      position: fixed;
      width: 100%;
      z-index: 1000;
      box-shadow: var(--shadow);
    }
    
    .navbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1rem 5%;
      max-width: 1400px;
      margin: 0 auto;
    }
    
    .logo {
      display: flex;
      align-items: center;
      text-decoration: none;
    }
    
    .logo-icon {
      color: var(--primary);
      font-size: 1.8rem;
      margin-right: 0.5rem;
    }
    
    .logo-text {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--secondary);
    }
    
    .logo-text span {
      color: var(--primary);
    }
    
    .nav-links {
      display: flex;
      list-style: none;
      gap: 2rem;
    }
    
    .nav-links a {
      color: var(--secondary);
      text-decoration: none;
      font-weight: 500;
      position: relative;
      padding-bottom: 0.2rem;
      transition: var(--transition);
    }
    
    .nav-links a:hover {
      color: var(--primary);
    }
    
    .nav-links a::after {
      content: '';
      position: absolute;
      width: 0;
      height: 2px;
      bottom: 0;
      left: 0;
      background-color: var(--primary);
      transition: var(--transition);
    }
    
    .nav-links a:hover::after {
      width: 100%;
    }
    
    .login-buttons {
      display: flex;
      gap: 1rem;
    }
    
    .btn {
      display: inline-block;
      padding: 0.6rem 1.5rem;
      border-radius: 50px;
      font-weight: 500;
      text-decoration: none;
      cursor: pointer;
      transition: var(--transition);
      border: none;
      outline: none;
      text-align: center;
    }
    
    .btn-primary {
      background-color: var(--primary);
      color: white;
    }
    
    .btn-primary:hover {
      background-color: var(--primary-dark);
      transform: translateY(-3px);
      box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
    }
    
    .btn-outline {
      background-color: transparent;
      border: 2px solid var(--primary);
      color: var(--primary);
    }
    
    .btn-outline:hover {
      background-color: var(--primary);
      color: white;
      transform: translateY(-3px);
      box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
    }
    
    .menu-toggle {
      display: none;
      flex-direction: column;
      cursor: pointer;
    }
    
    .bar {
      background-color: var(--secondary);
      height: 3px;
      width: 28px;
      margin: 3px 0;
      border-radius: 10px;
      transition: var(--transition);
    }
    
    /* Hero Section */
    .hero {
      padding-top: 5rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      min-height: 85vh;
      max-width: 1400px;
      margin: 0 auto;
      padding: 0 5%;
      padding-top: 6rem;
    }
    
    .hero-content {
      flex: 1;
      max-width: 600px;
    }
    
    .hero h1 {
      font-size: 3.2rem;
      font-weight: 700;
      margin-bottom: 1.5rem;
      line-height: 1.2;
      color: var(--secondary);
    }
    
    .hero h1 span {
      color: var(--primary);
    }
    
    .hero p {
      font-size: 1.1rem;
      color: var(--text-light);
      margin-bottom: 2rem;
    }
    
    .hero-btns {
      display: flex;
      gap: 1rem;
      margin-bottom: 3rem;
    }
    
    .hero-features {
      display: flex;
      gap: 2rem;
    }
    
    .feature {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    .feature i {
      color: var(--success);
      font-size: 1.2rem;
    }
    
    .hero-image {
      flex: 1;
      display: flex;
      justify-content: flex-end;
      position: relative;
    }
    
    .hero-img {
      max-width: 90%;
      border-radius: 20px;
      box-shadow: var(--shadow);
    }
    
    /* Features Section */
    .section {
      padding: 5rem 5%;
      max-width: 1400px;
      margin: 0 auto;
    }
    
    .section-title {
      text-align: center;
      margin-bottom: 4rem;
    }
    
    .section-title h2 {
      font-size: 2.5rem;
      font-weight: 700;
      color: var(--secondary);
      margin-bottom: 1rem;
    }
    
    .section-title p {
      font-size: 1.1rem;
      color: var(--text-light);
      max-width: 700px;
      margin: 0 auto;
    }
    
    .features {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 2rem;
    }
    
    .feature-card {
      background-color: white;
      border-radius: 20px;
      padding: 2rem;
      box-shadow: var(--shadow);
      transition: var(--transition);
    }
    
    .feature-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
    }
    
    .feature-icon {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      background-color: rgba(52, 152, 219, 0.1);
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 1.5rem;
    }
    
    .feature-icon i {
      color: var(--primary);
      font-size: 1.8rem;
    }
    
    .feature-card h3 {
      font-size: 1.3rem;
      font-weight: 600;
      margin-bottom: 1rem;
      color: var(--secondary);
    }
    
    .feature-card p {
      color: var(--text-light);
      margin-bottom: 1rem;
    }
    
    /* About Us Section */
    .about-us {
      background-color: rgba(52, 152, 219, 0.05);
      padding: 5rem 5%;
    }
    
    .about-content {
      max-width: 1400px;
      margin: 0 auto;
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 4rem;
      align-items: center;
    }
    
    .about-text h2 {
      font-size: 2.5rem;
      font-weight: 700;
      color: var(--secondary);
      margin-bottom: 1.5rem;
    }
    
    .about-text p {
      font-size: 1.1rem;
      color: var(--text-light);
      margin-bottom: 1.5rem;
      line-height: 1.8;
    }
    
    .about-features {
      margin-top: 2rem;
    }
    
    .about-feature {
      display: flex;
      align-items: flex-start;
      gap: 1rem;
      margin-bottom: 1.5rem;
    }
    
    .about-feature-icon {
      min-width: 40px;
      height: 40px;
      border-radius: 50%;
      background-color: rgba(52, 152, 219, 0.1);
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .about-feature-icon i {
      color: var(--primary);
      font-size: 1.2rem;
    }
    
    .about-feature-text h4 {
      font-size: 1.1rem;
      font-weight: 600;
      margin-bottom: 0.5rem;
      color: var(--secondary);
    }
    
    .about-feature-text p {
      font-size: 0.95rem;
      margin-bottom: 0;
    }
    
    .about-image img {
      max-width: 100%;
      border-radius: 20px;
      box-shadow: var(--shadow);
    }
    
    /* Footer */
    .footer {
      background-color: var(--secondary);
      color: white;
      padding: 4rem 5% 2rem;
    }
    
    .footer-content {
      max-width: 1400px;
      margin: 0 auto;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 3rem;
    }
    
    .footer-logo {
      display: flex;
      align-items: center;
      margin-bottom: 1.5rem;
    }
    
    .footer-logo-icon {
      color: var(--primary);
      font-size: 1.8rem;
      margin-right: 0.5rem;
    }
    
    .footer-logo-text {
      font-size: 1.5rem;
      font-weight: 700;
      color: white;
    }
    
    .footer-logo-text span {
      color: var(--primary);
    }
    
    .footer-info p {
      margin-bottom: 1.5rem;
      color: rgba(255, 255, 255, 0.7);
    }
    
    .footer-title {
      font-size: 1.2rem;
      font-weight: 600;
      margin-bottom: 1.5rem;
      color: white;
    }
    
    .footer-links {
      list-style: none;
    }
    
    .footer-links li {
      margin-bottom: 1rem;
    }
    
    .footer-links a {
      color: rgba(255, 255, 255, 0.7);
      text-decoration: none;
      transition: var(--transition);
    }
    
    .footer-links a:hover {
      color: var(--primary);
    }
    
    .footer-links a i {
      margin-right: 0.5rem;
    }
    
    .footer-bottom {
      max-width: 1400px;
      margin: 0 auto;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      padding-top: 2rem;
      margin-top: 3rem;
      text-align: center;
      color: rgba(255, 255, 255, 0.7);
      font-size: 0.9rem;
    }
    
    /* Responsive Styles */
    @media (max-width: 1024px) {
      .hero {
        flex-direction: column;
        text-align: center;
        padding-top: 8rem;
      }
      
      .hero-content {
        max-width: 100%;
        margin-bottom: 3rem;
      }
      
      .hero-btns {
        justify-content: center;
      }
      
      .hero-features {
        justify-content: center;
      }
      
      .hero-image {
        justify-content: center;
      }
      
      .about-content {
        grid-template-columns: 1fr;
        gap: 3rem;
      }
      
      .about-image {
        order: -1;
        text-align: center;
      }
      
      .about-image img {
        max-width: 80%;
      }
    }
    
    @media (max-width: 768px) {
      .navbar {
        position: relative;
      }
      
      .menu-toggle {
        display: flex;
      }
      
      .nav-links {
        position: absolute;
        top: 100%;
        left: 0;
        width: 100%;
        flex-direction: column;
        background-color: white;
        padding: 2rem;
        gap: 1.5rem;
        box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        opacity: 0;
        pointer-events: none;
        transform: translateY(-20px);
        transition: var(--transition);
      }
      
      .nav-links.active {
        opacity: 1;
        pointer-events: all;
        transform: translateY(0);
      }
      
      .login-buttons {
        flex-direction: column;
        width: 100%;
      }
      
      .login-buttons .btn {
        width: 100%;
      }
      
      .hero h1 {
        font-size: 2.5rem;
      }
      
      .hero-features {
        flex-direction: column;
        align-items: center;
        gap: 1rem;
      }
      
      .feature-card {
        text-align: center;
      }
      
      .feature-icon {
        margin-left: auto;
        margin-right: auto;
      }
      
      .about-image img {
        max-width: 100%;
      }
    }
  </style>
</head>
<body>
  <!-- Header & Navigation -->
  <header class="header">
    <nav class="navbar">
      <a href="index.php" class="logo">
        <div class="logo-icon"><i class="fas fa-calendar-check"></i></div>
        <div class="logo-text">ELMS <span>Portal</span></div>
      </a>
      
      <div class="menu-toggle" id="mobile-menu">
        <span class="bar"></span>
        <span class="bar"></span>
        <span class="bar"></span>
      </div>
      
      <ul class="nav-links" id="nav-links">
        <li><a href="index.php">Home</a></li>
        <li><a href="#features">Features</a></li>
        <li><a href="#about">About Us</a></li>
        <li><a href="#contact">Contact</a></li>
      </ul>
      
      <div class="login-buttons">
        <a href="employee_login.php" class="btn btn-outline">Employee Login</a>
        <a href="admin_login.php" class="btn btn-primary">Admin Login</a>
      </div>
    </nav>
  </header>
  
  <!-- Hero Section -->
  <section class="hero">
    <div class="hero-content">
      <h1>Simplify Your <span>Leave Management</span> Process</h1>
      <p>An efficient and user-friendly employee leave management system designed to streamline leave requests, approvals, and tracking for organizations of all sizes.</p>
      
      <div class="hero-btns">
        <a href="employee_login.php" class="btn btn-primary">Get Started</a>
        <a href="#features" class="btn btn-outline">Learn More</a>
      </div>
      
      <div class="hero-features">
        <div class="feature">
          <i class="fas fa-check-circle"></i>
          <span>Easy Application</span>
        </div>
        <div class="feature">
          <i class="fas fa-check-circle"></i>
          <span>Real-Time Tracking</span>
        </div>
      </div>
    </div>
    
    <div class="hero-image">
      <img src="https://sfdhr.org/sites/default/files/images/Employees/employee-leaves.jpg" alt="ELMS Dashboard" class="hero-img">
    </div>
  </section>
  
  <!-- Features Section -->
  <section id="features" class="section">
    <div class="section-title">
      <h2>Powerful Features</h2>
      <p>Our employee leave management system comes with everything you need to streamline your organization's leave processes.</p>
    </div>
    
    <div class="features">
      <div class="feature-card">
        <div class="feature-icon">
          <i class="fas fa-file-alt"></i>
        </div>
        <h3>Leave Application</h3>
        <p>Simple and intuitive interface for employees to submit leave requests with just a few clicks.</p>
      </div>
      
      <div class="feature-card">
        <div class="feature-icon">
          <i class="fas fa-check"></i>
        </div>
        <h3>Instant Approvals</h3>
        <p>Managers can review and respond to leave requests quickly, improving efficiency.</p>
      </div>
      
      <div class="feature-card">
        <div class="feature-icon">
          <i class="fas fa-chart-pie"></i>
        </div>
        <h3>Leave Tracking</h3>
        <p>Keep track of all leave balances and usage with detailed reports and analytics.</p>
      </div>
      
      <div class="feature-card">
        <div class="feature-icon">
          <i class="fas fa-bell"></i>
        </div>
        <h3>Notifications</h3>
        <p>Automated notifications keep everyone informed about leave requests and status updates.</p>
      </div>
    </div>
  </section>
  
  <!-- About Us Section -->
  <section id="about" class="about-us">
    <div class="about-content">
      <div class="about-text">
        <h2>About Us</h2>
        <p>The Employee Leave Management System (ELMS) was developed as part of the curriculum for the 6th semester of the Bachelor of Computer Applications (BCA) program.</p>
        <p>Our goal was to create a user-friendly and efficient software system that automates the process of managing employee leave within an organization, eliminating manual paperwork and reducing errors while improving transparency.</p>
        
        <div class="about-features">
          <div class="about-feature">
            <div class="about-feature-icon">
              <i class="fas fa-desktop"></i>
            </div>
            <div class="about-feature-text">
              <h4>Easy Implementation</h4>
              <p>Simple setup process with minimal  requirements for organizations of any size.</p>
            </div>
          </div>
         
          
          
        </div>
      </div>
      
      <div class="about-image">
        <img src="https://www.cflowapps.com/wp-content/uploads/2018/07/leave-management-process.png" alt="Leave Management Process">
      </div>
    </div>
  </section>
  
  <!-- Footer -->
  <footer class="footer">
    <div class="footer-content">
      <div class="footer-info">
        <div class="footer-logo">
          <div class="footer-logo-icon"><i class="fas fa-calendar-check"></i></div>
          <div class="footer-logo-text">ELMS <span>Portal</span></div>
        </div>
        <p>Streamline your organization's leave management process with our easy-to-use, comprehensive system designed for modern workplaces.</p>
      </div>
      
      <div class="footer-links-container">
        <h3 class="footer-title">Quick Links</h3>
        <ul class="footer-links">
          <li><a href="index.php"><i class="fas fa-chevron-right"></i> Home</a></li>
          <li><a href="#features"><i class="fas fa-chevron-right"></i> Features</a></li>
          <li><a href="#about"><i class="fas fa-chevron-right"></i> About Us</a></li>
          <li><a href="employee_login.php"><i class="fas fa-chevron-right"></i> Employee Login</a></li>
          <li><a href="admin_login.php"><i class="fas fa-chevron-right"></i> Admin Login</a></li>
        </ul>
      </div>
      
      <div class="footer-links-container">
        <h3 class="footer-title">Help</h3>
        <ul class="footer-links">
          <li><a href="employee_reset_password.php"><i class="fas fa-chevron-right"></i> Reset Password</a></li>

      </div>
      
      <div class="footer-links-container" id="contact">
        <h3 class="footer-title">Contact Us</h3>
        <ul class="footer-links">
          <li><a href="mailto:info@elms.com"><i class="fas fa-envelope"></i>tripurarinath98@gmail.com</a></li>
          <li><a href="tel:+1234567890"><i class="fas fa-phone"></i>+91 9801464782</a></li>
          <li><a href="#"><i class="fas fa-map-marker-alt"></i> Patna, Bihar,India</a></li>
        </ul>
      </div>
    </div>
    
    <div class="footer-bottom">
      <p>&copy; <?php echo date('Y'); ?> Employee Leave Management System. All Rights Reserved. | Developed by Tripurari Nath</p>
    </div>
  </footer>
  
  <script>
    // Mobile Menu Toggle
    const mobileMenu = document.getElementById('mobile-menu');
    const navLinks = document.getElementById('nav-links');
    
    mobileMenu.addEventListener('click', function() {
      navLinks.classList.toggle('active');
      
      // Animate the menu icon
      const bars = document.querySelectorAll('.bar');
      bars.forEach(bar => bar.classList.toggle('active'));
      
      if (navLinks.classList.contains('active')) {
        bars[0].style.transform = 'rotate(-45deg) translate(-5px, 6px)';
        bars[1].style.opacity = '0';
        bars[2].style.transform = 'rotate(45deg) translate(-5px, -6px)';
      } else {
        bars[0].style.transform = 'rotate(0) translate(0)';
        bars[1].style.opacity = '1';
        bars[2].style.transform = 'rotate(0) translate(0)';
      }
    });
    
    // Smooth Scroll for Anchor Links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        e.preventDefault();
        
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
          window.scrollTo({
            top: target.offsetTop - 80,
            behavior: 'smooth'
          });
          
          // Close mobile menu if open
          if (navLinks.classList.contains('active')) {
            navLinks.classList.remove('active');
            
            const bars = document.querySelectorAll('.bar');
            bars[0].style.transform = 'rotate(0) translate(0)';
            bars[1].style.opacity = '1';
            bars[2].style.transform = 'rotate(0) translate(0)';
          }
        }
      });
    });
  </script>
</body>
</html>
