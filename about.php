<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - Diabetes Clustering Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar {
            background-color: #f8f9fa;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .about-section {
            padding: 60px 0;
            background-color: #f8f9fa;
        }
        .team-section {
            padding: 60px 0;
        }
        .footer {
            background-color: #f1f1f1;
            padding: 20px 0;
            text-align: center;
        }
        .social-icons a {
            margin: 0 10px;
            color: #0d6efd;
            font-size: 24px;
        }
        .social-icons a:hover {
            color: #0a58ca;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-grid-3x3-gap-fill me-2"></i>
                Diabetes Clustering Dashboard
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/dashboard.php">Dashboard</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- About Section -->
    <section class="about-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center mb-5">
                    <h2 class="mb-4">Tentang DBSCAN Clustering Analysis for Diabetes Data</h2>
                    <p class="lead">
                        Proyek ini menggunakan algoritma DBSCAN (Density-Based Spatial Clustering of Applications with Noise) untuk menganalisis dan mengelompokkan data diabetes di Aceh Utara.
                    </p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h4 class="card-title">Tujuan Proyek</h4>
                            <p class="card-text">
                                Dashboard ini bertujuan untuk memvisualisasikan dan menganalisis pola persebaran diabetes di wilayah Aceh Utara. 
                                Dengan menggunakan teknik clustering, kami mengidentifikasi area-area dengan tingkat prevalensi diabetes yang tinggi,
                                sehingga dapat membantu pengambilan keputusan dalam intervensi kesehatan masyarakat.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h4 class="card-title">Metode Analisis</h4>
                            <p class="card-text">
                                Kami menggunakan algoritma DBSCAN untuk mengelompokkan data diabetes berdasarkan kepadatan populasi dan faktor-faktor risiko.
                                Pendekatan ini memungkinkan kami untuk mengidentifikasi cluster yang memiliki karakteristik serupa tanpa menentukan jumlah cluster terlebih dahulu,
                                sehingga lebih cocok untuk data geografis dan demografis yang kompleks.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h4 class="card-title">Sumber Data</h4>
                            <p class="card-text">
                                Data yang digunakan dalam analisis ini berasal dari catatan medis yang telah dianonimkan dari RSU Cut Meutia dan RS Prima Medika.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h4 class="card-title">Implikasi dan Manfaat</h4>
                            <p class="card-text">
                                Hasil dari analisis clustering ini dapat membantu pemerintah daerah dan pemangku kepentingan bidang kesehatan untuk:
                                <ul>
                                    <li>Mengidentifikasi wilayah dengan risiko diabetes tinggi</li>
                                    <li>Merencanakan program intervensi kesehatan yang lebih tepat sasaran</li>
                                    <li>Mengalokasikan sumber daya kesehatan secara lebih efisien</li>
                                    <li>Mengevaluasi efektivitas program penanganan diabetes yang telah dilaksanakan</li>
                                </ul>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section (Optional) -->
    <section class="team-section">
        <div class="container">
            <h2 class="text-center mb-5">Tim Pengembang</h2>
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4 mx-auto">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-person-circle" style="font-size: 3rem;"></i>
                            <h5 class="card-title mt-3">Ahmad Fauzi Abdillah</h5>
                            <p class="card-text mb-2">Universitas Malikussaleh</p>
                        </div>
                    </div>
                </div>
                <!-- You can add more team members here if needed -->
            </div>
        </div>
    </section>

  <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="social-icons mb-3">
<a href="https://github.com/AhmadFauziAbdillah/diabetesacehutara" title="GitHub"><i class="bi bi-github"></i></a>
<a href="https://www.instagram.com/faujiabdilah_/" title="Instagram"><i class="bi bi-instagram"></i></a>
                    </div>
                    <p>© 2025 DBSCAN Clustering Analysis for Diabetes Data</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>