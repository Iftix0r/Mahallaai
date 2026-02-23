<?php
// Check PHP upload configuration

echo "<h2>PHP Upload Configuration</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Setting</th><th>Value</th><th>Status</th></tr>";

$settings = [
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_execution_time' => ini_get('max_execution_time'),
    'max_input_time' => ini_get('max_input_time'),
    'memory_limit' => ini_get('memory_limit'),
    'file_uploads' => ini_get('file_uploads') ? 'Enabled' : 'Disabled'
];

foreach ($settings as $key => $value) {
    $status = '✅';
    if ($key === 'file_uploads' && $value === 'Disabled') {
        $status = '❌';
    }
    echo "<tr><td><strong>$key</strong></td><td>$value</td><td>$status</td></tr>";
}

echo "</table>";

// Check uploads directory
echo "<h2>Uploads Directory</h2>";
$uploadDir = __DIR__ . '/uploads/';
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Check</th><th>Status</th></tr>";

if (is_dir($uploadDir)) {
    echo "<tr><td>Directory exists</td><td>✅ Yes</td></tr>";
    echo "<tr><td>Path</td><td>$uploadDir</td></tr>";
    echo "<tr><td>Writable</td><td>" . (is_writable($uploadDir) ? '✅ Yes' : '❌ No') . "</td></tr>";
    echo "<tr><td>Permissions</td><td>" . substr(sprintf('%o', fileperms($uploadDir)), -4) . "</td></tr>";
} else {
    echo "<tr><td>Directory exists</td><td>❌ No</td></tr>";
    echo "<tr><td colspan='2'>Creating directory...</td></tr>";
    if (mkdir($uploadDir, 0755, true)) {
        echo "<tr><td colspan='2'>✅ Directory created successfully</td></tr>";
    } else {
        echo "<tr><td colspan='2'>❌ Failed to create directory</td></tr>";
    }
}

echo "</table>";

// Test file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
    echo "<h2>Upload Test Result</h2>";
    echo "<pre>";
    print_r($_FILES);
    echo "</pre>";
    
    if ($_FILES['test_file']['error'] === UPLOAD_ERR_OK) {
        $testFile = $uploadDir . 'test_' . time() . '_' . basename($_FILES['test_file']['name']);
        if (move_uploaded_file($_FILES['test_file']['tmp_name'], $testFile)) {
            echo "<p style='color: green;'>✅ File uploaded successfully: $testFile</p>";
            echo "<p>File size: " . filesize($testFile) . " bytes</p>";
            echo "<p>MIME type: " . $_FILES['test_file']['type'] . "</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to move uploaded file</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Upload error code: " . $_FILES['test_file']['error'] . "</p>";
    }
}
?>

<h2>Test File Upload</h2>
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="test_file" required>
    <button type="submit">Upload Test File</button>
</form>

<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    table { border-collapse: collapse; margin: 20px 0; }
    th { background: #667eea; color: white; }
    td { padding: 10px; }
</style>
