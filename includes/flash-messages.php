<?php
if (!empty($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    $allowedTypes = ['success', 'danger', 'warning', 'info'];
    $type = in_array($flash['type'], $allowedTypes, true) ? $flash['type'] : 'info';

    echo '<div class="alert alert-' . e($type) . '" role="status">' . e($flash['message']) . '</div>';
}
