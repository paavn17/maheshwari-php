<?php
require_once 'database.php'; // DB connection ($conn)
session_start();

// Handle POST login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $input = json_decode(file_get_contents('php://input'), true);
    $username = $input['email'] ?? '';   // for non-students, it's email; for students, it's roll_no
    $passwordInput = $input['password'] ?? ''; // for students, this is mobile
    $role = $input['role'] ?? '';
    $rememberMe = $input['rememberMe'] ?? false;

    if (!$username || !$passwordInput || !$role) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing credentials or role']);
        exit;
    }

    if ($role === 'employee') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Employee login is under development.']);
        exit;
    }

    switch ($role) {
        case 'student':
            $sql = "SELECT id, roll_no, mobile FROM students WHERE roll_no = ? AND acc_status = 'Live'";
            break;
        case 'institution admin':
            $sql = "SELECT id, email, password FROM institution_admins WHERE email = ?";
            break;
        case 'super admin':
            $sql = "SELECT id, email, password FROM super_admins WHERE email = ?";
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid role selected']);
            exit;
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error']);
        exit;
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid credentials']);
        exit;
    }

    $user = $result->fetch_assoc();

    // ✅ Validation
    if ($role === 'student') {
        $validPassword = ($passwordInput === $user['mobile']);
    } else {
        $validPassword = ($passwordInput === $user['password']);
    }

    if (!$validPassword) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid credentials']);
        exit;
    }

    // ✅ Set session
    $_SESSION['role'] = $role;
    $_SESSION['user_id'] = $user['id'];
    if ($role === 'student') $_SESSION['roll_no'] = $user['roll_no'];
    if ($role !== 'student') $_SESSION['email'] = $user['email'];

    // Dashboard mapping
    $dashboardMap = [
        'student' => '/maheshwari/student/dashboard.php',
        'institution admin' => '/maheshwari/institute/dashboard.php',
        'super admin' => '/maheshwari/admin/dashboard.php',
    ];

    echo json_encode(['success' => true, 'redirectUrl' => $dashboardMap[$role]]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Maheshwari ID Card's — Login Page</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<style>
body { margin:0; font-family: ui-sans-serif, system-ui, Arial, sans-serif; background: white; }
.min-h-screen { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem;}
.container { max-width: 400px; width: 100%; background: white; border: 1px solid #e5e7eb; border-radius: 1rem; padding: 1.5rem 2rem; box-shadow: 0 10px 15px -3px rgba(0,0,0,.1);}
.text-center { text-align: center; }
h1 { font-weight: 700; color: #2d3748; margin-bottom: 0.25rem; }
p { color: #718096; font-size: 0.9rem; margin-bottom: 1.5rem; }
label { display: block; font-weight: 600; color: #4a5568; margin-bottom: 0.5rem; }
input[type=text], input[type=password] {
  width: 100%; padding: 0.5rem; border: 2px solid #cbd5e0; border-radius: 0.5rem; margin-bottom: 1rem;
  font-size: 1rem; outline: none;
}
input[type=text]:focus, input[type=password]:focus {
  border-color: #f97316;
  box-shadow: 0 0 0 1px #f97316;
}
.button {
  width: 100%; background: linear-gradient(to right, #fb923c, #f97316);
  color: white; padding: 0.5rem; border:none; border-radius: 0.5rem;
  font-weight: 600; font-size: 1rem; cursor: pointer;
  transition: background 0.3s ease;
}
.button:hover { background: linear-gradient(to right, #f97316, #ea580c); }
.role-options { display: grid; grid-template-columns: repeat(2,1fr); gap: 0.5rem; margin-bottom: 1rem;}
.role-btn { padding: 0.5rem; border: 2px solid #e2e8f0; border-radius:0.5rem; cursor:pointer; font-weight: 600; color: #4a5568; background: white; display: flex; align-items: center; gap: 0.5rem; justify-content: center; transition: all 0.3s ease;}
.role-btn.selected { background: linear-gradient(to right, #fb923c, #f97316); color: white; border-color: #f97316; transform: scale(1.05);}
.role-btn.employee { opacity: 0.6; cursor: not-allowed; pointer-events: auto;}
.icon { width: 20px; height: 20px; display: inline-block; }
.text-danger { color: #e53e3e; font-size: 0.8rem; margin-top: -0.75rem; margin-bottom: 0.75rem; display: none; }
.checkbox-label { display: flex; align-items: center; gap: 0.5rem; font-weight: 600; color: #4a5568; margin-bottom: 1rem; cursor: pointer; }
.checkbox-label input { width: 18px; height: 18px; }
.relative { position: relative; }
.message-success { background: #f0fff4; border: 1px solid #48bb78; color: #38a169; padding: 0.5rem; border-radius: 0.5rem; margin-bottom: 1rem; display:none; }
.message-error { background: #fff5f5; border: 1px solid #f56565; color: #e53e3e; padding: 0.5rem; border-radius: 0.5rem; margin-bottom: 1rem; display:none; }
</style>
</head>
<body>
<div class="min-h-screen">
  <div class="container" id="login-container">
    <div class="text-center">
      <img src="public/images/logo.png" alt="Logo" width="64" height="64" style="border-radius:50%; border: 2px solid white; box-shadow: 0 0 8px rgba(251,146,60,0.7); margin-bottom: 0.5rem;">
      <h1>Maheshwari ID Card's</h1>
      <p>Welcome back! Please sign in to continue</p>
    </div>
    <div id="successMessage" class="message-success">Login successful! Redirecting...</div>
    <div id="generalError" class="message-error"></div>
    <form id="loginForm">
      <label>Select Your Role</label>
      <div class="role-options" id="roleOptions"></div>
      <div id="roleError" class="text-danger">Please select your role</div>

      <label id="usernameLabel" for="email">Email Address</label>
      <input type="text" id="email" placeholder="Email / Roll No" />
      <div id="emailError" class="text-danger">This field is required</div>

      <label id="passwordLabel" for="password">Password</label>
      <div class="relative">
        <input type="password" id="password" placeholder="Password / Mobile" />
      </div>
      <div id="passwordError" class="text-danger">This field is required</div>

      <label class="checkbox-label">
        <input type="checkbox" id="rememberMe" />
        Remember me
      </label>

      <button type="submit" class="button">Sign In</button>
    </form>
  </div>
</div>

<script>
const ROLE_OPTIONS = [
  { id: 'student', label: 'Student', icon: '📚', dashboardUrl: '/maheshwari/student/dashboard.php', disabled: true },
  { id: 'employee', label: 'Employee', icon: '💼', dashboardUrl: '#', disabled: true },
  { id: 'institution admin', label: 'Institution Admin', icon: '👥', dashboardUrl: '/maheshwari/institute/dashboard.php' },
  { id: 'super admin', label: 'Super Admin', icon: '🛡️', dashboardUrl: '/maheshwari/admin/dashboard.php' },
];

const roleOptionsDiv = document.getElementById('roleOptions');
const loginForm = document.getElementById('loginForm');
let selectedRole = '';
let formSubmitted = false;

function renderRoles() {
  roleOptionsDiv.innerHTML = '';
  ROLE_OPTIONS.forEach(role => {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'role-btn ' + (role.disabled ? 'employee' : '');
    btn.classList.toggle('selected', selectedRole === role.id);
    btn.innerHTML = `<span class="icon">${role.icon}</span>${role.label}`;

    if(role.disabled){
      btn.addEventListener('click', () => { alert(role.label + ' login is under development.'); });
    } else {
      btn.addEventListener('click', () => {
        selectedRole = role.id;
        renderRoles();
        adjustLabels();
        if(formSubmitted) hideError('roleError');
      });
    }
    roleOptionsDiv.appendChild(btn);
  });
}
renderRoles();

function adjustLabels(){
  const usernameLabel = document.getElementById('usernameLabel');
  const passwordLabel = document.getElementById('passwordLabel');
  if(selectedRole === 'student'){
    usernameLabel.textContent = "Roll No";
    passwordLabel.textContent = "Mobile Number";
  } else {
    usernameLabel.textContent = "Email Address";
    passwordLabel.textContent = "Password";
  }
}

function showError(id, message) { const el = document.getElementById(id); el.textContent = message; el.style.display='block'; }
function hideError(id) { document.getElementById(id).style.display='none'; }

loginForm.addEventListener('submit', e => {
  e.preventDefault();
  formSubmitted = true;
  let valid = true;
  const email = document.getElementById('email').value.trim();
  const password = document.getElementById('password').value.trim();

  if(!selectedRole){ showError('roleError','Please select your role'); valid=false; } else hideError('roleError');
  if(!email){ showError('emailError','This field is required'); valid=false; } else hideError('emailError');
  if(!password){ showError('passwordError','This field is required'); valid=false; } else hideError('passwordError');
  if(!valid) return;

  document.getElementById('generalError').style.display='none';
  const successMessage = document.getElementById('successMessage'); successMessage.style.display='none';

  fetch('', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ email, password, role:selectedRole, rememberMe: document.getElementById('rememberMe').checked })
  }).then(async res=>{
    const data = await res.json();
    if(res.ok && data.success){
      successMessage.style.display='block';
      setTimeout(()=>{ window.location.href = data.redirectUrl; },1000);
    } else {
      const generalError=document.getElementById('generalError');
      generalError.textContent = data.error || 'Login failed. Please try again.';
      generalError.style.display='block';
    }
  }).catch(()=>{
    const generalError=document.getElementById('generalError');
    generalError.textContent='Unexpected error.';
    generalError.style.display='block';
  });
});
</script>
</body>
</html>
