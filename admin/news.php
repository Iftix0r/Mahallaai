<?php 
require_once 'header.php'; 

$msg = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_news'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $image = $_POST['image'] ?: 'https://images.unsplash.com/photo-1541872703-74c5e443d1fe?auto=format&fit=crop&w=800&q=80';
    
    $stmt = $db->prepare("INSERT INTO news (title, content, image) VALUES (?, ?, ?)");
    $stmt->execute([$title, $content, $image]);
    $msg = 'success';
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $db->prepare("DELETE FROM news WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: news.php?deleted=1');
    exit;
}
?>

<script>document.getElementById('page-title').innerText = 'Yangiliklar';</script>

<?php if ($msg == 'success'): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> Yangilik muvaffaqiyatli qo'shildi!</div>
<?php endif; ?>
<?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-danger"><i class="fas fa-trash"></i> Yangilik o'chirildi.</div>
<?php endif; ?>

<div class="grid-2">
    <!-- Add News Form -->
    <div class="card" style="height: fit-content;">
        <div class="card-header">
            <h3><i class="fas fa-plus-circle" style="margin-right: 8px; color: var(--primary);"></i>Yangi qo'shish</h3>
        </div>
        <div class="card-body padded">
            <form method="POST">
                <input type="hidden" name="add_news" value="1">
                <div class="form-group">
                    <label><i class="fas fa-heading" style="margin-right: 6px;"></i>Sarlavha</label>
                    <input type="text" name="title" required class="form-control" placeholder="Yangilik sarlavhasi...">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-image" style="margin-right: 6px;"></i>Rasm URL</label>
                    <input type="text" name="image" class="form-control" placeholder="https://...">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-align-left" style="margin-right: 6px;"></i>Matn</label>
                    <textarea name="content" required class="form-control" placeholder="Yangilik matni..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
                    <i class="fas fa-save"></i> Saqlash
                </button>
            </form>
        </div>
    </div>

    <!-- News List -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-newspaper" style="margin-right: 8px; color: var(--text-muted);"></i>Mavjud yangiliklar</h3>
        </div>
        <div class="card-body">
            <table>
                <thead>
                    <tr>
                        <th>Rasm</th>
                        <th>Sarlavha</th>
                        <th>Sana</th>
                        <th>Amal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $db->query("SELECT * FROM news ORDER BY created_at DESC");
                    while ($news = $stmt->fetch()) {
                        echo "<tr>";
                        echo "<td><img src='{$news['image']}' style='width: 50px; height: 50px; border-radius: 10px; object-fit: cover;'></td>";
                        echo "<td style='font-weight: 600;'>" . htmlspecialchars($news['title']) . "</td>";
                        echo "<td style='color: var(--text-light);'>" . date('d.m.Y', strtotime($news['created_at'])) . "</td>";
                        echo "<td><a href='?delete={$news['id']}' class='btn btn-danger' style='font-size: 0.75rem;' onclick='return confirm(\"Ochirishga aminmisiz?\")'><i class='fas fa-trash-alt'></i></a></td>";
                        echo "</tr>";
                    }

                    if ($db->query("SELECT COUNT(*) FROM news")->fetchColumn() == 0) {
                        echo "<tr><td colspan='4' style='text-align: center; padding: 40px; color: var(--text-muted);'><i class='fas fa-inbox' style='font-size: 2rem; display: block; margin-bottom: 10px;'></i>Yangiliklar yo'q</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
