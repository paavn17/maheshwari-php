<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Maheshwari ID Card's — Landing Page</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>
        body { margin:0; font-family:ui-sans-serif,system-ui,Arial,sans-serif; background: linear-gradient(135deg,#FFFAF0 0%,#FFF 100%);}
        .min-h-screen {min-height: 100vh;}
        .px-4 {padding-left:1rem; padding-right:1rem;}
        .md\:px-8 {padding-left:2rem; padding-right:2rem;}
        .px-6 {padding-left:1.5rem; padding-right:1.5rem;}
        .md\:px-10 {padding-left:2.5rem; padding-right:2.5rem;}
        .p-8 {padding:2rem;}
        .pt-6 {padding-top:1.5rem;}
        .py-4 {padding-top:1rem; padding-bottom:1rem;}
        .max-w-7xl {max-width:80rem; margin-left:auto; margin-right:auto;}
        .bg-white\/90 {background-color: rgba(255, 255, 255, 0.9);}
        .backdrop-blur-md {backdrop-filter: blur(8px);}
        .border {border: 1px solid #fed7aa;} /* orange-100 */
        .rounded-2xl {border-radius:1rem;}
        .shadow-lg {box-shadow: 0 10px 15px -3px rgba(0,0,0,.1), 0 4px 6px -4px rgba(0,0,0,.1);}
        .flex {display:flex;}
        .flex-col {flex-direction: column;}
        .sm\:flex-row {flex-direction: row;}
        .justify-between {justify-content: space-between;}
        .items-center {align-items: center;}
        .gap-4 {gap: 1rem;}
        .pt-12 {padding-top: 3rem;}
        .mt-12 {margin-top: 3rem;}
        .flex-1 {flex: 1;}
        .text-center {text-align: center;}
        .md\:text-left {text-align: left;}
        .space-y-8 > * + * {margin-top: 2rem;}
        .bg-orange-100 {background:#FFEDD5;}
        .text-orange-800 {color:#9A3412;}
        .rounded-full {border-radius:9999px;}
        .text-sm {font-size:.875rem;}
        .font-medium {font-weight:500;}
        .mb-6 {margin-bottom:1.5rem;}
        .text-5xl {font-size:3rem;}
        .md\:text-7xl {font-size:5rem;}
        .font-bold {font-weight:700;}
        .text-gray-900 {color:#111827;}
        .leading-tight {line-height:1.1;}
        .text-orange-400 {color:#FB923C;}
        .text-lg {font-size:1.125rem;}
        .md\:text-xl {font-size:1.25rem;}
        .text-gray-600 {color:#4B5563;}
        .max-w-2xl {max-width:42rem;}
        .leading-relaxed {line-height:1.625;}
        .grid {display:grid;}
        .grid-cols-3 {grid-template-columns:repeat(3,1fr);}
        .gap-8 {gap:2rem;}
        .pt-8 {padding-top:2rem;}
        .sm\:text-left {text-align:left;}
        .text-3xl {font-size:1.875rem;}
        .text-gray-500 {color:#6B7280;}
        .bg-white {background:#FFF;}
        .p-8 {padding:2rem;}
        .rounded-2xl {border-radius:1rem;}
        .shadow-xl {box-shadow:0 20px 25px -5px rgba(0,0,0,.1),0 8px 10px -6px rgba(0,0,0,.1);}
        .border-gray-100 {border-color:#F3F4F6;}
        .max-w-md {max-width:28rem;}
        .flex-wrap {flex-wrap:wrap;}
        .gap-2 {gap:.5rem;}
        .justify-center {justify-content:center;}
        .bg-orange-400 {background:#FB923C;}
        .p-3 {padding:.75rem;}
        .rounded-xl {border-radius:.75rem;}
        .text-white {color:#FFF;}
        .border-4 {border-width:4px;}
        .border-orange-200 {border-color:#FED7AA;}
        .rounded-lg {border-radius:.5rem;}
        .object-cover {object-fit:cover;}
        .bg-orange-50 {background:#FFFBEB;}
        .px-3 {padding-left:.75rem;padding-right:.75rem;}
        .py-1 {padding-top:.25rem;padding-bottom:.25rem;}
        .text-orange-700 {color:#B45309;}
        .text-xs {font-size: .75rem;}
        .font-semibold {font-weight:600;}
        .hover\:bg-gray-200:hover {background:#E5E7EB;}
        .hover\:bg-orange-500:hover {background:#F97316;}
        .rounded-xl {border-radius:1rem;}
        .w-full {width:100%;}
        .sm\:w-auto {width:auto;}
        .text-gray-700 {color:#374151;}
        .border-gray-200 {border-color:#E5E7EB;}
        .transition-colors {transition: background-color 0.3s;}
        .text-white {color:#FFF;}
        .shadow-md {box-shadow: 0 4px 6px rgba(0,0,0,0.1);}
        .cursor-pointer {cursor:pointer;}
    </style>
</head>
<body>
    <!-- Navbar -->
    <div class="pt-6 px-4 md:px-8">
        <nav class="w-full max-w-7xl mx-auto bg-white/90 backdrop-blur-md border border-orange-100 rounded-2xl px-6 md:px-10 shadow-lg">
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4 py-4">
                <!-- Logo -->
                <img src="public/images/logo.png" alt="Logo" width="90" height="50" />

                <!-- Login Button -->
                <div class="w-full sm:w-auto">
                    <a href="login.php"
   class="w-full sm:w-auto px-8 py-3 text-sm font-semibold text-white bg-orange-400 hover:bg-orange-500 rounded-xl shadow-md transition-colors cursor-pointer" 
   style="display:inline-block; text-align:center; text-decoration:none;">
   Login
</a>

                </div>
            </div>
        </nav>
    </div>

    <!-- Hero Section -->
    <section class="min-h-screen bg-gradient-to-br px-6 md:px-16 py-6">
        <div class="max-w-7xl mx-auto flex flex-col-reverse lg:flex-row items-center gap-16 w-full mt-12">
            <!-- Left Content -->
            <div class="flex-1 text-center md:text-left space-y-8">
                <div class="inline-flex items-center px-4 py-2 bg-orange-100 text-orange-800 rounded-full text-sm font-medium mb-6">
                     Professional ID Solutions
                </div>

                <h1 class="text-5xl md:text-7xl font-bold text-gray-900 leading-tight">
                    Maheshwari ID<br />
                    <span class="text-orange-400">Card's</span>
                </h1>

                <p class="text-lg md:text-xl text-gray-600 max-w-2xl leading-relaxed">
                    Transform your organization with our premium ID card solutions. 
                    Create professional identity cards for students, employees, and organizations 
                    with <span class="text-orange-600 font-semibold">hundreds of beautifully designed templates</span>.
                </p>

                <!-- Stats -->
                <div class="grid grid-cols-3 gap-8 pt-8">
                    <div class="text-center sm:text-left">
                        <div class="text-3xl font-bold text-orange-400">500+</div>
                        <div class="text-gray-500 text-sm font-medium">ID Templates</div>
                    </div>
                    <div class="text-center sm:text-left">
                        <div class="text-3xl font-bold text-orange-400">10K+</div>
                        <div class="text-gray-500 text-sm font-medium">Happy Clients</div>
                    </div>
                    <div class="text-center sm:text-left">
                        <div class="text-3xl font-bold text-orange-400">24/7</div>
                        <div class="text-gray-500 text-sm font-medium">Support</div>
                    </div>
                </div>
            </div>

            <!-- Right Image Box -->
            <div class="flex-1 flex justify-end">
                <div class="bg-white p-8 rounded-2xl shadow-xl border border-gray-100 max-w-md w-full">
                    <!-- Header with icon -->
                    <div class="flex items-center justify-center mb-6">
                        <div class="bg-orange-400 p-3 rounded-xl">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="32" height="32">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V4a2 2 0 114 0v2m-4 0a2 2 0 104 0m-4 0v2m4-2v2"></path>
                            </svg>
                        </div>
                    </div>

                    <h2 class="text-xl md:text-2xl font-bold text-center text-gray-800 mb-8">
                        Identity Cards Management System
                    </h2>

                    <!-- Image container -->
                    <div class="border-4 border-orange-200 p-3 rounded-xl bg-orange-50">
                        <img src="public/images/hero.png" alt="Professional ID Cards Collection" width="500" height="400" style="border-radius:0.5rem; object-fit:cover;" />
                    </div>

                    <!-- Feature badges -->
                    <div class="flex flex-wrap gap-2 mt-6 justify-center">
                        <span class="px-3 py-1 bg-orange-100 text-orange-700 text-xs font-medium rounded-full">
                            Professional Design
                        </span>
                        <span class="px-3 py-1 bg-orange-100 text-orange-700 text-xs font-medium rounded-full">
                            Quick Delivery
                        </span>
                        <span class="px-3 py-1 bg-orange-100 text-orange-700 text-xs font-medium rounded-full">
                            Secure
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>
</body>
</html>
