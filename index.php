<?php
require_once __DIR__ . '/includes/auth.php';

if (isAuthenticated()) {
    redirect(dashboardPathFor(getCurrentUser()['role']));
}
redirect('login.php');
