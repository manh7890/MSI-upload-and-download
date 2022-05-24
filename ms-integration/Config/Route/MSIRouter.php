<?php

use Library\Route;
Route::get('MSIGetToken','MSI','getToKenMSServer');
Route::get('MSIRefreshToken','MSI','refreshToken');
Route::get('MSIGetTokenFromLocal','MSI','getToken');
Route::put('MSIMacroHandler','MSI','fileMacroHandler');
Route::delete('MSIDelete','MSI','deleteFile');
Route::post('MSICopy','MSI','copyFile');
Route::post('MSIUpdateDataAndRunMacro','MSI','updateDataAndRunMacro');





//ten methods ko viet hoa chu cai dau