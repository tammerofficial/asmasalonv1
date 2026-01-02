<?php
require_once('../../../wp-load.php');
require_once('includes/Database/Schema.php');
\AsmaaSalon\Database\Schema::create_core_tables();
echo "Migration completed.";
unlink(__FILE__);
