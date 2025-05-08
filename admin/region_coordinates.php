<?php
/**
 * Region Coordinates Management Page
 * 
 * This file allows administrators to:
 * 1. Upload a GeoJSON file for region boundaries
 * 2. Manage geographic coordinates for regions manually
 * 3. Preview current region boundaries on a map
 */
declare(strict_types=1);

session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$success_message = '';
$error_message = '';

// Data directory path
$data_dir = '../data';
$geojson_file = $data_dir . '/aceh_regions.geojson';

// Create data directory if it doesn't exist
if (!is_dir($data_dir)) {
    if (!mkdir($data_dir, 0755, true)) {
        $error_message = "Error: Unable to create data directory. Please check permissions.";
    }
}

// Handle manual coordinate management
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_coordinates'])) {
        // Add new coordinates
        $wilayah = trim($_POST['wilayah']);
        $latitude = floatval($_POST['latitude']);
        $longitude = floatval($_POST['longitude']);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO region_coordinates (wilayah, latitude, longitude) 
                                   VALUES (?, ?, ?) 
                                   ON DUPLICATE KEY UPDATE latitude = ?, longitude = ?");
            $stmt->execute([$wilayah, $latitude, $longitude, $latitude, $longitude]);
            $success_message = "Koordinat untuk wilayah \"$wilayah\" berhasil disimpan.";
        } catch (PDOException $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    } elseif (isset($_POST['delete'])) {
        // Delete coordinates
        $id = $_POST['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM region_coordinates WHERE id = ?");
            $stmt->execute([$id]);
            $success_message = "Koordinat berhasil dihapus.";
        } catch (PDOException $e) {
            $error_message = "Error deleting coordinates: " . $e->getMessage();
        }
    } elseif (isset($_POST['import_coordinates'])) {
        // Import coordinates from all regions in diabetes_data
        try {
            // Get all unique regions
            $stmt = $pdo->query("SELECT DISTINCT wilayah FROM diabetes_data 
                                 WHERE wilayah NOT IN (SELECT wilayah FROM region_coordinates)");
            $missing_regions = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $imported = 0;
            
            foreach ($missing_regions as $region) {
                // Generate coordinates within Aceh region (more realistic)
                $lat = mt_rand(4800, 5600) / 1000; // Range: 4.8 to 5.6
                $lng = mt_rand(95000, 97500) / 1000; // Range: 95.0 to 97.5
                
                $stmt = $pdo->prepare("INSERT INTO region_coordinates (wilayah, latitude, longitude) VALUES (?, ?, ?)");
                $stmt->execute([$region, $lat, $lng]);
                $imported++;
            }
            
            if ($imported > 0) {
                $success_message = "Berhasil mengimpor $imported wilayah dengan koordinat sementara. Silakan sesuaikan koordinat yang benar.";
            } else {
                $success_message = "Semua wilayah sudah memiliki koordinat.";
            }
        } catch (PDOException $e) {
            $error_message = "Error importing coordinates: " . $e->getMessage();
        }
    }
}

// Check if GeoJSON file exists
$geojson_exists = file_exists($geojson_file);

// Get regions that don't have coordinates
$stmt = $pdo->query("SELECT DISTINCT d.wilayah 
                     FROM diabetes_data d 
                     LEFT JOIN region_coordinates c ON d.wilayah = c.wilayah 
                     WHERE c.wilayah IS NULL");
$missing_coordinates = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get all coordinates with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

// Search functionality
$search = $_GET['search'] ?? '';

if (!empty($search)) {
    // Search query
    $query = "SELECT * FROM region_coordinates 
              WHERE wilayah LIKE :search 
              ORDER BY wilayah ASC
              LIMIT :offset, :limit";
    
    $stmt = $pdo->prepare($query);
    $search_param = "%$search%";
    $stmt->bindParam(':search', $search_param, PDO::PARAM_STR);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $items_per_page, PDO::PARAM_INT);
    $stmt->execute();
    
    // Get total count for pagination
    $count_query = "SELECT COUNT(*) FROM region_coordinates WHERE wilayah LIKE :search";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->bindParam(':search', $search_param, PDO::PARAM_STR);
    $count_stmt->execute();
    $total_items = $count_stmt->fetchColumn();
} else {
    // Regular query with no search
    $query = "SELECT * FROM region_coordinates ORDER BY wilayah ASC LIMIT :offset, :limit";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $items_per_page, PDO::PARAM_INT);
    $stmt->execute();
    
    // Get total count for pagination
    $count_query = "SELECT COUNT(*) FROM region_coordinates";
    $count_stmt = $pdo->query($count_query);
    $total_items = $count_stmt->fetchColumn();
}

$coordinates = $stmt->fetchAll();
$total_pages = ceil($total_items / $items_per_page);

// Include header and sidebar
include '../includes/header.php';

/**
 * Generate page URL for pagination
 * 
 * @param int $pageNum The page number
 * @return string URL with page parameter
 */
function pageUrl(int $pageNum): string {
    global $search;
    $params = ['page' => $pageNum];
    
    if (!empty($search)) {
        $params['search'] = $search;
    }
    
    return "?" . http_build_query($params);
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Kelola Koordinat Wilayah</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <form method="POST" class="me-2">
                        <button type="submit" name="import_coordinates" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-cloud-download me-1"></i> Import Koordinat Wilayah
                        </button>
                    </form>
                </div>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i><?= $success_message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i><?= $error_message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- GeoJSON Upload Section -->
            <div class="row mb-4">
                <div class="col-lg-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="bi bi-file-earmark-code me-2"></i>
                                Upload File GeoJSON
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <form id="geojsonUploadForm" enctype="multipart/form-data">
                                        <div class="mb-3">
                                            <label for="geojson_file" class="form-label">File GeoJSON Wilayah</label>
                                            <input type="file" class="form-control" id="geojson_file" name="geojson_file" 
                                                   accept=".geojson,application/json" required>
                                            <div class="form-text">Upload file GeoJSON yang berisi data poligon wilayah.</div>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-upload me-2"></i>Upload GeoJSON
                                        </button>
                                    </form>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card bg-light h-100">
                                        <div class="card-body">
                                            <h6 class="card-subtitle mb-2 text-muted">Status GeoJSON</h6>
                                            <?php if ($geojson_exists): ?>
                                                <div class="alert alert-success mb-2">
                                                    <i class="bi bi-check-circle me-2"></i>
                                                    File GeoJSON telah di-upload.
                                                </div>
                                                <p class="small mb-0">
                                                    <strong>Lokasi File:</strong> <?= $geojson_file ?><br>
                                                    <strong>Terakhir Diperbarui:</strong> <?= date("d M Y H:i", filemtime($geojson_file)) ?>
                                                </p>
                                            <?php else: ?>
                                                <div class="alert alert-warning mb-2">
                                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                                    Belum ada file GeoJSON yang di-upload.
                                                </div>
                                                <p class="small mb-0">
                                                    Upload file GeoJSON untuk menampilkan peta poligon yang akurat.
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <h6 class="text-muted">Format GeoJSON yang Kompatibel:</h6>
                                <div class="bg-light p-3 rounded" style="font-family: monospace; font-size: 0.85rem;">
<pre>{
  "type": "FeatureCollection",
  "features": [
    {
      "type": "Feature",
      "properties": {
        <strong class="text-success">"name": "Baktiya",</strong>  // Diperlukan untuk menyamakan dengan wilayah di database
        "kd_propinsi": "11",      // Properti lain boleh ada
        "kd_dati2": "08",
        "kd_kecamatan": "001",
        "nm_kecamatan": "Baktiya"  // Akan otomatis digunakan jika name tidak ada
      },
      "geometry": {
        "type": "Polygon", // atau "MultiPolygon"
        "coordinates": [...]
      }
    },
    // Feature wilayah lainnya...
  ]
}</pre>
                                </div>
                                <p class="small text-muted mt-2">
                                    <strong>Catatan:</strong> Sistem akan otomatis mengkonversi properti <code>nm_kecamatan</code> menjadi <code>name</code> jika diperlukan.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Missing Coordinates Warning -->
            <?php if (!empty($missing_coordinates)): ?>
                <div class="alert alert-warning" role="alert">
                    <h5 class="alert-heading"><i class="bi bi-exclamation-triangle me-2"></i>Wilayah Tanpa Koordinat</h5>
                    <p>Beberapa wilayah tidak memiliki koordinat dan tidak akan tampil di peta:</p>
                    <ul class="mb-0">
                        <?php foreach (array_slice($missing_coordinates, 0, 5) as $region): ?>
                            <li><?= htmlspecialchars($region) ?></li>
                        <?php endforeach; ?>
                        <?php if (count($missing_coordinates) > 5): ?>
                            <li>... dan <?= count($missing_coordinates) - 5 ?> wilayah lainnya</li>
                        <?php endif; ?>
                    </ul>
                    <hr>
                    <p class="mb-0">Upload file GeoJSON atau gunakan tombol "Import Koordinat Wilayah" untuk menambahkan koordinat sementara untuk semua wilayah.</p>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Add Coordinates Form -->
                <div class="col-lg-4 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="bi bi-pin-map me-2"></i>
                                Tambah/Edit Koordinat Manual
                            </h5>
                            
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="wilayah" class="form-label">Wilayah</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-geo-alt"></i>
                                        </span>
                                        <input type="text" class="form-control" id="wilayah" name="wilayah" required
                                               placeholder="Nama wilayah">
                                    </div>
                                    <div class="form-text">Masukkan nama wilayah yang sama persis dengan data diabetes.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="latitude" class="form-label">Latitude</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-compass"></i>
                                        </span>
                                        <input type="number" step="0.000001" class="form-control" id="latitude" 
                                               name="latitude" required placeholder="5.138">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="longitude" class="form-label">Longitude</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-compass"></i>
                                        </span>
                                        <input type="number" step="0.000001" class="form-control" id="longitude" 
                                               name="longitude" required placeholder="97.132">
                                    </div>
                                </div>
                                
                                <button type="submit" name="add_coordinates" class="btn btn-primary w-100">
                                    <i class="bi bi-save me-2"></i>Simpan Koordinat
                                </button>
                            </form>
                            
                            <div class="mt-4">
                                <div class="alert alert-info">
                                    <h6 class="alert-heading">Mencari Koordinat:</h6>
                                    <p class="small mb-1">Anda dapat mencari koordinat latitude/longitude dengan cara:</p>
                                    <ul class="small">
                                        <li>Gunakan Google Maps, klik kanan lokasi dan pilih "What's here?"</li>
                                        <li>Gunakan layanan seperti <a href="https://www.latlong.net/" target="_blank">LatLong.net</a></li>
                                        <li>Untuk Aceh, koordinat Latitude berkisar 2 hingga 6</li>
                                        <li>Untuk Aceh, koordinat Longitude berkisar 95 hingga 98</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Coordinates List -->
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="bi bi-list-ul me-2"></i>
                                Daftar Koordinat Wilayah Manual
                            </h5>
                            
                            <!-- Search Box -->
                            <div class="mb-3">
                                <form method="GET" class="row g-2">
                                    <div class="col">
                                        <div class="input-group">
                                            <input type="text" class="form-control" placeholder="Cari wilayah..." 
                                                   name="search" value="<?= htmlspecialchars($search) ?>">
                                            <button class="btn btn-primary" type="submit">
                                                <i class="bi bi-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <?php if (!empty($search)): ?>
                                    <div class="col-auto">
                                        <a href="region_coordinates.php" class="btn btn-outline-secondary">
                                            <i class="bi bi-x-circle me-1"></i> Reset
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </form>
                            </div>
                            
                            <!-- Coordinates Table -->
                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Wilayah</th>
                                            <th>Latitude</th>
                                            <th>Longitude</th>
                                            <th>Terakhir Diperbarui</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($coordinates) > 0): ?>
                                            <?php foreach ($coordinates as $coord): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($coord['wilayah']) ?></td>
                                                    <td><?= number_format((float)$coord['latitude'], 6) ?></td>
                                                    <td><?= number_format((float)$coord['longitude'], 6) ?></td>
                                                    <td><?= date('d M Y H:i', strtotime($coord['updated_at'])) ?></td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button type="button" class="btn btn-sm btn-primary edit-btn"
                                                                    data-wilayah="<?= htmlspecialchars($coord['wilayah']) ?>"
                                                                    data-lat="<?= $coord['latitude'] ?>"
                                                                    data-lng="<?= $coord['longitude'] ?>">
                                                                <i class="bi bi-pencil"></i>
                                                            </button>
                                                            <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus koordinat untuk <?= htmlspecialchars($coord['wilayah']) ?>?');" style="display:inline;">
                                                                <input type="hidden" name="id" value="<?= $coord['id'] ?>">
                                                                <button type="submit" name="delete" class="btn btn-sm btn-danger">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-4">
                                                    <div class="text-muted">
                                                        <i class="bi bi-geo-alt display-6 d-block mb-3"></i>
                                                        <p>Tidak ada data koordinat wilayah manual.</p>
                                                        <p>Silakan tambahkan koordinat atau upload file GeoJSON.</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <nav aria-label="Page navigation" class="mt-4">
                                    <ul class="pagination justify-content-center">
                                        <li class="page-item<?= $page <= 1 ? ' disabled' : '' ?>">
                                            <a class="page-link" href="<?= $page > 1 ? pageUrl($page - 1) : '#' ?>" tabindex="-1">
                                                <i class="bi bi-chevron-left"></i> Previous
                                            </a>
                                        </li>
                                        
                                        <?php 
                                        $start_page = max(1, min($page - 2, $total_pages - 4));
                                        $end_page = min($total_pages, max($page + 2, 5));
                                        
                                        for ($i = $start_page; $i <= $end_page; $i++): 
                                        ?>
                                            <li class="page-item<?= $i == $page ? ' active' : '' ?>">
                                                <a class="page-link" href="<?= pageUrl($i) ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <li class="page-item<?= $page >= $total_pages ? ' disabled' : '' ?>">
                                            <a class="page-link" href="<?= $page < $total_pages ? pageUrl($page + 1) : '#' ?>">
                                                Next <i class="bi bi-chevron-right"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Map Preview Section (if GeoJSON exists) -->
            <?php if ($geojson_exists): ?>
            <div class="card shadow-sm mt-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="bi bi-map me-2"></i>
                        Preview GeoJSON Map
                    </h5>
                    
                    <div id="previewMap" style="height: 400px; border-radius: 0.5rem;"></div>
                    
                    <div class="mt-3 text-center">
                        <p class="small text-muted">
                            Preview menampilkan bentuk wilayah dari file GeoJSON yang telah di-upload.
                            Warna wilayah akan sesuai dengan data diabetes setelah dimuat di halaman utama.
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Leaflet JS for preview map -->
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
            <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
            
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize preview map
                const map = L.map('previewMap').setView([5.2, 97.0], 9); // Center on Aceh
                
                // Add tile layer
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);
                
                // Load and display GeoJSON
                fetch('/data/aceh_regions.geojson?v=' + new Date().getTime())
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Add GeoJSON to map with simple styling
                        const geojsonLayer = L.geoJSON(data, {
                            style: function() {
                                return {
                                    fillColor: '#3388ff',
                                    weight: 2,
                                    opacity: 1,
                                    color: 'white',
                                    dashArray: '3',
                                    fillOpacity: 0.5
                                };
                            },
                            onEachFeature: function(feature, layer) {
                                // Try to get name from different properties
                                const name = feature.properties.name || 
                                          feature.properties.nm_kecamatan || 
                                          'Unnamed Region';
                                          
                                layer.bindPopup('<strong>' + name + '</strong>');
                                
                                layer.on({
                                    mouseover: function(e) {
                                        const layer = e.target;
                                        layer.setStyle({
                                            weight: 5,
                                            color: '#666',
                                            dashArray: '',
                                            fillOpacity: 0.7
                                        });
                                    },
                                    mouseout: function(e) {
                                        geojsonLayer.resetStyle(e.target);
                                    },
                                    click: function(e) {
                                        map.fitBounds(e.target.getBounds());
                                    }
                                });
                            }
                        }).addTo(map);
                        
                        // Fit map to GeoJSON bounds
                        map.fitBounds(geojsonLayer.getBounds());
                    })
                    .catch(error => {
                        console.error('Error loading GeoJSON:', error);
                    });
            });
            </script>
            <?php endif; ?>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle GeoJSON Upload with AJAX
    const uploadForm = document.getElementById('geojsonUploadForm');
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            // Show loading indicator
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Uploading...';
            
            fetch('upload_geojson.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Create success alert
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-success alert-dismissible fade show mt-3';
                    alertDiv.innerHTML = `
                        <i class="bi bi-check-circle me-2"></i>${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    `;
                    
                    // Add stats if available
                    if (data.stats) {
                        let statsHTML = `
                        <div class="mt-2">
                            <p class="mb-1">Matched regions: ${data.stats.matched}</p>
                            <p class="mb-1">Unmatched regions in GeoJSON: ${data.stats.unmatched}</p>
                            <p class="mb-1">Regions in database not in GeoJSON: ${data.stats.missing}</p>
                        `;
                        
                        if (data.stats.unmatched > 0 && data.stats.unmatched_list.length > 0) {
                            statsHTML += `<p class="mb-1"><strong>Unmatched examples:</strong> ${data.stats.unmatched_list.join(", ")}</p>`;
                        }
                        
                        if (data.stats.missing > 0 && data.stats.missing_list.length > 0) {
                            statsHTML += `<p class="mb-1"><strong>Missing examples:</strong> ${data.stats.missing_list.join(", ")}</p>`;
                        }
                        
                        statsHTML += '</div>';
                        alertDiv.innerHTML += statsHTML;
                    }
                    
                    uploadForm.insertAdjacentElement('afterend', alertDiv);
                    
                    // Refresh the page after 3 seconds to show the updated status
                    setTimeout(() => {
                        window.location.reload();
                    }, 3000);
                } else {
                    // Create error alert
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-danger alert-dismissible fade show mt-3';
                    alertDiv.innerHTML = `
                        <i class="bi bi-exclamation-triangle me-2"></i>${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    `;
                    uploadForm.insertAdjacentElement('afterend', alertDiv);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                
                // Create error alert
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger alert-dismissible fade show mt-3';
                alertDiv.innerHTML = `
                    <i class="bi bi-exclamation-triangle me-2"></i>An error occurred during upload. Please try again.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                uploadForm.insertAdjacentElement('afterend', alertDiv);
            })
            .finally(() => {
                // Reset button
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            });
        });
    }
    
    // Fill form with data for editing
    const editButtons = document.querySelectorAll('.edit-btn');
    
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const wilayah = this.getAttribute('data-wilayah');
            const lat = this.getAttribute('data-lat');
            const lng = this.getAttribute('data-lng');
            
            document.getElementById('wilayah').value = wilayah;
            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;
            
            // Scroll to the form
            document.querySelector('.card-title').scrollIntoView({ behavior: 'smooth' });
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>