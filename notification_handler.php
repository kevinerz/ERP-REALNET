<?php
// notification_handler.php
function display_notification() {
    if (isset($_SESSION['notif'])) {
        $type = isset($_SESSION['notif_type']) ? $_SESSION['notif_type'] : 'danger'; // Default to danger
        echo '<div class="alert alert-' . htmlspecialchars($type) . ' text-center mb-0 rounded-0">' . htmlspecialchars($_SESSION['notif']) . '</div>';
        unset($_SESSION['notif']);
        unset($_SESSION['notif_type']);
    }
}

function set_notification($message, $type = 'danger') {
    $_SESSION['notif'] = $message;
    $_SESSION['notif_type'] = $type;
}
?>