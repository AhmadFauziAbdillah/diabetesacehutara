<?php
/**
 * Public Index Page
 * 
 * This is the main public-facing page of the Diabetes Clustering application.
 * It displays visualizations of diabetes data and clustering results.
 */
require_once 'config/database.php';

// Start session to access user settings if available
session_start();

// Get user settings from session if available (for admin users)
$show_chart_index = true; // Default value
if (isset($_SESSION['user_settings']) && isset($_SESSION['user_settings']['show_chart_index'])) {
    $show_chart_index = (bool)$_SESSION['user_settings']['show_chart_index'];
}

// Get the selected year (default to the latest year if not set)
$selected_year = isset($_GET['year']) ? $_GET['year'] : null;

// Fetch available years
$stmt = $pdo->query("SELECT DISTINCT tahun FROM diabetes_data ORDER BY tahun DESC");
$years = $stmt->fetchAll(PDO::FETCH_COLUMN);

// If no year is selected, use the latest year
if (!$selected_year && !empty($years)) {
    $selected_year = $years[0];
}

// Fetch region data for chart and table
$stmt = $pdo->prepare("SELECT * FROM diabetes_data WHERE tahun = ? ORDER BY jumlah_penderita DESC");
$stmt->execute([$selected_year]);
$region_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get top 10 regions for the chart
$chart_data = array_slice($region_data, 0, 10);

// Calculate statistics
$total_penduduk = array_sum(array_column($region_data, 'jumlah_penduduk'));
$total_penderita = array_sum(array_column($region_data, 'jumlah_penderita'));
$total_kematian = array_sum(array_column($region_data, 'jumlah_kematian'));

// Calculate averages
$region_count = count($region_data);
$avg_penduduk = $region_count > 0 ? $total_penduduk / $region_count : 0;
$avg_penderita = $region_count > 0 ? $total_penderita / $region_count : 0;
$avg_kematian = $region_count > 0 ? $total_kematian / $region_count : 0;

// Calculate mortality rate as percentage of patients
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
function getKategoriDiabetes($jumlah_penderita, $jumlah_kematian, $jumlah_penduduk) {
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

// Calculate level statistics with new categorization function
$level_counts = ['Rendah' => 0, 'Sedang' => 0, 'Tinggi' => 0, 'Tidak Terdefinisi' => 0];
foreach ($region_data as $row) {
    $kategori_info = getKategoriDiabetes(
        $row['jumlah_penderita'], 
        $row['jumlah_kematian'], 
        $row['jumlah_penduduk']
    );
    $level_counts[$kategori_info['kategori']]++;
}

$show_map_index = true; // Default value
if (isset($_SESSION['user_settings']) && isset($_SESSION['user_settings']['show_map_index'])) {
    $show_map_index = (bool)$_SESSION['user_settings']['show_map_index'];
}

$map_default_zoom = isset($_SESSION['user_settings']['map_default_zoom']) ? $_SESSION['user_settings']['map_default_zoom'] : 5;
$map_default_center = isset($_SESSION['user_settings']['map_default_center']) ? $_SESSION['user_settings']['map_default_center'] : '-2.5, 118';
$map_default_color = isset($_SESSION['user_settings']['map_default_color']) ? $_SESSION['user_settings']['map_default_color'] : 'penderita';

// Fetch region coordinates
$stmt = $pdo->query("SELECT wilayah, latitude, longitude FROM region_coordinates");
$region_coordinates = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $region_coordinates[$row['wilayah']] = [$row['latitude'], $row['longitude']];
}

include 'includes/header.php';
?>

<?php if ($show_map_index): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
<style>
.map-legend {
    margin-top: 10px;
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
</style>
<?php endif; ?>

<div class="container-fluid py-4">
    <!-- Header Section -->
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

    <!-- Statistics Cards -->
    <div class="row fade-in">
        <!-- Total Population Card -->
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
        
        <!-- Total Patients Card -->
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
                <div class="small text-muted">Average: <?= number_format($avg_penderita, 1) ?> per region</div>
                <div class="progress" style="height: 4px;">
                    <div class="progress-bar bg-info" style="width: 100%"></div>
                </div>
            </div>
        </div>
        
        <!-- Mortality Card -->
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
        
        <!-- Cluster Distribution Card -->
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="dashboard-card">
                <div class="d-flex align-items-center mb-2">
                    <div class="stat-icon bg-success bg-opacity-10">
                        <i class="bi bi-diagram-3 text-success"></i>
                    </div>
                    <div class="ms-3">
                        <div class="text-muted small">Cluster Distribution</div>
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
    <!-- Charts Section - Only shown if setting is enabled -->
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
                    $color = '';
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
    <!-- Message when charts are disabled -->
    <div class="alert alert-info fade-in">
        <i class="bi bi-info-circle me-2"></i>
        Charts are currently disabled. You can enable them in the <strong>Settings</strong> page.
    </div>
    <?php endif; ?>
    
    <?php if ($show_map_index): ?>
    <div class="dashboard-card fade-in mt-4">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center mb-4">
            <h5><i class="bi bi-geo-alt-fill me-2"></i>Peta Distribusi Diabetes per Wilayah</h5>
            <div class="map-controls">
                <select id="mapColorBy" class="form-select form-select-sm">
                    <option value="penderita" <?php echo $map_default_color === 'penderita' ? 'selected' : ''; ?>>Berdasarkan Jumlah Penderita</option>
                    <option value="kematian" <?php echo $map_default_color === 'kematian' ? 'selected' : ''; ?>>Berdasarkan Jumlah Kematian</option>
                    <option value="kategori" <?php echo $map_default_color === 'kategori' ? 'selected' : ''; ?>>Berdasarkan Kategori</option>
                </select>
            </div>
        </div>
        
        <div id="regionMap" style="height: 500px; border-radius: 0.5rem; z-index: 1;"></div>
        
        <div class="map-legend mt-3 d-flex justify-content-center">
            <div class="d-flex align-items-center me-4">
                <span class="legend-item" style="background-color: #25a244;"></span>
                <span class="small ms-1">Rendah</span>
            </div>
            <div class="d-flex align-items-center me-4">
                <span class="legend-item" style="background-color: #fd8d3c;"></span>
                <span class="small ms-1">Sedang</span>
            </div>
            <div class="d-flex align-items-center">
                <span class="legend-item" style="background-color: #bd0026;"></span>
                <span class="small ms-1">Tinggi</span>
            </div>
        </div>
    </div>
    <?php elseif (isset($_SESSION['user_id'])): ?>
    <!-- Message when map is disabled but user is logged in -->
    <div class="alert alert-info fade-in mt-4">
        <i class="bi bi-info-circle me-2"></i>
        Peta distribusi saat ini dinonaktifkan. Anda dapat mengaktifkannya di halaman <a href="/admin/settings.php" class="alert-link">Pengaturan</a>.
    </div>
    <?php endif; ?>
    
    <!-- Data Table -->
    <div class="dashboard-card fade-in mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5><i class="bi bi-table me-2"></i>Data Penderita Diabetes per Wilayah</h5>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover custom-table">
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
                            $row['jumlah_penderita'], 
                            $row['jumlah_kematian'], 
                            $row['jumlah_penduduk']
                        );
                    ?>
                    <tr>
                        <td class="fw-medium"><?= htmlspecialchars($row['wilayah']) ?></td>
                        <td><?= number_format($row['jumlah_penduduk'] ?? 0) ?></td>
                        <td><?= number_format($row['jumlah_penderita']) ?></td>
                        <td><?= number_format($row['jumlah_kematian']) ?></td>
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
                            const penduduk = <?= json_encode(array_column($chart_data, 'jumlah_penduduk')) ?>[dataIndex] || 0;
                            const penderita = context.raw;
                            const kematian = <?= json_encode(array_column($chart_data, 'jumlah_kematian')) ?>[dataIndex];
                            
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

<?php if ($show_map_index): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check if map element exists (skip if not)
    const mapElement = document.getElementById('regionMap');
    if (!mapElement) return;
    
    // Parse map center from settings
    const mapCenter = [<?php echo $map_default_center; ?>];
    const mapZoom = <?php echo $map_default_zoom; ?>;
    
    // Initialize map centered on defined location
    const map = L.map('regionMap').setView(mapCenter, mapZoom);
    
    // Add tile layer (OpenStreetMap)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 18
    }).addTo(map);
    
    // Get regions data from PHP
    const regionsData = <?php echo json_encode($region_data); ?>;
    
    // Get region coordinates
    const regionCoordinates = <?php echo json_encode($region_coordinates); ?>;
    
    // Function to get color based on value
    function getColor(value, max, type) {
        // Color scales - from light yellow to dark red
        const colors = ['#ffffcc', '#ffeda0', '#fed976', '#feb24c', '#fd8d3c', '#fc4e2a', '#e31a1c', '#bd0026'];
        
        if (type === 'kategori') {
            // Get kategori
            const kategoriInfo = getKategoriDiabetes(value.jumlah_penderita, value.jumlah_kematian, value.jumlah_penduduk);
            // Return color based on kategori
            switch(kategoriInfo.kategori) {
                case 'Rendah': return '#25a244'; // Success green
                case 'Sedang': return '#fd8d3c'; // Warning orange
                case 'Tinggi': return '#bd0026'; // Danger red
                default: return '#cccccc'; // Gray
            }
        }
        
        // Get value to use for coloring
        let displayValue;
        if (type === 'kematian') {
            displayValue = value.jumlah_kematian;
            max = Math.max(...regionsData.map(item => item.jumlah_kematian));
        } else {
            // Default to penderita
            displayValue = value.jumlah_penderita;
            max = Math.max(...regionsData.map(item => item.jumlah_penderita));
        }
        
        // Linear scale for continuous values
        const ratio = max > 0 ? displayValue / max : 0;
        const index = Math.min(Math.floor(ratio * colors.length), colors.length - 1);
        return colors[index];
    }
    
    // Get map coloring preference
    let colorBy = document.getElementById('mapColorBy').value;
    
    // Calculate max values for normalization
    const maxPenderita = Math.max(...regionsData.map(item => item.jumlah_penderita));
    const maxKematian = Math.max(...regionsData.map(item => item.jumlah_kematian));
    
    // Function to determine kategori
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
    
    // Function to update map markers
    function updateMap() {
        // Clear existing markers
        map.eachLayer(function(layer) {
            if (layer instanceof L.Circle) {
                map.removeLayer(layer);
            }
        });
        
        // Add markers for each region
        regionsData.forEach(function(region) {
            const coordinates = regionCoordinates[region.wilayah];
            if (!coordinates) return; // Skip if coordinates not found
            
            // Determine circle color based on selected metric
            const circleColor = getColor(region, colorBy === 'kematian' ? maxKematian : maxPenderita, colorBy);
            
            // Get kategori for popup display
            const kategoriInfo = getKategoriDiabetes(
                region.jumlah_penderita, 
                region.jumlah_kematian, 
                region.jumlah_penduduk
            );
            
            // Create popup content
            const popupText = `
                <strong>${region.wilayah}</strong><br>
                <p>Populasi: ${parseInt(region.jumlah_penduduk).toLocaleString()}</p>
                <p>Penderita: ${parseInt(region.jumlah_penderita).toLocaleString()}</p>
                <p>Kematian: ${parseInt(region.jumlah_kematian).toLocaleString()}</p>
                <p>Kategori: <span class="badge ${kategoriInfo.color}">${kategoriInfo.kategori}</span></p>
            `;
            
            // Create circle marker - scale radius based on value for visual emphasis
            const radius = Math.sqrt(region.jumlah_penderita) * 1000; // Scale for visibility
            
            L.circle(coordinates, {
                color: circleColor,
                fillColor: circleColor,
                fillOpacity: 0.6,
                radius: radius
            }).bindPopup(popupText).addTo(map);
        });
        
        // Update legend based on coloring method
        updateLegend();
    }
    
    // Function to update the legend
    function updateLegend() {
        const legendContainer = document.querySelector('.map-legend');
        if (!legendContainer) return;
        
        legendContainer.innerHTML = '';
        
        if (colorBy === 'kategori') {
            // Kategori legend
            const items = [
                { color: '#25a244', label: 'Rendah' },
                { color: '#fd8d3c', label: 'Sedang' },
                { color: '#bd0026', label: 'Tinggi' }
            ];
            
            items.forEach(item => {
                const div = document.createElement('div');
                div.className = 'd-flex align-items-center me-4';
                div.innerHTML = `
                    <span class="legend-item" style="background-color: ${item.color};"></span>
                    <span class="small ms-1">${item.label}</span>
                `;
                legendContainer.appendChild(div);
            });
        } else {
            // Gradient legend for numeric values
            const colors = ['#ffffcc', '#fed976', '#fd8d3c', '#e31a1c', '#bd0026'];
            const labels = ['Sangat Rendah', 'Rendah', 'Sedang', 'Tinggi', 'Sangat Tinggi'];
            
            colors.forEach((color, i) => {
                const div = document.createElement('div');
                div.className = 'd-flex align-items-center me-4';
                div.innerHTML = `
                    <span class="legend-item" style="background-color: ${color};"></span>
                    <span class="small ms-1">${labels[i]}</span>
                `;
                legendContainer.appendChild(div);
            });
        }
    }
    
    // Initial map rendering
    updateMap();
    
    // Add event listener for color by change
    document.getElementById('mapColorBy').addEventListener('change', function() {
        colorBy = this.value;
        updateMap();
    });
});
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>