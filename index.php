<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سیستم مدیریت شکایات</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Tahoma', sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }
        
        /* Header Section */
        .header-section {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            padding: 1.5rem 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: #2c3e50;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
        }
        
        .logo-icon {
            font-size: 2rem;
            margin-left: 1rem;
            color: #007bff;
        }
        
        .logo-text {
            font-size: 1.4rem;
            font-weight: bold;
            margin: 0;
            color: #2c3e50;
        }
        
        .login-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .login-btn {
            background: #007bff;
            border: 2px solid #007bff;
            border-radius: 50px;
            padding: 0.6rem 1.5rem;
            font-size: 0.9rem;
            font-weight: bold;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .login-btn:hover {
            background: #0056b3;
            border-color: #0056b3;
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
        }
        
        /* Hero Section */
        .hero-section {
            padding: 6rem 0;
            color: #2c3e50;
            background: white;
            min-height: 80vh;
        }
        
        .hero-content {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .hero-text {
            text-align: center;
        }
        
        .hero-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            line-height: 1.3;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: none;
            letter-spacing: -0.5px;
        }
        
        .hero-subtitle {
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            color: #6c757d;
            line-height: 1.6;
        }
        
        .hero-description {
            font-size: 1rem;
            margin-bottom: 4rem;
            color: #5a6c7d;
            line-height: 1.8;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            font-weight: 400;
            text-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        
        .cta-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .cta-btn {
            padding: 0.8rem 1.5rem;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: bold;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .cta-btn-primary {
            background: #28a745;
            border: 2px solid #28a745;
            color: white;
        }
        
        .cta-btn-primary:hover {
            background: #218838;
            border-color: #218838;
            color: white;
            text-decoration: none;
            transform: translateY(-3px);
        }
        
        .cta-btn-secondary {
            background: transparent;
            border: 2px solid #007bff;
            color: #007bff;
        }
        
        .cta-btn-secondary:hover {
            background: #007bff;
            color: white;
            text-decoration: none;
            transform: translateY(-3px);
        }
        
        
        /* Features Section */
        .features-section {
            padding: 3rem 0;
            background: #f8f9fa;
        }
        
        .features-title {
            text-align: center;
            color: #2c3e50;
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 2rem;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        
        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            color: #2c3e50;
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #007bff;
        }
        
        .feature-title {
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 0.8rem;
            color: #2c3e50;
        }
        
        .feature-description {
            font-size: 0.9rem;
            color: #6c757d;
            line-height: 1.6;
        }
        
        
        /* Footer */
        .footer-section {
            padding: 1.5rem 0;
            background: #2c3e50;
            color: white;
            text-align: center;
            border-top: 1px solid #34495e;
        }
        
        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .footer-text {
            opacity: 0.8;
            font-size: 0.9rem;
        }
        
        .footer-links {
            display: flex;
            gap: 2rem;
        }
        
        .footer-link {
            color: white;
            text-decoration: none;
            opacity: 0.8;
            transition: opacity 0.3s ease;
            font-size: 0.9rem;
        }
        
        .footer-link:hover {
            opacity: 1;
            color: white;
            text-decoration: none;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-content {
                grid-template-columns: 1fr;
                gap: 2rem;
                text-align: center;
            }
            
            .hero-title {
                font-size: 1.8rem;
            }
            
            .hero-subtitle {
                font-size: 1rem;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
            
            .footer-content {
                flex-direction: column;
                text-align: center;
            }
            
            .header-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <div class="header-section">
        <div class="container">
            <div class="header-content">
                <div class="logo-section">
                    <i class="fas fa-shield-alt logo-icon"></i>
                    <h1 class="logo-text">سیستم مدیریت شکایات</h1>
                </div>
                <div class="login-section">
                    <a href="login.php" class="login-btn">
                        <i class="fas fa-sign-in-alt"></i>
                        ورود به سیستم
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <h1 class="hero-title">سیستم جامع و پیشرفته برای مدیریت و پیگیری شکایات</h1>
                    <p class="hero-description">
                        با استفاده از این سیستم می‌توانید شکایات را به صورت حرفه‌ای مدیریت کنید، 
                        گزارشات جامع تهیه کنید و فرآیند پیگیری را بهینه‌سازی نمایید.
                    </p>
                    <div class="cta-buttons">
                        <a href="login.php" class="cta-btn cta-btn-primary">
                            <i class="fas fa-sign-in-alt"></i>
                            ورود به سیستم
                        </a>
                        <a href="#features" class="cta-btn cta-btn-secondary">
                            <i class="fas fa-info-circle"></i>
                            اطلاعات بیشتر
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="features-section" id="features">
        <div class="container">
            <div class="features-grid">
                <div class="feature-card">
                    <i class="fas fa-users feature-icon"></i>
                    <h3 class="feature-title">مدیریت افراد</h3>
                    <p class="feature-description">
                        ثبت و مدیریت اطلاعات کامل افراد شامل کد ملی، اطلاعات تماس و سایر جزئیات
                    </p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-folder-open feature-icon"></i>
                    <h3 class="feature-title">مدیریت پرونده‌ها</h3>
                    <p class="feature-description">
                        ایجاد، پیگیری و مدیریت پرونده‌های شکایات با قابلیت‌های پیشرفته
                    </p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-file-alt feature-icon"></i>
                    <h3 class="feature-title">ثبت ورودی‌ها</h3>
                    <p class="feature-description">
                        ثبت ورودی‌ها و پیوست‌ها برای هر پرونده با امکان پیگیری کامل
                    </p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-chart-bar feature-icon"></i>
                    <h3 class="feature-title">گزارشات و آمار</h3>
                    <p class="feature-description">
                        تهیه گزارشات جامع و آمار دقیق از وضعیت پرونده‌ها و عملکرد سیستم
                    </p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-lock feature-icon"></i>
                    <h3 class="feature-title">امنیت بالا</h3>
                    <p class="feature-description">
                        سیستم امنیتی پیشرفته با کنترل دسترسی و محافظت از اطلاعات
                    </p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-mobile-alt feature-icon"></i>
                    <h3 class="feature-title">طراحی واکنش‌گرا</h3>
                    <p class="feature-description">
                        رابط کاربری مدرن و واکنش‌گرا که در تمام دستگاه‌ها بهینه کار می‌کند
                    </p>
                </div>
            </div>
        </div>
    </div>


    <!-- Footer -->
    <div class="footer-section">
        <div class="container">
            <div class="footer-content">
                <div class="footer-text">
                    <p>&copy; ۱۴۰۳ سیستم مدیریت شکایات - تمامی حقوق محفوظ است</p>
                </div>
                <div class="footer-links">
                    <a href="#" class="footer-link">درباره ما</a>
                    <a href="#" class="footer-link">تماس با ما</a>
                    <a href="#" class="footer-link">راهنمای استفاده</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>