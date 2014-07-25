<?php

require "../config/params.php";

echo md5_file(SQLITE_DB_FILENAME . ".gz");
