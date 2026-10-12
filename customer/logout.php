<?php
/**
 * Customer — sign out
 */
require_once __DIR__ . '/../config/config.php';

logout_all();
set_flash('success', 'You have been signed out. Until next time!');
redirect('/');
