<?php
require_once __DIR__ . '/../config/constants.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bergabung Sebagai Kurir - DailyCup Coffee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary-color: #6F4E37;
            --secondary-color: #8B4513;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 80px 0;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><path d="M0 0h100v100H0z" fill="none"/><path d="M50 10c-5.5 0-10 4.5-10 10s4.5 10 10 10 10-4.5 10-10-4.5-10-10-10zm0 15c-2.8 0-5-2.2-5-5s2.2-5 5-5 5 2.2 5 5-2.2 5-5 5z" fill="%23ffffff" opacity="0.1"/></svg>');
            opacity: 0.1;
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
        }
        
        .hero-icon {
            font-size: 5rem;
            margin-bottom: 20px;
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        
        .benefit-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
        }
        
        .benefit-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .benefit-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
        }
        
        .feature-list li {
            padding: 15px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .feature-list li:last-child {
            border-bottom: none;
        }
        
        .feature-icon {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .cta-section {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 60px 0;
            border-radius: 20px;
            margin: 50px 0;
        }
        
        .btn-login {
            background: white;
            color: var(--primary-color);
            padding: 15px 40px;
            font-size: 1.2rem;
            font-weight: 600;
            border: none;
            border-radius: 50px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            transition: all 0.3s;
        }
        
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
            color: var(--primary-color);
        }
        
        .faq-item {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .faq-question {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 10px;
            font-size: 1.1rem;
        }
        
        .stats-section {
            background: #f8f9fa;
            padding: 60px 0;
            margin: 50px 0;
            border-radius: 20px;
        }
        
        .stat-box {
            text-align: center;
            padding: 20px;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .stat-label {
            color: #666;
            font-size: 1.1rem;
        }
        
        .requirements-badge {
            display: inline-block;
            background: #e9ecef;
            padding: 10px 20px;
            border-radius: 25px;
            margin: 5px;
            font-weight: 500;
        }
    </style>
</head>
<body>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container hero-content text-center">
            <div class="hero-icon">
                <i class="bi bi-bicycle"></i>
            </div>
            <h1 class="display-4 fw-bold mb-3">Bergabung Sebagai Kurir DailyCup</h1>
            <p class="lead mb-4">Dapatkan penghasilan tambahan dengan fleksibilitas waktu kerja Anda sendiri!</p>
            <a href="login.php" class="btn btn-login btn-lg">
                <i class="bi bi-box-arrow-in-right"></i> Login Kurir
            </a>
            <div class="mt-3">
                <small>Belum punya akun? Hubungi admin untuk registrasi</small>
            </div>
        </div>
    </div>

    <!-- Benefits Section -->
    <div class="container my-5">
        <h2 class="text-center mb-5 fw-bold">Mengapa Bergabung dengan Kami?</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <h4>Penghasilan Menarik</h4>
                    <p>Dapatkan komisi kompetitif untuk setiap pengiriman yang berhasil diselesaikan.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="bi bi-clock"></i>
                    </div>
                    <h4>Waktu Fleksibel</h4>
                    <p>Atur jadwal kerja Anda sendiri sesuai dengan waktu luang yang Anda miliki.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="bi bi-phone"></i>
                    </div>
                    <h4>Teknologi Modern</h4>
                    <p>Dashboard mobile yang mudah digunakan dengan GPS tracking real-time.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h4>Asuransi Kesehatan</h4>
                    <p>Perlindungan asuransi untuk kecelakaan kerja selama bertugas.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="bi bi-award"></i>
                    </div>
                    <h4>Bonus Performa</h4>
                    <p>Bonus tambahan untuk kurir dengan rating tinggi dan banyak pengiriman.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <h4>Komunitas Solid</h4>
                    <p>Bergabung dengan tim kurir yang supportif dan professional.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Section -->
    <div class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-md-3 col-6">
                    <div class="stat-box">
                        <div class="stat-number">500+</div>
                        <div class="stat-label">Pengiriman/Bulan</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-box">
                        <div class="stat-number">4.8★</div>
                        <div class="stat-label">Rating Rata-rata</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-box">
                        <div class="stat-number">50+</div>
                        <div class="stat-label">Kurir Aktif</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-box">
                        <div class="stat-number">99%</div>
                        <div class="stat-label">On-Time Delivery</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="container my-5">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h2 class="fw-bold mb-4">Fitur Dashboard Kurir</h2>
                <ul class="feature-list">
                    <li>
                        <div class="feature-icon">
                            <i class="bi bi-list-task"></i>
                        </div>
                        <div>
                            <strong>Manajemen Orderan</strong><br>
                            <small class="text-muted">Lihat dan kelola semua orderan aktif dalam satu dashboard</small>
                        </div>
                    </li>
                    <li>
                        <div class="feature-icon">
                            <i class="bi bi-geo-alt"></i>
                        </div>
                        <div>
                            <strong>GPS Navigation</strong><br>
                            <small class="text-muted">Navigasi langsung ke alamat customer dengan GPS real-time</small>
                        </div>
                    </li>
                    <li>
                        <div class="feature-icon">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div>
                            <strong>Update Status Mudah</strong><br>
                            <small class="text-muted">Update status pengiriman dengan sekali tap</small>
                        </div>
                    </li>
                    <li>
                        <div class="feature-icon">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <div>
                            <strong>Statistik Performa</strong><br>
                            <small class="text-muted">Pantau penghasilan dan performa harian Anda</small>
                        </div>
                    </li>
                    <li>
                        <div class="feature-icon">
                            <i class="bi bi-telephone"></i>
                        </div>
                        <div>
                            <strong>Kontak Customer Langsung</strong><br>
                            <small class="text-muted">Hubungi customer dengan satu klik untuk koordinasi</small>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="col-md-6 text-center">
                <img src="<?php echo SITE_URL; ?>/assets/images/kurir-dashboard-preview.png" 
                     alt="Dashboard Preview" 
                     class="img-fluid rounded shadow"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <div style="display: none; background: #f8f9fa; padding: 100px; border-radius: 10px;">
                    <i class="bi bi-phone" style="font-size: 5rem; color: var(--primary-color);"></i>
                    <p class="mt-3 text-muted">Dashboard Mobile Preview</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Requirements Section -->
    <div class="container my-5">
        <h2 class="text-center mb-4 fw-bold">Persyaratan Menjadi Kurir</h2>
        <div class="text-center">
            <div class="requirements-badge">
                <i class="bi bi-person-check"></i> Usia minimal 18 tahun
            </div>
            <div class="requirements-badge">
                <i class="bi bi-bicycle"></i> Memiliki kendaraan (motor/sepeda)
            </div>
            <div class="requirements-badge">
                <i class="bi bi-phone"></i> Smartphone dengan GPS
            </div>
            <div class="requirements-badge">
                <i class="bi bi-card-checklist"></i> KTP & SIM yang masih berlaku
            </div>
            <div class="requirements-badge">
                <i class="bi bi-heart"></i> Sehat jasmani & rohani
            </div>
            <div class="requirements-badge">
                <i class="bi bi-star"></i> Attitude baik & customer oriented
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="container">
        <div class="cta-section text-center">
            <div class="container">
                <h2 class="display-5 fw-bold mb-3">Siap Bergabung?</h2>
                <p class="lead mb-4">Mulai perjalanan Anda sebagai kurir DailyCup hari ini!</p>
                <a href="login.php" class="btn btn-login btn-lg me-3">
                    <i class="bi bi-box-arrow-in-right"></i> Login Sekarang
                </a>
                <a href="<?php echo SITE_URL; ?>/contact.php" class="btn btn-outline-light btn-lg">
                    <i class="bi bi-envelope"></i> Hubungi Kami
                </a>
            </div>
        </div>
    </div>

    <!-- FAQ Section -->
    <div class="container my-5">
        <h2 class="text-center mb-5 fw-bold">Pertanyaan Yang Sering Diajukan</h2>
        <div class="row">
            <div class="col-md-6">
                <div class="faq-item">
                    <div class="faq-question">
                        <i class="bi bi-question-circle"></i> Bagaimana cara mendaftar sebagai kurir?
                    </div>
                    <p class="mb-0">Hubungi admin kami melalui WhatsApp atau datang langsung ke kantor DailyCup dengan membawa persyaratan yang dibutuhkan. Tim kami akan memproses pendaftaran Anda dalam 1-2 hari kerja.</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="faq-item">
                    <div class="faq-question">
                        <i class="bi bi-question-circle"></i> Berapa penghasilan rata-rata kurir?
                    </div>
                    <p class="mb-0">Penghasilan bervariasi tergantung jumlah pengiriman. Rata-rata kurir aktif bisa mendapat Rp 2-5 juta per bulan dengan bekerja 4-6 jam per hari.</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="faq-item">
                    <div class="faq-question">
                        <i class="bi bi-question-circle"></i> Apakah ada biaya pendaftaran?
                    </div>
                    <p class="mb-0">Tidak ada biaya pendaftaran! Kami hanya meminta deposit jaminan yang dapat dikembalikan setelah Anda berhenti bekerja.</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="faq-item">
                    <div class="faq-question">
                        <i class="bi bi-question-circle"></i> Bagaimana sistem pembayaran?
                    </div>
                    <p class="mb-0">Pembayaran dilakukan setiap minggu melalui transfer bank. Anda juga bisa request payment kapan saja setelah mencapai minimal saldo.</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="faq-item">
                    <div class="faq-question">
                        <i class="bi bi-question-circle"></i> Apakah bisa paruh waktu?
                    </div>
                    <p class="mb-0">Ya! Anda bebas mengatur jadwal sendiri. Banyak kurir kami yang bekerja paruh waktu sambil kuliah atau kerja lain.</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="faq-item">
                    <div class="faq-question">
                        <i class="bi bi-question-circle"></i> Bagaimana dengan asuransi?
                    </div>
                    <p class="mb-0">Semua kurir aktif dilindungi asuransi kecelakaan kerja. Premi dibayar oleh perusahaan, tidak ada potongan dari penghasilan Anda.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Section -->
    <div class="container my-5 text-center">
        <div class="card shadow-lg border-0">
            <div class="card-body p-5">
                <h3 class="fw-bold mb-3">Masih Ada Pertanyaan?</h3>
                <p class="text-muted mb-4">Tim kami siap membantu Anda!</p>
                <div class="row justify-content-center">
                    <div class="col-md-4">
                        <div class="p-3">
                            <i class="bi bi-whatsapp" style="font-size: 2rem; color: #25D366;"></i>
                            <h5 class="mt-2">WhatsApp</h5>
                            <a href="https://wa.me/6281234567890" class="text-decoration-none">+62 812-3456-7890</a>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3">
                            <i class="bi bi-envelope" style="font-size: 2rem; color: var(--primary-color);"></i>
                            <h5 class="mt-2">Email</h5>
                            <a href="mailto:kurir@dailycup.com" class="text-decoration-none">kurir@dailycup.com</a>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3">
                            <i class="bi bi-telephone" style="font-size: 2rem; color: var(--primary-color);"></i>
                            <h5 class="mt-2">Telepon</h5>
                            <a href="tel:02112345678" class="text-decoration-none">(021) 1234-5678</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p class="mb-2">&copy; 2026 DailyCup Coffee. All rights reserved.</p>
            <p class="mb-0">
                <a href="<?php echo SITE_URL; ?>" class="text-white text-decoration-none">Kembali ke Website Utama</a> |
                <a href="login.php" class="text-white text-decoration-none">Login Kurir</a>
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
