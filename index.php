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

// Calculate level statistics with cluster categorization
$level_counts = ['Rendah' => 0, 'Sedang' => 0, 'Tinggi' => 0, 'Tidak Terdefinisi' => 0];
foreach ($region_data as $row) {
    $category = getCategory(isset($row['cluster']) ? $row['cluster'] : null)['category'];
    $level_counts[$category]++;
}

/**
 * Maps cluster IDs to categories and colors
 *
 * @param int|null $cluster The cluster ID
 * @return array Array with category name and color class
 */
function getCategory($cluster) {
    $cluster = (int)$cluster;
    switch ($cluster) {
        case 0:
            return ['category' => 'Rendah', 'color' => 'bg-success'];
        case 1:
            return ['category' => 'Sedang', 'color' => 'bg-warning'];
        case 2:
            return ['category' => 'Tinggi', 'color' => 'bg-danger'];
        default:
            return ['category' => 'Tidak Terdefinisi', 'color' => 'bg-secondary'];
    }
}

include 'includes/header.php';
?>

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
                        $cat_info = getCategory(isset($row['cluster']) ? $row['cluster'] : null);
                    ?>
                    <tr>
                        <td class="fw-medium"><?= htmlspecialchars($row['wilayah']) ?></td>
                        <td><?= number_format($row['jumlah_penduduk'] ?? 0) ?></td>
                        <td><?= number_format($row['jumlah_penderita']) ?></td>
                        <td><?= number_format($row['jumlah_kematian']) ?></td>
                        <td>
                            <span class="status-badge <?= $cat_info['color'] ?>">
                                <?= $cat_info['category'] ?>
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

<?php include 'includes/footer.php'; ?>