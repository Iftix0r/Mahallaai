<?php 
require_once 'header.php'; 

// Handle broadcast send
$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_broadcast'])) {
    $messageText = trim($_POST['message_text'] ?? '');
    $target = $_POST['target'] ?? 'all';
    $mediaType = 'text';
    $mediaFile = '';
    
    // Handle file upload
    if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileName = time() . '_' . basename($_FILES['media_file']['name']);
        $filePath = $uploadDir . $fileName;
        
        // Detect media type
        $mimeType = $_FILES['media_file']['type'];
        
        if (strpos($mimeType, 'image/') === 0) {
            $mediaType = 'photo';
        } elseif (strpos($mimeType, 'video/') === 0) {
            $mediaType = 'video';
        } else {
            $mediaType = 'document';
        }
        
        if (move_uploaded_file($_FILES['media_file']['tmp_name'], $filePath)) {
            $mediaFile = $filePath;
        } else {
            $msg = 'Fayl yuklashda xatolik!';
            $msgType = 'danger';
        }
    } elseif (isset($_FILES['media_file']) && $_FILES['media_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        // File upload error
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => 'Fayl hajmi juda katta (php.ini)',
            UPLOAD_ERR_FORM_SIZE => 'Fayl hajmi juda katta (form)',
            UPLOAD_ERR_PARTIAL => 'Fayl qisman yuklandi',
            UPLOAD_ERR_NO_TMP_DIR => 'Vaqtinchalik papka topilmadi',
            UPLOAD_ERR_CANT_WRITE => 'Faylni yozib bo\'lmadi',
            UPLOAD_ERR_EXTENSION => 'PHP kengaytmasi faylni to\'xtatdi'
        ];
        $errorCode = $_FILES['media_file']['error'];
        $msg = 'Fayl yuklashda xatolik: ' . ($uploadErrors[$errorCode] ?? "Noma'lum xatolik ($errorCode)");
        $msgType = 'danger';
    }
    
    if (empty($messageText) && empty($mediaFile)) {
        $msg = 'Xabar matni yoki media fayl yuborish kerak!';
        $msgType = 'danger';
    } else {
        // Get target chats
        $query = "SELECT chat_id, chat_type FROM chats";
        if ($target === 'users') {
            $query .= " WHERE chat_type = 'private'";
        } elseif ($target === 'groups') {
            $query .= " WHERE chat_type IN ('group', 'supergroup')";
        } elseif ($target === 'channels') {
            $query .= " WHERE chat_type = 'channel'";
        }
        
        // Also include users from users table (they may not be in chats table yet)
        $chatIds = [];
        
        $stmt = $db->query($query);
        while ($row = $stmt->fetch()) {
            $chatIds[$row['chat_id']] = true;
        }
        
        // Add users from users table if target is 'all' or 'users'
        if ($target === 'all' || $target === 'users') {
            $stmt2 = $db->query("SELECT telegram_id FROM users WHERE telegram_id IS NOT NULL");
            while ($row = $stmt2->fetch()) {
                $chatIds[$row['telegram_id']] = true;
            }
        }
        
        $totalSent = 0;
        $totalFailed = 0;
        $totalChats = count($chatIds);
        $processed = 0;
        
        // For media files, upload once and reuse file_id
        $mediaFileId = null;
        
        // Enable output buffering for real-time progress
        if (ob_get_level() == 0) ob_start();
        
        foreach ($chatIds as $chatId => $v) {
            $result = false;
            
            if ($mediaType === 'photo' && $mediaFile) {
                if ($mediaFileId) {
                    $result = sendPhotoByFileId($chatId, $mediaFileId, $messageText);
                } else {
                    $result = sendPhoto($chatId, $mediaFile, $messageText, $mediaFileId);
                }
            } elseif ($mediaType === 'video' && $mediaFile) {
                if ($mediaFileId) {
                    $result = sendVideoByFileId($chatId, $mediaFileId, $messageText);
                } else {
                    $result = sendVideo($chatId, $mediaFile, $messageText, $mediaFileId);
                }
            } elseif ($mediaType === 'document' && $mediaFile) {
                if ($mediaFileId) {
                    $result = sendDocumentByFileId($chatId, $mediaFileId, $messageText);
                } else {
                    $result = sendDocument($chatId, $mediaFile, $messageText, $mediaFileId);
                }
            } else {
                $result = sendBroadcastMessage($chatId, $messageText);
            }
            
            if ($result) {
                $totalSent++;
            } else {
                $totalFailed++;
            }
            
            $processed++;
            
            // Send progress update every 5 messages
            if ($processed % 5 == 0 || $processed == $totalChats) {
                $percent = round(($processed / $totalChats) * 100);
                echo "data: " . json_encode([
                    'progress' => $percent,
                    'sent' => $totalSent,
                    'failed' => $totalFailed,
                    'total' => $totalChats,
                    'current' => $processed
                ]) . "\n\n";
                ob_flush();
                flush();
            }
            
            // Small delay to avoid Telegram rate limits
            usleep(50000); // 50ms
        }
        
        // Save to history
        $stmt = $db->prepare("INSERT INTO broadcast_history (message_text, media_type, media_file, target, total_sent, total_failed, sent_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$messageText, $mediaType, basename($mediaFile), $target, $totalSent, $totalFailed, $_SESSION['admin_user']]);
        
        // Send final response
        echo "data: " . json_encode([
            'status' => 'complete',
            'sent' => $totalSent,
            'failed' => $totalFailed,
            'message' => "✅ Habar muvaffaqiyatli yuborildi! Yuborildi: {$totalSent}, Xatolik: {$totalFailed}"
        ]) . "\n\n";
        ob_end_flush();
        exit;
    }
}

// Delete history item
if (isset($_GET['delete_history'])) {
    $id = intval($_GET['delete_history']);
    $stmt = $db->prepare("DELETE FROM broadcast_history WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: broadcast.php?deleted=1');
    exit;
}

// Helper functions for sending media
function sendBroadcastMessage($chat_id, $text) {
    $data = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    
    $ch = curl_init(API_URL . 'sendMessage');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    return isset($result['ok']) && $result['ok'];
}

function sendPhoto($chat_id, $filePath, $caption = '', &$fileId = null) {
    $data = [
        'chat_id' => $chat_id,
        'photo' => new CURLFile($filePath),
        'caption' => $caption,
        'parse_mode' => 'HTML'
    ];
    
    $ch = curl_init(API_URL . 'sendPhoto');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    // Save file_id for reuse
    if (isset($result['ok']) && $result['ok'] && isset($result['result']['photo'])) {
        $photos = $result['result']['photo'];
        $fileId = end($photos)['file_id']; // Get largest photo
    }
    
    return isset($result['ok']) && $result['ok'];
}

function sendPhotoByFileId($chat_id, $fileId, $caption = '') {
    $data = [
        'chat_id' => $chat_id,
        'photo' => $fileId,
        'caption' => $caption,
        'parse_mode' => 'HTML'
    ];
    
    $ch = curl_init(API_URL . 'sendPhoto');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    return isset($result['ok']) && $result['ok'];
}

function sendVideo($chat_id, $filePath, $caption = '', &$fileId = null) {
    $data = [
        'chat_id' => $chat_id,
        'video' => new CURLFile($filePath),
        'caption' => $caption,
        'parse_mode' => 'HTML'
    ];
    
    $ch = curl_init(API_URL . 'sendVideo');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120); // 2 minutes for video
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    // Save file_id for reuse
    if (isset($result['ok']) && $result['ok'] && isset($result['result']['video']['file_id'])) {
        $fileId = $result['result']['video']['file_id'];
    }
    
    return isset($result['ok']) && $result['ok'];
}

function sendVideoByFileId($chat_id, $fileId, $caption = '') {
    $data = [
        'chat_id' => $chat_id,
        'video' => $fileId,
        'caption' => $caption,
        'parse_mode' => 'HTML'
    ];
    
    $ch = curl_init(API_URL . 'sendVideo');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    return isset($result['ok']) && $result['ok'];
}

function sendDocument($chat_id, $filePath, $caption = '', &$fileId = null) {
    $data = [
        'chat_id' => $chat_id,
        'document' => new CURLFile($filePath),
        'caption' => $caption,
        'parse_mode' => 'HTML'
    ];
    
    $ch = curl_init(API_URL . 'sendDocument');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    // Save file_id for reuse
    if (isset($result['ok']) && $result['ok'] && isset($result['result']['document']['file_id'])) {
        $fileId = $result['result']['document']['file_id'];
    }
    
    return isset($result['ok']) && $result['ok'];
}

function sendDocumentByFileId($chat_id, $fileId, $caption = '') {
    $data = [
        'chat_id' => $chat_id,
        'document' => $fileId,
        'caption' => $caption,
        'parse_mode' => 'HTML'
    ];
    
    $ch = curl_init(API_URL . 'sendDocument');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    return isset($result['ok']) && $result['ok'];
}

// Get statistics
$totalUsers = $db->query("SELECT COUNT(*) FROM users WHERE telegram_id IS NOT NULL")->fetchColumn();
$totalGroups = $db->query("SELECT COUNT(*) FROM chats WHERE chat_type IN ('group', 'supergroup')")->fetchColumn();
$totalChannels = $db->query("SELECT COUNT(*) FROM chats WHERE chat_type = 'channel'")->fetchColumn();
$totalPrivate = $db->query("SELECT COUNT(*) FROM chats WHERE chat_type = 'private'")->fetchColumn();
$totalAll = $totalUsers + $totalGroups + $totalChannels;
?>

<script>document.getElementById('page-title').innerText = '📢 Habar Yuborish';</script>

<style>
    .broadcast-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
    }

    .target-cards {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        margin-bottom: 20px;
    }

    .target-card {
        padding: 16px;
        border-radius: 12px;
        border: 2px solid var(--border);
        cursor: pointer;
        transition: all 0.3s ease;
        text-align: center;
        position: relative;
    }

    .target-card:hover {
        border-color: var(--primary-light);
        transform: translateY(-2px);
        box-shadow: var(--shadow);
    }

    .target-card.selected {
        border-color: var(--primary);
        background: var(--primary-bg);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
    }

    .target-card .target-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        margin: 0 auto 10px;
    }

    .target-card .target-count {
        font-size: 1.4rem;
        font-weight: 800;
        color: var(--text);
        margin-bottom: 4px;
    }

    .target-card .target-label {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .target-card input[type="radio"] {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }

    .tc-all .target-icon { background: #eff6ff; color: #3b82f6; }
    .tc-users .target-icon { background: #ecfdf5; color: #10b981; }
    .tc-groups .target-icon { background: #f5f3ff; color: #8b5cf6; }
    .tc-channels .target-icon { background: #fff7ed; color: #f97316; }

    .file-upload-area {
        border: 2px dashed var(--border);
        border-radius: 12px;
        padding: 28px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        background: #fafbfc;
        position: relative;
        overflow: hidden;
    }

    .file-upload-area:hover {
        border-color: var(--primary);
        background: var(--primary-bg);
    }

    .file-upload-area.has-file {
        border-color: var(--success);
        background: #ecfdf5;
    }

    .file-upload-area i {
        font-size: 2.2rem;
        color: var(--text-muted);
        margin-bottom: 10px;
        display: block;
    }

    .file-upload-area.has-file i {
        color: var(--success);
    }

    .file-upload-area p {
        color: var(--text-light);
        font-size: 0.85rem;
        font-weight: 500;
        margin-bottom: 4px;
    }

    .file-upload-area small {
        color: var(--text-muted);
        font-size: 0.75rem;
    }

    .file-upload-area input[type="file"] {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
    }

    .file-preview {
        display: none;
        margin-top: 12px;
        padding: 12px;
        background: white;
        border-radius: 10px;
        border: 1px solid var(--border);
    }

    .file-preview.active {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .file-preview img {
        width: 60px;
        height: 60px;
        border-radius: 8px;
        object-fit: cover;
    }

    .file-preview .file-info {
        flex: 1;
    }

    .file-preview .file-info .file-name {
        font-weight: 600;
        font-size: 0.85rem;
        color: var(--text);
        word-break: break-all;
    }

    .file-preview .file-info .file-size {
        font-size: 0.75rem;
        color: var(--text-muted);
        margin-top: 2px;
    }

    .file-preview .remove-file {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: none;
        background: #fef2f2;
        color: var(--danger);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }

    .file-preview .remove-file:hover {
        background: #fee2e2;
    }

    .message-textarea {
        min-height: 140px;
        font-family: 'Inter', sans-serif;
        line-height: 1.6;
    }

    .send-btn {
        width: 100%;
        padding: 14px;
        justify-content: center;
        font-size: 0.95rem;
        border-radius: 12px;
        gap: 10px;
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
    }

    .send-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
    }

    .send-btn:active {
        transform: translateY(0);
    }

    .preview-card {
        background: #f8fafc;
        border-radius: 12px;
        padding: 20px;
        border: 1px solid var(--border);
    }

    .preview-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 14px;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--border);
    }

    .preview-avatar {
        width: 38px;
        height: 38px;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1rem;
    }

    .preview-name {
        font-weight: 700;
        font-size: 0.9rem;
    }

    .preview-badge {
        background: #eff6ff;
        color: #3b82f6;
        font-size: 0.68rem;
        padding: 2px 8px;
        border-radius: 4px;
        font-weight: 600;
    }

    .preview-body {
        background: white;
        border-radius: 0 12px 12px 12px;
        padding: 14px 16px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.06);
        max-width: 90%;
        word-wrap: break-word;
    }

    .preview-body .preview-image {
        width: 100%;
        max-height: 200px;
        object-fit: cover;
        border-radius: 8px;
        margin-bottom: 10px;
    }

    .preview-body .preview-text {
        font-size: 0.88rem;
        line-height: 1.6;
        color: var(--text);
    }

    .preview-time {
        text-align: right;
        margin-top: 6px;
        font-size: 0.72rem;
        color: var(--text-muted);
    }

    .history-item {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 16px 20px;
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.2s;
    }

    .history-item:hover { background: #fafbfc; }
    .history-item:last-child { border-bottom: none; }

    .history-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
    }

    .history-icon.hi-text { background: #eff6ff; color: #3b82f6; }
    .history-icon.hi-photo { background: #ecfdf5; color: #10b981; }
    .history-icon.hi-video { background: #f5f3ff; color: #8b5cf6; }
    .history-icon.hi-document { background: #fff7ed; color: #f97316; }

    .history-info { flex: 1; min-width: 0; }

    .history-info .history-text {
        font-size: 0.85rem;
        font-weight: 500;
        color: var(--text);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 300px;
    }

    .history-meta {
        display: flex;
        gap: 14px;
        margin-top: 4px;
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    .history-stats {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .history-stats .h-stat {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 0.78rem;
        font-weight: 600;
    }

    .h-stat.success { color: #059669; }
    .h-stat.danger { color: #dc2626; }

    .char-counter {
        text-align: right;
        font-size: 0.72rem;
        color: var(--text-muted);
        margin-top: 4px;
    }

    .format-toolbar {
        display: flex;
        gap: 4px;
        margin-bottom: 8px;
        padding: 6px;
        background: #f8fafc;
        border-radius: 8px;
        border: 1px solid var(--border);
    }

    .format-btn {
        width: 32px;
        height: 32px;
        border: none;
        background: none;
        border-radius: 6px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.82rem;
        color: var(--text-light);
        transition: all 0.2s;
    }

    .format-btn:hover {
        background: white;
        color: var(--primary);
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    }

    @media (max-width: 1024px) {
        .broadcast-grid { grid-template-columns: 1fr; }
        .target-cards { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 600px) {
        .target-cards { grid-template-columns: 1fr; }
    }

    /* Loading and Progress Animations */
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }

    .fa-spinner {
        animation: spin 1s linear infinite;
    }

    #uploadProgress {
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    #progressBar {
        transition: width 0.3s ease;
        box-shadow: 0 0 10px rgba(99, 102, 241, 0.5);
    }

    .file-upload-area.uploading {
        pointer-events: none;
        opacity: 0.7;
    }
</style>

<?php if ($msg): ?>
    <div class="alert alert-<?php echo $msgType; ?>">
        <i class="fas fa-<?php echo $msgType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
        <?php echo $msg; ?>
    </div>
<?php endif; ?>
<?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-danger"><i class="fas fa-trash"></i> Tarix yozuvi o'chirildi.</div>
<?php endif; ?>

<div class="broadcast-grid">
    <!-- Broadcast Form -->
    <div>
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header">
                <h3><i class="fas fa-paper-plane" style="margin-right: 8px; color: var(--primary);"></i>Yangi habar yuborish</h3>
            </div>
            <div class="card-body padded">
                <form method="POST" enctype="multipart/form-data" id="broadcastForm">
                    <input type="hidden" name="send_broadcast" value="1">
                    
                    <!-- Target Selection -->
                    <div class="form-group">
                        <label><i class="fas fa-bullseye" style="margin-right: 6px;"></i>Kimga yuborish</label>
                        <div class="target-cards">
                            <label class="target-card tc-all selected" onclick="selectTarget(this)">
                                <input type="radio" name="target" value="all" checked>
                                <div class="target-icon"><i class="fas fa-globe"></i></div>
                                <div class="target-count"><?php echo number_format($totalAll); ?></div>
                                <div class="target-label">Barchaga</div>
                            </label>
                            <label class="target-card tc-users" onclick="selectTarget(this)">
                                <input type="radio" name="target" value="users">
                                <div class="target-icon"><i class="fas fa-user"></i></div>
                                <div class="target-count"><?php echo number_format($totalUsers); ?></div>
                                <div class="target-label">Foydalanuvchilar</div>
                            </label>
                            <label class="target-card tc-groups" onclick="selectTarget(this)">
                                <input type="radio" name="target" value="groups">
                                <div class="target-icon"><i class="fas fa-users"></i></div>
                                <div class="target-count"><?php echo number_format($totalGroups); ?></div>
                                <div class="target-label">Guruhlar</div>
                            </label>
                            <label class="target-card tc-channels" onclick="selectTarget(this)">
                                <input type="radio" name="target" value="channels">
                                <div class="target-icon"><i class="fas fa-bullhorn"></i></div>
                                <div class="target-count"><?php echo number_format($totalChannels); ?></div>
                                <div class="target-label">Kanallar</div>
                            </label>
                        </div>
                    </div>

                    <!-- Media Upload -->
                    <div class="form-group">
                        <label><i class="fas fa-paperclip" style="margin-right: 6px;"></i>Media fayl (ixtiyoriy)</label>
                        <div class="file-upload-area" id="uploadArea">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Rasm, video yoki fayl yuklang</p>
                            <small>JPG, PNG, GIF, MP4, PDF va boshqalar</small>
                            <input type="file" name="media_file" id="mediaFile" 
                                   accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.zip,.rar"
                                   onchange="handleFileSelect(this)">
                        </div>
                        <div class="file-preview" id="filePreview">
                            <img id="previewImg" src="" alt="">
                            <div class="file-info">
                                <div class="file-name" id="fileName"></div>
                                <div class="file-size" id="fileSize"></div>
                            </div>
                            <button type="button" class="remove-file" onclick="removeFile()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Message Text -->
                    <div class="form-group">
                        <label><i class="fas fa-comment-alt" style="margin-right: 6px;"></i>Xabar matni</label>
                        <div class="format-toolbar">
                            <button type="button" class="format-btn" onclick="insertFormat('b')" title="Qalin">
                                <i class="fas fa-bold"></i>
                            </button>
                            <button type="button" class="format-btn" onclick="insertFormat('i')" title="Kursiv">
                                <i class="fas fa-italic"></i>
                            </button>
                            <button type="button" class="format-btn" onclick="insertFormat('u')" title="Tagiga chizilgan">
                                <i class="fas fa-underline"></i>
                            </button>
                            <button type="button" class="format-btn" onclick="insertFormat('code')" title="Kod">
                                <i class="fas fa-code"></i>
                            </button>
                            <button type="button" class="format-btn" onclick="insertFormat('a')" title="Havola">
                                <i class="fas fa-link"></i>
                            </button>
                            <button type="button" class="format-btn" onclick="insertEmoji('😊')" title="Emoji">
                                😊
                            </button>
                            <button type="button" class="format-btn" onclick="insertEmoji('🎉')" title="Emoji">
                                🎉
                            </button>
                            <button type="button" class="format-btn" onclick="insertEmoji('📢')" title="Emoji">
                                📢
                            </button>
                        </div>
                        <textarea name="message_text" id="messageText" class="form-control message-textarea" 
                                  placeholder="Habar matnini yozing... HTML formatini qo'llab-quvvatlaydi" 
                                  oninput="updatePreview(); updateCharCount();"></textarea>
                        <div class="char-counter"><span id="charCount">0</span> belgi</div>
                    </div>

                    <!-- Submit -->
                    <button type="submit" class="btn btn-primary send-btn" id="sendBtn">
                        <i class="fas fa-paper-plane"></i> Habarni yuborish
                    </button>
                    
                    <!-- Progress Bar -->
                    <div id="uploadProgress" style="display: none; margin-top: 16px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span style="font-size: 0.85rem; font-weight: 600; color: var(--text);" id="progressText">Yuklanmoqda...</span>
                            <span style="font-size: 0.85rem; font-weight: 600; color: var(--primary);" id="progressPercent">0%</span>
                        </div>
                        <div style="height: 8px; background: #e5e7eb; border-radius: 10px; overflow: hidden;">
                            <div id="progressBar" style="height: 100%; width: 0%; background: linear-gradient(90deg, var(--primary), var(--primary-dark)); transition: width 0.3s ease;"></div>
                        </div>
                        <div style="text-align: center; margin-top: 12px; font-size: 0.8rem; color: var(--text-muted);" id="progressStatus"></div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Preview & History -->
    <div>
        <!-- Preview -->
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header">
                <h3><i class="fas fa-eye" style="margin-right: 8px; color: var(--text-muted);"></i>Ko'rinish</h3>
            </div>
            <div class="card-body padded">
                <div class="preview-card">
                    <div class="preview-header">
                        <div class="preview-avatar"><i class="fas fa-building"></i></div>
                        <div>
                            <div class="preview-name">Mahalla AI</div>
                        </div>
                        <span class="preview-badge">Bot</span>
                    </div>
                    <div class="preview-body">
                        <img id="previewImage" class="preview-image" src="" style="display: none;">
                        <div class="preview-text" id="previewText">
                            <span style="color: var(--text-muted); font-style: italic;">Xabar matni bu yerda ko'rinadi...</span>
                        </div>
                    </div>
                    <div class="preview-time" id="previewTime"><?php echo date('H:i'); ?></div>
                </div>
            </div>
        </div>

        <!-- History -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-history" style="margin-right: 8px; color: var(--text-muted);"></i>Yuborish tarixi</h3>
            </div>
            <div class="card-body">
                <?php
                $historyStmt = $db->query("SELECT * FROM broadcast_history ORDER BY created_at DESC LIMIT 15");
                $historyItems = $historyStmt->fetchAll();
                
                if (empty($historyItems)) {
                    echo "<div style='text-align: center; padding: 40px; color: var(--text-muted);'>
                            <i class='fas fa-inbox' style='font-size: 2rem; display: block; margin-bottom: 10px;'></i>
                            Hali habar yuborilmagan
                          </div>";
                } else {
                    foreach ($historyItems as $item) {
                        $iconClass = 'hi-text';
                        $iconName = 'fa-comment';
                        if ($item['media_type'] === 'photo') { $iconClass = 'hi-photo'; $iconName = 'fa-image'; }
                        elseif ($item['media_type'] === 'video') { $iconClass = 'hi-video'; $iconName = 'fa-video'; }
                        elseif ($item['media_type'] === 'document') { $iconClass = 'hi-document'; $iconName = 'fa-file'; }
                        
                        $targetLabel = 'Barchaga';
                        if ($item['target'] === 'users') $targetLabel = 'Foydalanuvchilar';
                        elseif ($item['target'] === 'groups') $targetLabel = 'Guruhlar';
                        elseif ($item['target'] === 'channels') $targetLabel = 'Kanallar';
                        
                        echo "<div class='history-item'>";
                        echo "  <div class='history-icon {$iconClass}'><i class='fas {$iconName}'></i></div>";
                        echo "  <div class='history-info'>";
                        echo "    <div class='history-text'>" . htmlspecialchars(mb_substr($item['message_text'] ?: '(Media)', 0, 60)) . "</div>";
                        echo "    <div class='history-meta'>";
                        echo "      <span><i class='fas fa-bullseye' style='margin-right: 4px;'></i>{$targetLabel}</span>";
                        echo "      <span><i class='fas fa-user' style='margin-right: 4px;'></i>{$item['sent_by']}</span>";
                        echo "      <span><i class='fas fa-clock' style='margin-right: 4px;'></i>" . date('d.m.Y H:i', strtotime($item['created_at'])) . "</span>";
                        echo "    </div>";
                        echo "  </div>";
                        echo "  <div class='history-stats'>";
                        echo "    <span class='h-stat success'><i class='fas fa-check'></i> {$item['total_sent']}</span>";
                        echo "    <span class='h-stat danger'><i class='fas fa-times'></i> {$item['total_failed']}</span>";
                        echo "  </div>";
                        echo "  <a href='?delete_history={$item['id']}' class='btn btn-danger' style='font-size: 0.7rem; padding: 6px 10px;' onclick='return confirm(\"O\\\"chirishga aminmisiz?\")'><i class='fas fa-trash-alt'></i></a>";
                        echo "</div>";
                    }
                }
                ?>
            </div>
        </div>
    </div>
</div>

<script>
function selectTarget(el) {
    document.querySelectorAll('.target-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
}

function handleFileSelect(input) {
    const file = input.files[0];
    if (!file) return;

    const area = document.getElementById('uploadArea');
    const preview = document.getElementById('filePreview');
    const previewImg = document.getElementById('previewImg');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const previewImage = document.getElementById('previewImage');

    // Show loading animation (without destroying input)
    const loadingDiv = document.createElement('div');
    loadingDiv.id = 'loadingOverlay';
    loadingDiv.style.cssText = 'position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.95); display: flex; flex-direction: column; align-items: center; justify-content: center; border-radius: 12px; z-index: 10;';
    loadingDiv.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--primary);"></i><p style="margin-top: 10px; color: var(--text);">Yuklanmoqda...</p>';
    area.appendChild(loadingDiv);

    // Simulate file processing
    setTimeout(() => {
        // Remove loading overlay
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) overlay.remove();
        
        area.classList.add('has-file');
        preview.classList.add('active');
        fileName.textContent = file.name;
        fileSize.textContent = formatFileSize(file.size);

        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                previewImg.src = e.target.result;
                previewImg.style.display = 'block';
                previewImage.src = e.target.result;
                previewImage.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else if (file.type.startsWith('video/')) {
            // Show video icon for video files
            previewImg.style.display = 'none';
            previewImage.style.display = 'none';
        } else {
            previewImg.style.display = 'none';
            previewImage.style.display = 'none';
        }
    }, 500);
}

function removeFile() {
    const fileInput = document.getElementById('mediaFile');
    const area = document.getElementById('uploadArea');
    const preview = document.getElementById('filePreview');
    const previewImage = document.getElementById('previewImage');
    
    // Clear file input
    fileInput.value = '';
    
    // Remove classes and hide preview
    area.classList.remove('has-file');
    preview.classList.remove('active');
    previewImage.style.display = 'none';
    
    // Remove loading overlay if exists
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) overlay.remove();
}

function updatePreview() {
    const text = document.getElementById('messageText').value;
    const previewText = document.getElementById('previewText');
    
    if (text.trim()) {
        // Replace newlines with <br> for preview
        let formatted = text.replace(/\n/g, '<br>');
        previewText.innerHTML = formatted;
    } else {
        previewText.innerHTML = '<span style="color: var(--text-muted); font-style: italic;">Xabar matni bu yerda ko\'rinadi...</span>';
    }
}

function updateCharCount() {
    const text = document.getElementById('messageText').value;
    document.getElementById('charCount').textContent = text.length;
}

function insertFormat(tag) {
    const textarea = document.getElementById('messageText');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selected = textarea.value.substring(start, end);
    
    let formatted;
    if (tag === 'a') {
        const url = prompt('Havolani kiriting:', 'https://');
        if (!url) return;
        formatted = `<a href="${url}">${selected || 'Havola matni'}</a>`;
    } else {
        formatted = `<${tag}>${selected || ''}</${tag}>`;
    }
    
    textarea.value = textarea.value.substring(0, start) + formatted + textarea.value.substring(end);
    textarea.focus();
    updatePreview();
    updateCharCount();
}

function insertEmoji(emoji) {
    const textarea = document.getElementById('messageText');
    const start = textarea.selectionStart;
    textarea.value = textarea.value.substring(0, start) + emoji + textarea.value.substring(start);
    textarea.focus();
    updatePreview();
    updateCharCount();
}

// Handle form submission with progress
document.getElementById('broadcastForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const text = document.getElementById('messageText').value.trim();
    const fileInput = document.getElementById('mediaFile');
    const file = fileInput.files[0];
    
    if (!text && !file) {
        alert('Iltimos, xabar matni yozing yoki media fayl yuklang!');
        return;
    }
    
    const target = document.querySelector('input[name="target"]:checked').value;
    const targetLabels = { all: 'barchaga', users: 'foydalanuvchilarga', groups: 'guruhlarga', channels: 'kanallarga' };
    
    if (!confirm(`Habarni ${targetLabels[target]} yuborishni tasdiqlaysizmi?`)) {
        return;
    }
    
    // Show progress
    const sendBtn = document.getElementById('sendBtn');
    const uploadProgress = document.getElementById('uploadProgress');
    const progressBar = document.getElementById('progressBar');
    const progressPercent = document.getElementById('progressPercent');
    const progressText = document.getElementById('progressText');
    const progressStatus = document.getElementById('progressStatus');
    
    sendBtn.disabled = true;
    sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Yuborilmoqda...';
    uploadProgress.style.display = 'block';
    
    // Prepare form data - IMPORTANT: Get fresh FormData from form
    const formData = new FormData();
    formData.append('send_broadcast', '1');
    formData.append('message_text', text);
    formData.append('target', target);
    
    // Add file if exists
    if (file) {
        formData.append('media_file', file);
        console.log('File added to FormData:', file.name, file.size, file.type);
    }
    
    // Create XMLHttpRequest for progress tracking
    const xhr = new XMLHttpRequest();
    
    // Upload progress
    xhr.upload.addEventListener('progress', function(e) {
        if (e.lengthComputable) {
            const percentComplete = Math.round((e.loaded / e.total) * 100);
            progressBar.style.width = percentComplete + '%';
            progressPercent.textContent = percentComplete + '%';
            
            if (file) {
                progressText.textContent = 'Fayl yuklanmoqda...';
                progressStatus.textContent = `${formatFileSize(e.loaded)} / ${formatFileSize(e.total)}`;
            } else {
                progressText.textContent = 'Ma\'lumotlar yuklanmoqda...';
            }
        }
    });
    
    // When upload is complete, show sending status
    xhr.upload.addEventListener('load', function() {
        progressText.textContent = 'Foydalanuvchilarga yuborilmoqda...';
        progressStatus.textContent = 'Iltimos kuting...';
    });
    
    // Request complete
    xhr.addEventListener('load', function() {
        console.log('Response status:', xhr.status);
        console.log('Response text:', xhr.responseText);
        
        if (xhr.status === 200) {
            // Parse streaming response
            const lines = xhr.responseText.split('\n');
            let lastData = null;
            
            for (let line of lines) {
                if (line.startsWith('data: ')) {
                    try {
                        lastData = JSON.parse(line.substring(6));
                    } catch (e) {}
                }
            }
            
            if (lastData && lastData.status === 'complete') {
                progressBar.style.width = '100%';
                progressPercent.textContent = '100%';
                progressText.textContent = '✅ Muvaffaqiyatli yuborildi!';
                progressStatus.textContent = `Yuborildi: ${lastData.sent}, Xatolik: ${lastData.failed}`;
                
                setTimeout(function() {
                    window.location.reload();
                }, 2000);
            } else {
                progressBar.style.width = '100%';
                progressPercent.textContent = '100%';
                progressText.textContent = '✅ Muvaffaqiyatli yuborildi!';
                progressStatus.textContent = 'Habar barcha foydalanuvchilarga yetkazildi';
                
                setTimeout(function() {
                    window.location.reload();
                }, 2000);
            }
        } else {
            progressText.textContent = '❌ Xatolik yuz berdi';
            progressStatus.textContent = 'Iltimos, qaytadan urinib ko\'ring';
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Habarni yuborish';
        }
    });
    
    // Progress handler for streaming response
    let lastResponseLength = 0;
    xhr.addEventListener('readystatechange', function() {
        if (xhr.readyState === 3) { // LOADING
            const response = xhr.responseText.substring(lastResponseLength);
            lastResponseLength = xhr.responseText.length;
            
            const lines = response.split('\n');
            for (let line of lines) {
                if (line.startsWith('data: ')) {
                    try {
                        const data = JSON.parse(line.substring(6));
                        if (data.progress) {
                            progressBar.style.width = data.progress + '%';
                            progressPercent.textContent = data.progress + '%';
                            progressText.textContent = 'Yuborilmoqda...';
                            progressStatus.textContent = `${data.current}/${data.total} - Yuborildi: ${data.sent}, Xatolik: ${data.failed}`;
                        }
                    } catch (e) {
                        console.error('Parse error:', e);
                    }
                }
            }
        }
    });
    
    // Request error
    xhr.addEventListener('error', function() {
        console.error('XHR Error');
        progressText.textContent = '❌ Tarmoq xatosi';
        progressStatus.textContent = 'Internet aloqasini tekshiring';
        sendBtn.disabled = false;
        sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Habarni yuborish';
    });
    
    // Timeout handler
    xhr.addEventListener('timeout', function() {
        console.error('XHR Timeout');
        progressText.textContent = '❌ Vaqt tugadi';
        progressStatus.textContent = 'Server javob bermadi';
        sendBtn.disabled = false;
        sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Habarni yuborish';
    });
    
    // Send request
    xhr.open('POST', window.location.href, true);
    xhr.timeout = 300000; // 5 minutes timeout for large files
    xhr.send(formData);
});

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}
</script>

<?php require_once 'footer.php'; ?>
