<?php
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

if (!is_admin()) {
    redirect('../../login.php');
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM events WHERE id = ?")->execute([$id]);
    $_SESSION['success'] = "Event deleted!";
    redirect('index.php');
}

$events = $pdo->query("SELECT * FROM events ORDER BY date DESC")->fetchAll();
?>
<!-- Add full HTML similar to dashboard with: -->
<table class="table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Date</th>
            <th>Price</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($events as $event): ?>
        <tr>
            <td><?= $event['id'] ?></td>
            <td><?= $event['name'] ?></td>
            <td><?= date('M j, Y', strtotime($event['date'])) ?></td>
            <td>$<?= $event['price'] ?></td>
            <td>
                <a href="edit.php?id=<?= $event['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                <a href="?delete=<?= $event['id'] ?>" class="btn btn-sm btn-danger" 
                   onclick="return confirm('Delete this event?')">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<a href="add.php" class="btn btn-success">Add New Event</a>