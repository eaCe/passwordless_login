<?php

try {
    \rex_config::set($this->getName(), 'key', bin2hex(random_bytes(40)));
}
catch (Exception $e) {
}

rex_sql_table::get(rex::getTable($this->getName()))
    ->ensurePrimaryIdColumn()
    ->ensureColumn(new rex_sql_column('user_id', 'int', false))
    ->ensureColumn(new rex_sql_column('hash', 'varchar(255)', false))
    ->ensureColumn(new rex_sql_column('expiration', 'datetime', false))
    ->ensure();
