<?php
/**
 * GeoJSON Upload & Conversion Handler
 * 
 * Upload and processes GeoJSON files, converting formats if necessary
 */
declare(strict_types=1);

session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Data directory path
$data_dir = '../data';
$geojson_file = $data_dir . '/aceh_regions.geojson';

// Create data directory if it doesn't exist
if (!is_dir($data_dir)) {
    if (!mkdir($data_dir, 0755, true)) {
        die(json_encode(['status' => 'error', 'message' => 'Unable to create data directory']));
    }
}

// Handle the file upload
if (isset($_FILES['geojson_file']) && $_FILES['geojson_file']['error'] === UPLOAD_ERR_OK) {
    $tmp_name = $_FILES['geojson_file']['tmp_name'];
    
    // Read the file content
    $geojson_content = file_get_contents($tmp_name);
    $json_data = json_decode($geojson_content);
    
    if ($json_data === null) {
        die(json_encode(['status' => 'error', 'message' => 'Invalid GeoJSON file']));
    }
    
    // Check if it has the required structure
    if (!isset($json_data->type) || $json_data->type !== 'FeatureCollection' || !isset($json_data->features) || !is_array($json_data->features)) {
        die(json_encode(['status' => 'error', 'message' => 'The file is not a valid GeoJSON FeatureCollection']));
    }
    
    // Process each feature to ensure it has a 'name' property
    $converted = false;
    $feature_names = [];
    $features_modified = 0;
    
    foreach ($json_data->features as $feature) {
        // Check if it has properties
        if (!isset($feature->properties)) {
            $feature->properties = new stdClass();
            $converted = true;
        }
        
        // If it doesn't have a 'name' property but has 'nm_kecamatan'
        if (!isset($feature->properties->name) && isset($feature->properties->nm_kecamatan)) {
            $feature->properties->name = $feature->properties->nm_kecamatan;
            $converted = true;
            $features_modified++;
        }
        
        // Collect feature names for validation with database
        if (isset($feature->properties->name)) {
            $feature_names[] = $feature->properties->name;
        }
    }
    
    // If modifications were made, update the GeoJSON content
    if ($converted) {
        $geojson_content = json_encode($json_data, JSON_PRETTY_PRINT);
    }
    
    // Write the (possibly modified) GeoJSON to the destination file
    if (file_put_contents($geojson_file, $geojson_content)) {
        // Fetch regions from database to check matches
        $stmt = $pdo->query("SELECT DISTINCT wilayah FROM diabetes_data");
        $db_regions = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Find matching and missing regions
        $matched_regions = array_intersect($feature_names, $db_regions);
        $unmatched_regions = array_diff($feature_names, $db_regions);
        $missing_regions = array_diff($db_regions, $feature_names);
        
        $message = "GeoJSON file uploaded successfully.";
        if ($converted) {
            $message .= " Modified $features_modified features to add 'name' property.";
        }
        
        die(json_encode([
            'status' => 'success',
            'message' => $message,
            'stats' => [
                'matched' => count($matched_regions),
                'unmatched' => count($unmatched_regions),
                'missing' => count($missing_regions),
                'unmatched_list' => array_slice($unmatched_regions, 0, 5),
                'missing_list' => array_slice($missing_regions, 0, 5)
            ]
        ]));
    } else {
        die(json_encode(['status' => 'error', 'message' => 'Failed to save the GeoJSON file']));
    }
} else {
    die(json_encode(['status' => 'error', 'message' => 'No file uploaded or upload error']));
}