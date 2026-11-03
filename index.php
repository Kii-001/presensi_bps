<?php
require __DIR__ . '/includes/functions.php';
$u = me();
redirect($u ? ($u['role'] === 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php') : 'login.php');
