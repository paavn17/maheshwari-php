<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Maheshwari ID Card's — Landing Page</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    body {
      margin: 0;
      font-family: ui-sans-serif, system-ui, Arial, sans-serif;
      background: linear-gradient(135deg, #FFFAF0 0%, #FFF 100%);
    }

    /* Add more top padding to container for space above navbar */
    .pt-6 {
      padding-top: 4rem; /* Increased gap from top of screen */
      padding-left: 1rem;
      padding-right: 1rem;
    }
    @media (min-width: 768px) {
      .pt-6 {
        padding-left: 2rem;
        padding-right: 2rem;
      }
    }
    
    /* Navbar */
    nav {
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(8px);
      border: 1px solid #fed7aa;
      border-radius: 1rem;
      padding: 1rem 2rem;
      max-width: 80rem;
      margin: auto;
    }
    nav img {
      max-height: 80px; /* Increased logo height from 50 to 80 */
      width: auto;
    }
    .nav-container {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .login-btn {
      display: inline-block;
      padding: 1rem 2.5rem;
      font-size: 1.1rem;
      font-weight: 600;
      border-radius: 0.75rem;
      border: 2px solid #FB923C;
      color: #FB923C;
      background: transparent;
      text-decoration: none;
      transition: all 0.3s;
    }
    .login-btn:hover {
      background: #FB923C;
      color: #fff;
    }

    /* Hero */
    .hero {
      padding: 4rem 1.5rem;
    }
    @media (min-width: 768px) {
      .hero {
        padding: 6rem 2.5rem;
      }
    }
    .hero-container {
      max-width: 80rem;
      margin: auto;
      display: flex;
      flex-direction: column-reverse;
      gap: 3rem;
    }
    @media (min-width: 1024px) {
      .hero-container {
        flex-direction: row;
        align-items: center;
        gap: 4rem;
      }
    }

    /* Left content */
    .hero h1 {
      font-size: 2.5rem;
      font-weight: 700;
      color: #111827;
      line-height: 1.1;
    }
    @media (min-width: 768px) {
      .hero h1 {
        font-size: 4rem;
      }
    }
    .hero h1 span {
      color: #FB923C;
    }
    .hero p {
      font-size: 1.125rem;
      color: #4B5563;
      max-width: 42rem;
      line-height: 1.625;
      margin-top: 1rem;
      margin-bottom: 2rem;
    }
    @media (min-width: 768px) {
      .hero p {
        font-size: 1.25rem;
      }
    }

    /* Stats */
    .stats {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 2rem;
      margin-top: 2rem;
    }
    .stats div {
      text-align: center;
    }
    .stats .number {
      font-size: 1.875rem;
      font-weight: 700;
      color: #FB923C;
    }
    .stats .label {
      color: #6B7280;
      font-size: 0.9rem;
      font-weight: 500;
    }

    /* Right card */
    .card {
      background: #fff;
      border: 1px solid #F3F4F6;
      padding: 2rem;
      border-radius: 1rem;
      max-width: 28rem;
      margin: auto;
      box-shadow: 0 20px 25px -5px rgba(0,0,0,.1),0 8px 10px -6px rgba(0,0,0,.1);
    }
    .card-icon {
      background: #FB923C;
      padding: 0.75rem;
      border-radius: 0.75rem;
      display: inline-flex;
      justify-content: center;
      align-items: center;
      margin-bottom: 1rem;
    }
    .card h2 {
      font-size: 1.5rem;
      font-weight: 700;
      text-align: center;
      margin-bottom: 1.5rem;
      color: #374151;
    }
    .card img {
      border-radius: 0.5rem;
      object-fit: cover;
      width: 100%;
    }
    .badge {
      display: inline-block;
      padding: 0.25rem 0.75rem;
      font-size: 0.75rem;
      font-weight: 600;
      border-radius: 9999px;
      background: #FFEDD5;
      color: #B45309;
    }
    .badge-group {
      margin-top: 1rem;
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
      justify-content: center;
    }
  </style>
</head>
<body>
  <!-- Navbar -->
  <div class="pt-6 px-4 md:px-8">
    <nav>
      <div class="nav-container">
        <!-- Left logo -->
        <img src="public/images/logo.png" alt="Logo" width="auto" height="80" />

        <!-- Right login -->
        <a href="login.php" class="login-btn">Login</a>
      </div>
    </nav>
  </div>

  <!-- Hero Section -->
  <section class="hero min-h-screen">
    <div class="hero-container">
      <!-- Left Content -->
      <div class="flex-1 text-center md:text-left">
        <div class="inline-flex items-center px-4 py-2 bg-orange-100 text-orange-800 rounded-full text-sm font-medium mb-6">
          Professional ID Solutions
        </div>

        <h1>
          Maheshwari ID<br />
          <span>Card's</span>
        </h1>

        <p>
          Transform your organization with our premium ID card solutions. 
          Create professional identity cards for students, employees, and organizations 
          with <span style="color:#EA580C; font-weight:600;">hundreds of beautifully designed templates</span>.
        </p>

        <!-- Stats -->
        <div class="stats">
          <div>
            <div class="number">500+</div>
            <div class="label">ID Templates</div>
          </div>
          <div>
            <div class="number">10K+</div>
            <div class="label">Happy Clients</div>
          </div>
          <div>
            <div class="number">24/7</div>
            <div class="label">Support</div>
          </div>
        </div>
      </div>

      <!-- Right Card -->
      <div class="flex-1 flex justify-center">
        <div class="card">
          <div class="flex items-center justify-center">
            <div class="card-icon">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V4a2 2 0 114 0v2m-4 0a2 2 0 104 0m-4 0v2m4-2v2"></path>
              </svg>
            </div>
          </div>

          <h2>Identity Cards Management System</h2>

          <div class="border-4 border-orange-200 p-3 rounded-xl bg-orange-50">
            <img src="public/images/hero.png" alt="Professional ID Cards Collection" />
          </div>

          <div class="badge-group">
            <span class="badge">Professional Design</span>
            <span class="badge">Quick Delivery</span>
            <span class="badge">Secure</span>
          </div>
        </div>
      </div>
    </div>
  </section>
</body>
</html>
