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
Route::post('permission_packs/delete-many','PermissionService','deleteMany');

Route::post('server_keys','ServerKeyService','create');
Route::get('server_keys','ServerKeyService','list');
Route::put('server_keys/{id}','ServerKeyService','update');
Route::delete('server_keys/{id}','ServerKeyService','delete');
Route::get('server_keys/{id}','ServerKeyService','detail');


Route::post('roles/{role_identifier}/permissions','RoleService','setPermission');
Route::get('roles/{role_identifier}/permissions','RoleService','listPermission');
Route::post('roles/set-permissions','RoleService','setPermissionBatch');
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
Route::post('action_packs/delete-many','ActionPackService','deleteMany');

Route::get('operations','OperationService','list');
Route::post('operations','OperationService','create');
Route::post('operations/save-batch','OperationService','saveBatch');
Route::post('operations/delete-many','OperationService','deleteMany');
Route::get('operations/actions','OperationService','getListType');
Route::post('operations/objects','OperationService','getListObjectIdentifier');
Route::get('operations/{type}/actions','OperationService','getActionByObjectType');

Route::get('env/object-types','Env','listObjectType',[],false,false,true);

Route::get('env/action_packs','Env','listActionPack',[],false,false,true);
Route::post('env/action_packs/ids','Env','getActionPackByIds',[],false,false,true);
Route::post('env/action_packs/save','Env','saveActionPackByIds',[],false,false,true);


Route::get('env/permission_packs','Env','listPermission',[],false,false,true);
Route::post('env/permission_packs/ids','Env','getPermissionByIds',[],false,false,true);
Route::post('env/permission_packs/save','Env','savePermissionByIds',[],false,false,true);

Route::get('filters','FilterService','list');
Route::post('filters','FilterService','create');
Route::put('filters/{id}','FilterService','update');
Route::get('filters/{id}','FilterService','detail');
Route::delete('filters/{id}','FilterService','delete');
Route::post('filters/delete-many','FilterService','deleteMany');
