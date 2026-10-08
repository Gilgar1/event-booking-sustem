<?php
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

if (!is_admin()) {
    redirect('../../login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and process form
    $data = [
        'name' => $_POST['name'],
        'date' => $_POST['date'],
        'time' => $_POST['time'],
        'venue' => $_POST['venue'],
        'organizer' => $_POST['organizer'],
        'price' => (float)$_POST['price'],
        'description' => $_POST['description']
    ];

    // Handle image upload
    if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], '../../assets/images/' . $filename);
        $data['image'] = $filename;
    }

    $stmt = $pdo->prepare("
        INSERT INTO events (name, date, time, venue, organizer, image, price, description)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute(array_values($data));
    
    $_SESSION['success'] = "Event added!";
    redirect('index.php');
}
?>
<!-- Form HTML with file upload -->