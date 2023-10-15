<?php

use Library\Route;
Route::post('permission_packs/{id}/action_packs','PermissionService','addActionPack');
Route::get('permission_packs/{id}/action_packs','PermissionService','listActionPack');
Route::delete('permission_packs/{id}/action_packs/{actionPackId}','PermissionService','removeActionPack');

Route::post('permission_packs','PermissionService','create');
Route::get('permission_packs','PermissionService','list');
Route::put('permission_packs/{id}','PermissionService','update');
Route::delete('permission_packs/{id}','PermissionService','delete');
Route::get('permission_packs/{id}','PermissionService','detail');

Route::post('roles/{role_identifier}/permissions','RoleService','setPermission');
Route::get('roles/{role_identifier}/permissions','RoleService','listPermission');
Route::post('roles/set-permissions','RoleService','setPermissionBatch');
Route::post('roles/update-permissions','RoleService','updatePermissionBatch');
Route::get('roles/{role_identifier}/accesscontrol','RoleService','getAccessControlByRole');
Route::post('roles/accesscontrol/query','RoleService','getAccessControlByRoles');
Route::post('roles/{role_identifier}/accesscontrol/get-multi','RoleService','getAccessControlMultiObject');
Route::get('roles/{role_identifier}/accesscontrol/{object_identifier}','RoleService','getAccessControl');
Route::post('action_packs/{id}/operations','ActionPackService','addOperation');
Route::get('action_packs/{id}/operations','ActionPackService','listOperation');
Route::delete('action_packs/{id}/operations/{operationId}','ActionPackService','removeOperation');

Route::get('action_packs','ActionPackService','list');
Route::post('action_packs','ActionPackService','create');
Route::put('action_packs/{id}','ActionPackService','update');
Route::delete('action_packs/{id}','ActionPackService','delete');
Route::get('action_packs/{id}','ActionPackService','detail');

Route::get('operations','OperationService','list');
Route::get('operations/all-objects','OperationService','getListObjectIdentifierMultiTenant');
Route::post('operations','OperationService','create');
Route::post('operations/save-batch','OperationService','saveBatch');
Route::post('operations/delete-many','OperationService','deleteMany');
Route::get('operations/actions','OperationService','getListType');
Route::post('operations/objects','OperationService','getListObjectIdentifier');
Route::get('operations/{type}/actions','OperationService','getActionByObjectType');
Route::get('operations/{objectType}/{role}','OperationService','getOperationByObjectAndRole');

Route::get('filters','FilterService','list');
Route::get('filters-in-action-pack/{actionPackId}','FilterService','getFilterInActionPack');
Route::post('filters','FilterService','create');
Route::put('filters/{id}','FilterService','update');
Route::get('filters/{id}','FilterService','detail');
Route::delete('filters/{id}','FilterService','delete');
Route::post('filters/delete-many','FilterService','deleteMany');
Route::post('demotoken', 'Api', 'getDemoToken');
Route::post('test', 'Api', 'testFunction');
Route::post('objects/tenant-migrate', 'ObjectTenantMigration', 'migrate');
Route::post('object-identify', 'ObjectIdentifyService', 'save');
Route::post('role-action/make-new-view', 'RoleActionService', 'makeNewViewForTenant');
