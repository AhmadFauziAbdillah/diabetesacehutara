<?php
/**
 * Public Index Page
 * 
 * Halaman utama publik aplikasi Clustering Diabetes.
 * Menampilkan visualisasi data diabetes dan hasil clustering.
 */
declare(strict_types=1);

require_once 'config/database.php';

// Mulai sesi untuk mengakses pengaturan pengguna jika tersedia
session_start();

// Dapatkan pengaturan pengguna dari sesi jika tersedia (untuk pengguna admin)
$show_chart_index = true; // Nilai default
if (isset($_SESSION['user_settings']['show_chart_index'])) {
    $show_chart_index = (bool)$_SESSION['user_settings']['show_chart_index'];
}

// Dapatkan tahun yang dipilih (default ke tahun terbaru jika tidak diatur)
$selected_year = $_GET['year'] ?? null;

// Ambil tahun yang tersedia
$stmt = $pdo->query("SELECT DISTINCT tahun FROM diabetes_data ORDER BY tahun DESC");
$years = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Jika tidak ada tahun yang dipilih, gunakan tahun terbaru
if (!$selected_year && !empty($years)) {
    $selected_year = $years[0];
}

// Ambil data wilayah untuk grafik dan tabel
$stmt = $pdo->prepare("SELECT * FROM diabetes_data WHERE tahun = ? ORDER BY jumlah_penderita DESC");
$stmt->execute([$selected_year]);
$region_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Dapatkan 10 wilayah teratas untuk grafik
$chart_data = array_slice($region_data, 0, 10);

// Hitung statistik
$total_penduduk = array_sum(array_column($region_data, 'jumlah_penduduk'));
$total_penderita = array_sum(array_column($region_data, 'jumlah_penderita'));
$total_kematian = array_sum(array_column($region_data, 'jumlah_kematian'));

// Hitung rata-rata
$region_count = count($region_data);
$avg_penduduk = $region_count > 0 ? $total_penduduk / $region_count : 0;
$avg_penderita = $region_count > 0 ? $total_penderita / $region_count : 0;
$avg_kematian = $region_count > 0 ? $total_kematian / $region_count : 0;

// Hitung tingkat kematian sebagai persentase pasien
$mortality_rate = $total_penderita > 0 ? ($total_kematian / $total_penderita) * 100 : 0;

/**
 * Fungsi untuk menentukan kategori risiko diabetes berdasarkan jumlah penderita
 * dan tingkat kematian
 * 
 * @param int $jumlah_penderita Jumlah penderita diabetes
 * @param int $jumlah_kematian Jumlah kematian karena diabetes
 * @param int $jumlah_penduduk Jumlah penduduk wilayah
 * @return array Array dengan kategori dan kelas warna CSS
 */
function getKategoriDiabetes(int $jumlah_penderita, int $jumlah_kematian, int $jumlah_penduduk): array {
    // Menghitung rasio penderita per 1000 penduduk
    $rasio_penderita = ($jumlah_penduduk > 0) ? ($jumlah_penderita / $jumlah_penduduk) * 1000 : 0;
    
    // Menghitung rasio kematian per penderita (%)
    $rasio_kematian = ($jumlah_penderita > 0) ? ($jumlah_kematian / $jumlah_penderita) * 100 : 0;
    
    // Kategori berdasarkan jumlah absolut penderita
    if ($jumlah_penderita >= 300 || ($jumlah_penderita >= 200 && $jumlah_kematian >= 10)) {
        return [
            'kategori' => 'Tinggi',
            'color' => 'bg-danger'
        ];
    } elseif ($jumlah_penderita >= 20 || ($rasio_penderita >= 5 && $jumlah_penderita >= 10)) {
        return [
            'kategori' => 'Sedang',
            'color' => 'bg-warning'
        ];
    } else {
        return [
            'kategori' => 'Rendah',
            'color' => 'bg-success'
        ];
    }
}

// Hitung statistik level dengan fungsi kategorisasi baru
$level_counts = [
    'Rendah' => 0, 
    'Sedang' => 0, 
    'Tinggi' => 0, 
    'Tidak Terdefinisi' => 0
];

foreach ($region_data as $row) {
    $kategori_info = getKategoriDiabetes(
        (int)$row['jumlah_penderita'], 
        (int)$row['jumlah_kematian'], 
        (int)$row['jumlah_penduduk']
    );
    $level_counts[$kategori_info['kategori']]++;
}

$show_map_index = true; // Nilai default
if (isset($_SESSION['user_settings']['show_map_index'])) {
    $show_map_index = (bool)$_SESSION['user_settings']['show_map_index'];
}

$map_default_zoom = $_SESSION['user_settings']['map_default_zoom'] ?? 5;
$map_default_center = $_SESSION['user_settings']['map_default_center'] ?? '-2.5, 118';
$map_default_color = $_SESSION['user_settings']['map_default_color'] ?? 'penderita';

// Muat handler bahasa
require_once 'includes/language.php';

// Dapatkan bahasa saat ini
$currentLang = $_SESSION['language'] ?? 'id';
$lang = loadLanguage($currentLang);

// Periksa apakah file geojson ada
$geojson_exists = file_exists('data/aceh_regions.geojson');

include 'includes/header.php';
?>

<!-- Style yang diperlukan untuk peta -->
<style>
.map-legend {
    margin-top: 10px;
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
}

.legend-item {
    display: inline-block;
    width: 20px;
    height: 20px;
    border-radius: 3px;
    margin-right: 5px;
    border: 1px solid rgba(0,0,0,0.2);
}

.map-controls {
    width: 220px;
}

.leaflet-popup-content {
    margin: 10px;
}

.leaflet-popup-content p {
    margin: 5px 0;
}

@media (max-width: 768px) {
    .map-legend {
        flex-direction: column;
        align-items: center;
    }
    
    .map-legend div {
        margin-bottom: 5px;
    }
}
</style>

<div class="container-fluid py-4">
    <!-- Bagian Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 fade-in">
        <h1 class="h2">Dashboard Statistik Diabetes <?= $selected_year ?></h1>
        <form method="GET" class="d-flex align-items-center">
            <select name="year" class="year-select" onchange="this.form.submit()">
                <?php foreach ($years as $year): ?>
                    <option value="<?= $year ?>" <?= $year == $selected_year ? 'selected' : '' ?>>
                        <?= $year ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <!-- Kartu Statistik -->
    <div class="row fade-in">
        <!-- Kartu Total Penduduk -->
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="dashboard-card">
                <div class="d-flex align-items-center mb-2">
                    <div class="stat-icon bg-primary bg-opacity-10">
                        <i class="bi bi-people-fill text-primary"></i>
                    </div>
                    <div class="ms-3">
                        <div class="text-muted small">Total Penduduk</div>
                        <div class="stat-value text-primary"><?= number_format($total_penduduk) ?></div>
                    </div>
                </div>
                <div class="progress" style="height: 4px;">
                    <div class="progress-bar bg-primary" style="width: 100%"></div>
                </div>
            </div>
        </div>
        
        <!-- Kartu Total Penderita -->
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="dashboard-card">
                <div class="d-flex align-items-center mb-2">
                    <div class="stat-icon bg-info bg-opacity-10">
                        <i class="bi bi-heart-pulse-fill text-info"></i>
                    </div>
                    <div class="ms-3">
                        <div class="text-muted small">Total Penderita</div>
                        <div class="stat-value text-info"><?= number_format($total_penderita) ?></div>
                    </div>
                </div>
                <div class="small text-muted">Rata-rata: <?= number_format($avg_penderita, 1) ?> per wilayah</div>
                <div class="progress" style="height: 4px;">
                    <div class="progress-bar bg-info" style="width: 100%"></div>
                </div>
            </div>
        </div>
        
        <!-- Kartu Kematian -->
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="dashboard-card">
                <div class="d-flex align-items-center mb-2">
                    <div class="stat-icon bg-danger bg-opacity-10">
                        <i class="bi bi-heart-fill text-danger"></i>
                    </div>
                    <div class="ms-3">
                        <div class="text-muted small">Total Kematian</div>
                        <div class="stat-value text-danger"><?= number_format($total_kematian) ?></div>
                    </div>
                </div>
                <div class="small text-muted">Tingkat Kematian: <?= number_format($mortality_rate, 1) ?>%</div>
                <div class="progress" style="height: 4px;">
                    <div class="progress-bar bg-danger" style="width: <?= min(100, $mortality_rate * 5) ?>%"></div>
                </div>
            </div>
        </div>
        
        <!-- Kartu Distribusi Kluster -->
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="dashboard-card">
                <div class="d-flex align-items-center mb-2">
                    <div class="stat-icon bg-success bg-opacity-10">
                        <i class="bi bi-diagram-3 text-success"></i>
                    </div>
                    <div class="ms-3">
                        <div class="text-muted small">Distribusi Kluster</div>
                        <div class="stat-value text-success"><?= array_sum($level_counts) ?> Total</div>
                    </div>
                </div>
                <div class="progress" style="height: 12px; border-radius: 6px;">
                    <?php 
                    $total = array_sum($level_counts);
                    if ($total > 0):
                        $low_pct = ($level_counts['Rendah'] / $total) * 100;
                        $med_pct = ($level_counts['Sedang'] / $total) * 100;
                        $high_pct = ($level_counts['Tinggi'] / $total) * 100;
                    ?>
                        <div class="progress-bar bg-success" style="width: <?= $low_pct ?>%" title="Rendah: <?= $level_counts['Rendah'] ?>"></div>
                        <div class="progress-bar bg-warning" style="width: <?= $med_pct ?>%" title="Sedang: <?= $level_counts['Sedang'] ?>"></div>
                        <div class="progress-bar bg-danger" style="width: <?= $high_pct ?>%" title="Tinggi: <?= $level_counts['Tinggi'] ?>"></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($show_chart_index): ?>
    <!-- Bagian Grafik - Hanya ditampilkan jika pengaturan diaktifkan -->
    <div class="row fade-in">
        <div class="col-lg-8">
            <div class="dashboard-card">
                <h5 class="mb-4">Distribusi Penderita per Wilayah</h5>
                <div class="chart-wrapper">
                    <canvas id="regionChart" height="350"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="dashboard-card">
                <h5 class="mb-4">Statistik Level</h5>
                <?php foreach ($level_counts as $level => $count): 
                    if ($level === 'Tidak Terdefinisi' && $count === 0) continue;
                    
                    $percentage = ($count / array_sum($level_counts)) * 100;
                    switch($level) {
                        case 'Rendah':
                            $color = 'success';
                            break;
                        case 'Sedang':
                            $color = 'warning';
                            break;
                        case 'Tinggi':
                            $color = 'danger';
                            break;
                        default:
                            $color = 'secondary';
                    }
                ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="status-badge bg-<?= $color ?> bg-opacity-10 text-<?= $color ?>">
                                <?= $level ?>
                            </span>
                            <span class="fw-bold"><?= $count ?></span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-<?= $color ?>" 
                                 role="progressbar" 
                                 style="width: <?= $percentage ?>%" 
                                 aria-valuenow="<?= $percentage ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Pesan ketika grafik dinonaktifkan -->
    <div class="alert alert-info fade-in">
        <i class="bi bi-info-circle me-2"></i>
        Grafik saat ini dinonaktifkan. Anda dapat mengaktifkannya di halaman <strong>Pengaturan</strong>.
    </div>
    <?php endif; ?>
    
    <?php if ($show_map_index): ?>
    <div class="dashboard-card fade-in mt-4">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center mb-4">
            <h5><i class="bi bi-geo-alt-fill me-2"></i>Peta Distribusi Diabetes per Wilayah</h5>
            <div class="map-controls">
                <select id="mapColorBy" class="form-select form-select-sm">
                    <option value="penderita" <?= $map_default_color === 'penderita' ? 'selected' : '' ?>>Berdasarkan Jumlah Penderita</option>
                    <option value="kematian" <?= $map_default_color === 'kematian' ? 'selected' : '' ?>>Berdasarkan Jumlah Kematian</option>
                    <option value="kategori" <?= $map_default_color === 'kategori' ? 'selected' : '' ?>>Berdasarkan Kategori</option>
                </select>
            </div>
        </div>
        
        <?php if ($geojson_exists): ?>
            <div id="regionMap" style="height: 500px; border-radius: 0.5rem; z-index: 1;"></div>
            
            <div class="map-legend mt-3 d-flex justify-content-center">
                <!-- Legenda akan diisi secara dinamis oleh JavaScript -->
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle me-2"></i>
                GeoJSON file tidak ditemukan. Silakan upload file GeoJSON di halaman <a href="/admin/region_coordinates.php" class="alert-link">Koordinat Wilayah</a>.
            </div>
        <?php endif; ?>
    </div>
    <?php elseif (isset($_SESSION['user_id'])): ?>
    <!-- Pesan ketika peta dinonaktifkan tetapi pengguna masuk -->
    <div class="alert alert-info fade-in mt-4">
        <i class="bi bi-info-circle me-2"></i>
        Peta distribusi saat ini dinonaktifkan. Anda dapat mengaktifkannya di halaman <a href="/admin/settings.php" class="alert-link">Pengaturan</a>.
    </div>
    <?php endif; ?>
    
    <!-- Tabel Data -->
    <div class="dashboard-card fade-in mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5><i class="bi bi-table me-2"></i>Data Penderita Diabetes per Wilayah</h5>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Wilayah</th>
                        <th>Jumlah Penduduk</th>
                        <th>Jumlah Penderita</th>
                        <th>Jumlah Kematian</th>
                        <th>Kategori</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($region_data as $row): 
                        $kategori_info = getKategoriDiabetes(
                            (int)$row['jumlah_penderita'], 
                            (int)$row['jumlah_kematian'], 
                            (int)($row['jumlah_penduduk'] ?? 0)
                        );
                    ?>
                    <tr>
                        <td class="fw-medium"><?= htmlspecialchars($row['wilayah']) ?></td>
                        <td><?= number_format((int)($row['jumlah_penduduk'] ?? 0)) ?></td>
                        <td><?= number_format((int)$row['jumlah_penderita']) ?></td>
                        <td><?= number_format((int)$row['jumlah_kematian']) ?></td>
                        <td>
                            <span class="status-badge <?= $kategori_info['color'] ?>">
                                <?= $kategori_info['kategori'] ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($show_chart_index): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('regionChart').getContext('2d');
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($chart_data, 'wilayah')) ?>,
            datasets: [{
                label: 'Jumlah Penderita',
                data: <?= json_encode(array_column($chart_data, 'jumlah_penderita')) ?>,
                backgroundColor: [
                    'rgba(13, 110, 253, 0.6)',  // primary
                    'rgba(220, 53, 69, 0.6)',   // danger
                    'rgba(25, 135, 84, 0.6)',   // success
                    'rgba(255, 193, 7, 0.6)',   // warning
                    'rgba(13, 202, 240, 0.6)',  // info
                    'rgba(111, 66, 193, 0.6)',  // purple
                    'rgba(102, 16, 242, 0.6)',  // indigo
                    'rgba(253, 126, 20, 0.6)',  // orange
                    'rgba(32, 201, 151, 0.6)',  // teal
                    'rgba(214, 51, 132, 0.6)'   // pink
                ],
                borderColor: [
                    'rgb(13, 110, 253)',
                    'rgb(220, 53, 69)',
                    'rgb(25, 135, 84)',
                    'rgb(255, 193, 7)',
                    'rgb(13, 202, 240)',
                    'rgb(111, 66, 193)',
                    'rgb(102, 16, 242)',
                    'rgb(253, 126, 20)',
                    'rgb(32, 201, 151)',
                    'rgb(214, 51, 132)'
                ],
                borderWidth: 1,
                borderRadius: 5,
                barThickness: 30
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: {
                    left: 10,
                    right: 10
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        drawBorder: false,
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat().format(value);
                        },
                        font: {
                            size: 12
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 12
                        },
                        maxRotation: 45,
                        minRotation: 45
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.9)',
                    titleColor: '#000',
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyColor: '#000',
                    bodyFont: {
                        size: 13
                    },
                    borderColor: '#ddd',
                    borderWidth: 1,
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            const dataIndex = context.dataIndex;
                            
                            const pendudukArray = <?= json_encode(array_column($chart_data, 'jumlah_penduduk')) ?>;
                            const penduduk = pendudukArray[dataIndex] ? pendudukArray[dataIndex] : 0;
                            
                            const penderita = context.raw;
                            
                            const kematianArray = <?= json_encode(array_column($chart_data, 'jumlah_kematian')) ?>;
                            const kematian = kematianArray[dataIndex] ? kematianArray[dataIndex] : 0;
                            
                            return [
                                'Jumlah Penduduk: ' + new Intl.NumberFormat().format(penduduk),
                                'Jumlah Penderita: ' + new Intl.NumberFormat().format(penderita),
                                'Jumlah Kematian: ' + new Intl.NumberFormat().format(kematian)
                            ];
                        }
                    }
                }
            }
        }
    });
});
</script>
<?php endif; ?>

<?php if ($show_map_index && $geojson_exists): ?>
<!-- Inisialisasi Peta -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Periksa apakah elemen peta ada (lewati jika tidak)
    const mapElement = document.getElementById('regionMap');
    if (!mapElement) return;
    
    // Parse pusat peta dari pengaturan
    const mapCenter = [<?= $map_default_center ?>];
    const mapZoom = <?= $map_default_zoom ?>;
    
    // Inisialisasi peta yang berpusat pada lokasi yang ditentukan
    const map = L.map('regionMap').setView(mapCenter, mapZoom);
    
    // Tambahkan layer ubin (OpenStreetMap)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 18
    }).addTo(map);
    
    // Dapatkan data wilayah dari PHP
    const regionsData = <?= json_encode($region_data) ?>;
    
    // Variabel global untuk layer GeoJSON
    let geojsonLayer;
    
    // Dapatkan preferensi pewarnaan peta
    let colorBy = document.getElementById('mapColorBy').value;
    
    // Fungsi untuk menentukan kategori
    function getKategoriDiabetes(jumlah_penderita, jumlah_kematian, jumlah_penduduk) {
        // Menghitung rasio penderita per 1000 penduduk
        const rasio_penderita = (jumlah_penduduk > 0) ? (jumlah_penderita / jumlah_penduduk) * 1000 : 0;
        
        // Menghitung rasio kematian per penderita (%)
        const rasio_kematian = (jumlah_penderita > 0) ? (jumlah_kematian / jumlah_penderita) * 100 : 0;
        
        // Kategori berdasarkan jumlah absolut penderita
        if (jumlah_penderita >= 300 || (jumlah_penderita >= 200 && jumlah_kematian >= 10)) {
            return {
                kategori: 'Tinggi',
                color: 'bg-danger'
            };
        } else if (jumlah_penderita >= 20 || (rasio_penderita >= 5 && jumlah_penderita >= 10)) {
            return {
                kategori: 'Sedang',
                color: 'bg-warning'
            };
        } else {
            return {
                kategori: 'Rendah',
                color: 'bg-success'
            };
        }
    }
    
    // Fungsi untuk mendapatkan warna berdasarkan nilai
    function getColor(feature) {
        // Dapatkan data wilayah dari data diabetes
        let regionData = null;
        for (const region of regionsData) {
            // Periksa properti name dan nm_kecamatan
            const featureName = feature.properties.name || feature.properties.nm_kecamatan;
            if (region.wilayah === featureName) {
                regionData = region;
                break;
            }
        }
        
        if (!regionData) return '#cccccc'; // Abu-abu untuk wilayah tanpa data
        
        if (colorBy === 'kategori') {
            // Dapatkan kategori
            const kategoriInfo = getKategoriDiabetes(
                parseInt(regionData.jumlah_penderita), 
                parseInt(regionData.jumlah_kematian), 
                parseInt(regionData.jumlah_penduduk || 0)
            );
            
            // Kembalikan warna berdasarkan kategori
            switch(kategoriInfo.kategori) {
                case 'Rendah': return '#25a244'; // Hijau - risiko rendah
                case 'Sedang': return '#fd8d3c'; // Kuning/oranye - risiko sedang
                case 'Tinggi': return '#bd0026'; // Merah - risiko tinggi
                default: return '#cccccc'; // Abu-abu
            }
        } else {
            // Default ke pewarnaan menurut jumlah pasien ('penderita')
            const value = parseInt(regionData.jumlah_penderita);
            if (value > 500) return '#bd0026';
            if (value > 200) return '#bd0026';
            if (value > 20) return '#fd8d3c';
            if (value > 10) return '#25a244';
            return '#25a244';
        }
    }
    
    // Fungsi untuk menata gaya setiap fitur
    function style(feature) {
        return {
            fillColor: getColor(feature),
            weight: 2,
            opacity: 1,
            color: 'white',
            dashArray: '3',
            fillOpacity: 0.7
        };
    }
    
    // Fungsi untuk menangani peristiwa mouseover
    function highlightFeature(e) {
        const layer = e.target;
        
        layer.setStyle({
            weight: 5,
            color: '#666',
            dashArray: '',
            fillOpacity: 0.9
        });
        
        layer.bringToFront();
    }
    
    // Fungsi untuk mengatur ulang sorotan pada mouseout
    function resetHighlight(e) {
        geojsonLayer.resetStyle(e.target);
    }
    
    // Fungsi untuk memperbesar fitur saat diklik
    function zoomToFeature(e) {
        map.fitBounds(e.target.getBounds());
    }
    
    // Fungsi untuk menambahkan perilaku interaktif ke setiap fitur
    function onEachFeature(feature, layer) {
        // Temukan data wilayah
        let regionData = null;
        for (const region of regionsData) {
            // Periksa properti name dan nm_kecamatan
            const featureName = feature.properties.name || feature.properties.nm_kecamatan;
            if (region.wilayah === featureName) {
                regionData = region;
                break;
            }
        }
        
        if (regionData) {
            // Buat konten popup
            const kategoriInfo = getKategoriDiabetes(
                parseInt(regionData.jumlah_penderita), 
                parseInt(regionData.jumlah_kematian), 
                parseInt(regionData.jumlah_penduduk || 0)
            );
            
            const popupContent = `
                <strong>${feature.properties.name || feature.properties.nm_kecamatan}</strong><br>
                <p>Populasi: ${parseInt(regionData.jumlah_penduduk || 0).toLocaleString()}</p>
                <p>Penderita: ${parseInt(regionData.jumlah_penderita).toLocaleString()}</p>
                <p>Kematian: ${parseInt(regionData.jumlah_kematian).toLocaleString()}</p>
                <p>Kategori: <span class="badge ${kategoriInfo.color}">${kategoriInfo.kategori}</span></p>
            `;
            
            layer.bindPopup(popupContent);
        }
        
        layer.on({
            mouseover: highlightFeature,
            mouseout: resetHighlight,
            click: zoomToFeature
        });
    }
    
    // Fungsi untuk memperbarui peta
    function updateMap() {
        // Hapus layer yang ada
        if (geojsonLayer) {
            map.removeLayer(geojsonLayer);
        }
        
        // Muat dan tambahkan data GeoJSON
        fetch('data/aceh_regions.geojson')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                geojsonLayer = L.geoJSON(data, {
                    style: style,
                    onEachFeature: onEachFeature
                }).addTo(map);
            })
            .catch(error => {
                console.error('Error loading GeoJSON:', error);
                alert('Error loading map data. Please make sure the GeoJSON file exists.');
            });
        
        // Perbarui legenda
        updateLegend();
    }
    
    // Fungsi untuk memperbarui legenda
    function updateLegend() {
        const legendContainer = document.querySelector('.map-legend');
        if (!legendContainer) return;
        
        legendContainer.innerHTML = '';
        
        if (colorBy === 'kategori') {
            // Legenda kategori
            const items = [
                { color: '#25a244', label: 'Hijau (Risiko Rendah)' },
                { color: '#fd8d3c', label: 'Kuning (Risiko Sedang)' },
                { color: '#bd0026', label: 'Merah (Risiko Tinggi)' }
            ];
            
            for (const item of items) {
                const div = document.createElement('div');
                div.className = 'd-flex align-items-center me-4';
                div.innerHTML = `
                    <span class="legend-item" style="background-color: ${item.color};"></span>
                    <span class="small ms-1">${item.label}</span>
                `;
                legendContainer.appendChild(div);
            }
        } else if (colorBy === 'kematian') {
            // Legenda jumlah kematian
            const items = [
                { color: '#25a244', label: 'Hijau (Risiko Rendah)' },
                { color: '#fd8d3c', label: 'Kuning (Risiko Sedang)' },
                { color: '#bd0026', label: 'Merah (Risiko Tinggi)' }
            ];
            
            for (const item of items) {
                const div = document.createElement('div');
                div.className = 'd-flex align-items-center me-4';
                div.innerHTML = `
                    <span class="legend-item" style="background-color: ${item.color};"></span>
                    <span class="small ms-1">${item.label}</span>
                `;
                legendContainer.appendChild(div);
            }
        } else {
            // Legenda jumlah penderita
            const items = [
                { color: '#25a244', label: 'Hijau (Risiko Rendah)' },
                { color: '#fd8d3c', label: 'Kuning (Risiko Sedang)' },
                { color: '#bd0026', label: 'Merah (Risiko Tinggi)' }
            ];
            
            for (const item of items) {
                const div = document.createElement('div');
                div.className = 'd-flex align-items-center me-4';
                div.innerHTML = `
                    <span class="legend-item" style="background-color: ${item.color};"></span>
                    <span class="small ms-1">${item.label}</span>
                `;
                legendContainer.appendChild(div);
            }
        }
    }
    
    // Rendering peta awal
    updateMap();
    
    // Tambahkan event listener untuk perubahan warna
    document.getElementById('mapColorBy').addEventListener('change', function() {
        colorBy = this.value;
        updateMap();
    });
});
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>