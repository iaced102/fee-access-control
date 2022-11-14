<?php

use Library\Route;

Route::post('demotoken', 'Api', 'getDemoToken');
Route::post('test', 'Api', 'testFunction');
Route::post('objects/tenant-migrate', 'ObjectTenantMigration', 'migrate');
