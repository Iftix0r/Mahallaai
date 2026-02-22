<?php 
require_once 'header.php'; 

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_news'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $image = $_POST['image'] ?? 'https://images.unsplash.com/photo-1541872703-74c5e443d1fe?auto=format&fit=crop&w=800&q=80';
    
    $stmt = $db->prepare("INSERT INTO news (title, content, image) VALUES (?, ?, ?)");
    $stmt->execute([$title, $content, $image]);
    echo "<script>alert('Yangilik qo\'shildi!');</script>";
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $db->prepare("DELETE FROM news WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: news.php');
}
?>

<script>document.getElementById('page-title').innerText = 'Yangiliklar Boshqaruvi';</script>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px;">
    <!-- Add News Form -->
    <div class="table-container" style="height: fit-content;">
        <h2 style="margin-bottom: 20px; font-size: 1.1rem;">Yangi qo'shish</h2>
        <form method="POST">
            <input type="hidden" name="add_news" value="1">
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-size: 0.9rem;">Sarlavha</label>
                <input type="text" name="title" required style="width: 100%; padding: 10px; border-radius: 10px; border: 1px solid #e2e8f0;">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-size: 0.9rem;">Rasm URL</label>
                <input type="text" name="image" placeholder="https://..." style="width: 100%; padding: 10px; border-radius: 10px; border: 1px solid #e2e8f0;">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-size: 0.9rem;">Matn</label>
                <textarea name="content" required style="width: 100%; height: 120px; padding: 10px; border-radius: 10px; border: 1px solid #e2e8f0; resize: none;"></textarea>
            </div>
            <button type="submit" style="width: 100%; padding: 12px; background: var(--primary); color: white; border: none; border-radius: 10px; font-weight: 700; cursor: pointer;">Saqlash</button>
        </form>
    </div>

    <!-- News List -->
    <div class="table-container">
        <h2 style="margin-bottom: 20px; font-size: 1.1rem;">Mavjud yangiliklar</h2>
        <table>
            <thead>
                <tr>
                    <th>Rasm</th>
                    <th>Sarlavha</th>
                    <th>Sana</th>
                    <th>Amallar</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $db->query("SELECT * FROM news ORDER BY created_at DESC");
                while ($news = $stmt->fetch()) {
                    echo "<tr>";
                    echo "<td><img src='{$news['image']}' style='width: 50px; height: 50px; border-radius: 10px; object-fit: cover;'></td>";
                    echo "<td>" . htmlspecialchars($news['title']) . "</td>";
                    echo "<td>" . date('d.m.Y', strtotime($news['created_at'])) . "</td>";
                    echo "<td><a href='?delete={$news['id']}' style='color: #ef4444; text-decoration: none;' onclick='return confirm(\"Ochirishga aminmisiz?\")'>O'chirish</a></td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>
