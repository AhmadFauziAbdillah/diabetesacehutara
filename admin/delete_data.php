<?php
/**
 * Data Management Page
 * 
 * This file displays all diabetes data records, allows searching, sorting, pagination,
 * and provides links to edit or delete individual records.
 */
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$success_message = '';
$error_message = '';

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

// Function to update all cluster values in the database based on our categorization
function updateAllClusterInDatabase($pdo) {
    // Get all data
    $stmt = $pdo->query("SELECT id, wilayah, tahun, jumlah_penderita, jumlah_kematian, jumlah_penduduk FROM diabetes_data");
    $all_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Update statement
    $update_stmt = $pdo->prepare("UPDATE diabetes_data SET cluster = ? WHERE id = ?");
    
    foreach ($all_data as $row) {
        $kategori_info = getKategoriDiabetes(
            $row['jumlah_penderita'], 
            $row['jumlah_kematian'], 
            $row['jumlah_penduduk']
        );
        
        // Konversi kategori ke cluster ID
        $cluster_id = 0; // Default: Rendah
        if ($kategori_info['kategori'] == 'Sedang') {
            $cluster_id = 1;
        } elseif ($kategori_info['kategori'] == 'Tinggi') {
            $cluster_id = 2;
        }
        
        // Update database
        $update_stmt->execute([$cluster_id, $row['id']]);
    }
    
    return true;
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete'])) {
    $id = $_POST['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM diabetes_data WHERE id = ?");
        $stmt->execute([$id]);
        $success_message = "Record deleted successfully!";
        
        // Optionally update all clusters after deletion
        // updateAllClusterInDatabase($pdo);
    } catch (PDOException $e) {
        $error_message = "Error deleting record: " . $e->getMessage();
    }
}

// Sorting logic
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'id';
$order = isset($_GET['order']) ? $_GET['order'] : 'ASC';

// Validate sort column
$allowed_sort_columns = ['id', 'wilayah', 'jumlah_penduduk', 'tahun', 'jumlah_penderita', 'jumlah_kematian', 'cluster'];
if (!in_array($sort, $allowed_sort_columns)) {
    $sort = 'id';
}

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

// Fetch data with sorting, searching and pagination
try {
    if (!empty($search)) {
        // Search query
        $query = "SELECT * FROM diabetes_data WHERE 
                 wilayah LIKE :search OR 
                 tahun LIKE :search OR 
                 jumlah_penduduk LIKE :search OR
                 jumlah_penderita LIKE :search OR 
                 jumlah_kematian LIKE :search 
                 ORDER BY $sort $order
                 LIMIT :offset, :limit";
        
        $stmt = $pdo->prepare($query);
        $search_param = "%$search%";
        $stmt->bindParam(':search', $search_param, PDO::PARAM_STR);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $items_per_page, PDO::PARAM_INT);
        $stmt->execute();
        
        // Get total count for pagination
        $count_query = "SELECT COUNT(*) FROM diabetes_data WHERE 
                        wilayah LIKE :search OR 
                        tahun LIKE :search OR
                        jumlah_penduduk LIKE :search OR
                        jumlah_penderita LIKE :search OR 
                        jumlah_kematian LIKE :search";
                        
        $count_stmt = $pdo->prepare($count_query);
        $count_stmt->bindParam(':search', $search_param, PDO::PARAM_STR);
        $count_stmt->execute();
        $total_items = $count_stmt->fetchColumn();
    } else {
        // Regular query with no search
        $query = "SELECT * FROM diabetes_data ORDER BY $sort $order LIMIT :offset, :limit";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $items_per_page, PDO::PARAM_INT);
        $stmt->execute();
        
        // Get total count for pagination
        $count_query = "SELECT COUNT(*) FROM diabetes_data";
        $count_stmt = $pdo->query($count_query);
        $total_items = $count_stmt->fetchColumn();
    }
    
    $data = $stmt->fetchAll();
    $total_pages = ceil($total_items / $items_per_page);
    
} catch (PDOException $e) {
    $error_message = "Error fetching data: " . $e->getMessage();
    $data = [];
    $total_pages = 0;
}

// Include header
include '../includes/header.php';

// Function to generate sort URL
function sortUrl($column) {
    global $sort, $order, $search, $page;
    $newOrder = ($sort === $column && $order === 'ASC') ? 'DESC' : 'ASC';
    $params = [
        'sort' => $column,
        'order' => $newOrder,
        'page' => $page
    ];
    
    if (!empty($search)) {
        $params['search'] = $search;
    }
    
    return "?" . http_build_query($params);
}

// Function to generate page URL
function pageUrl($pageNum) {
    global $sort, $order, $search;
    $params = [
        'page' => $pageNum
    ];
    
    if (!empty($sort)) {
        $params['sort'] = $sort;
    }
    
    if (!empty($order)) {
        $params['order'] = $order;
    }
    
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
                <h1 class="h2">Manage Data</h1>
                <a href="data_input.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Add New Data
                </a>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i><?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Search and Filter Section -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Search data..." name="search" value="<?php echo htmlspecialchars($search); ?>">
                                <button class="btn btn-primary" type="submit">
                                    <i class="bi bi-search me-1"></i> Search
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-md-6 text-md-end">
                            <?php if (!empty($search)): ?>
                                <a href="delete_data.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle me-1"></i> Clear Search
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Data Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th><a href="<?php echo sortUrl('id'); ?>" class="text-decoration-none">ID <?php echo $sort === 'id' ? ($order === 'ASC' ? '▲' : '▼') : ''; ?></a></th>
                                    <th><a href="<?php echo sortUrl('wilayah'); ?>" class="text-decoration-none">Wilayah <?php echo $sort === 'wilayah' ? ($order === 'ASC' ? '▲' : '▼') : ''; ?></a></th>
                                    <th><a href="<?php echo sortUrl('jumlah_penduduk'); ?>" class="text-decoration-none">Jumlah Penduduk <?php echo $sort === 'jumlah_penduduk' ? ($order === 'ASC' ? '▲' : '▼') : ''; ?></a></th>
                                    <th><a href="<?php echo sortUrl('tahun'); ?>" class="text-decoration-none">Tahun <?php echo $sort === 'tahun' ? ($order === 'ASC' ? '▲' : '▼') : ''; ?></a></th>
                                    <th><a href="<?php echo sortUrl('jumlah_penderita'); ?>" class="text-decoration-none">Jumlah Penderita <?php echo $sort === 'jumlah_penderita' ? ($order === 'ASC' ? '▲' : '▼') : ''; ?></a></th>
                                    <th><a href="<?php echo sortUrl('jumlah_kematian'); ?>" class="text-decoration-none">Jumlah Kematian <?php echo $sort === 'jumlah_kematian' ? ($order === 'ASC' ? '▲' : '▼') : ''; ?></a></th>
                                    <th><a href="<?php echo sortUrl('cluster'); ?>" class="text-decoration-none">Kategori <?php echo $sort === 'cluster' ? ($order === 'ASC' ? '▲' : '▼') : ''; ?></a></th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($data) > 0): ?>
                                    <?php foreach ($data as $row): 
                                        $kategori_info = getKategoriDiabetes(
                                            $row['jumlah_penderita'], 
                                            $row['jumlah_kematian'], 
                                            $row['jumlah_penduduk']
                                        );
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                                            <td><?php echo htmlspecialchars($row['wilayah']); ?></td>
                                            <td><?php echo number_format($row['jumlah_penduduk'] ?? 0); ?></td>
                                            <td><?php echo htmlspecialchars($row['tahun']); ?></td>
                                            <td><?php echo number_format($row['jumlah_penderita']); ?></td>
                                            <td><?php echo number_format($row['jumlah_kematian']); ?></td>
                                            <td>
                                                <span class="badge <?php echo $kategori_info['color']; ?>">
                                                    <?php echo $kategori_info['kategori']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="edit_data.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this record?');" style="display:inline;">
                                                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
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
                                        <td colspan="8" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="bi bi-info-circle display-6 d-block mb-3"></i>
                                                <p>No data records found.</p>
                                                <?php if (!empty($search)): ?>
                                                    <p>Try a different search term or <a href="delete_data.php">clear the search</a>.</p>
                                                <?php else: ?>
                                                    <p>Start by <a href="data_input.php">adding new data</a>.</p>
                                                <?php endif; ?>
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
                                <li class="page-item<?php echo $page <= 1 ? ' disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo $page > 1 ? pageUrl($page - 1) : '#'; ?>" tabindex="-1">
                                        <i class="bi bi-chevron-left"></i> Previous
                                    </a>
                                </li>
                                
                                <?php 
                                $start_page = max(1, min($page - 2, $total_pages - 4));
                                $end_page = min($total_pages, max($page + 2, 5));
                                
                                for ($i = $start_page; $i <= $end_page; $i++): 
                                ?>
                                    <li class="page-item<?php echo $i == $page ? ' active' : ''; ?>">
                                        <a class="page-link" href="<?php echo pageUrl($i); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <li class="page-item<?php echo $page >= $total_pages ? ' disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo $page < $total_pages ? pageUrl($page + 1) : '#'; ?>">
                                        Next <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
            
  
<script>
function updateCategories() {
    if (confirm('Are you sure you want to update all categories in the database? This will override any existing cluster values.')) {
        // You would need to implement an AJAX call or form submission here to trigger the updateAllClusterInDatabase function
        // For simplicity, we'll redirect to a script that does the update
        window.location.href = 'update_categories.php';
    }
}
</script>

<?php include '../includes/footer.php'; ?>