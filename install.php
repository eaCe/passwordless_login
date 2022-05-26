<?php
/** @var rex_addon $this */

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

if (!$this->getProperty('secret')) {
    $yaml = array_merge(rex_file::getConfig($this->getPath() . rex_package::FILE_PACKAGE), array('secret' => bin2hex(random_bytes(40))));
    rex_file::putConfig($this->getPath() . rex_package::FILE_PACKAGE, $yaml);
}