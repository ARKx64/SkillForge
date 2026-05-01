<?php
require_once 'includes/config.php';
startSession();
session_destroy();
redirect(app_url('index.php'));
