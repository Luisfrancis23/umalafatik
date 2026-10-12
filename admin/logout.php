<?php
/**
 * Admin — sign out
 */
require_once __DIR__ . '/../config/config.php';

if (is_admin_logged_in()) {
    set_flash('success', 'You have been signed out of the admin dashboard.');
}
logout_all();
redirect('/admin/login.php');
