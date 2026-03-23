<?php
require_once __DIR__ . '/../src/helpers/functions.php';
require_once __DIR__ . '/../src/models/SystemSettings.php';

// Strict admin check
if (!isAdmin()) {
    redirect('/dashboard.php');
}

$systemSettings = new SystemSettings();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_store_visibility'])) {
        $newMode = $systemSettings->toggleStoreVisibilityMode();
        $modeText = $newMode === 'available_only' ? 'Available Stores Only' : 'All Stores';
        setFlashMessage("Store visibility mode changed to: $modeText", 'success');
        redirect('/admin/dashboard.php');
    }
}

// Redirect back if not POST
redirect('/admin/dashboard.php');
?>
