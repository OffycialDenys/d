<?php
// One-time cleanup of temporary smoke-test scaffolding. Safe to delete this file.
array_map('unlink', array_filter([
    __DIR__ . '/_smoke.php',
    __DIR__ . '/_smoke_out.txt',
    __DIR__ . '/_smoke_err.txt',
], 'file_exists'));
echo "Temp test files removed.\n";
