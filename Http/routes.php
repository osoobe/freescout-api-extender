<?php
Route::group(['prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\ApiExtender\Http\Controllers'], function () {
    Route::get('/api/knowledgebase/{mailboxId}/categories', ['uses' => 'KnowledgeBaseApiController@get', 'laroute' => false])->name('knowledgebase.index');
    Route::get('/api/knowledgebase/{mailboxId}/categories/{categoryId}', ['uses' => 'KnowledgeBaseApiController@category', 'laroute' => false])->name('knowledgebase.category');
    Route::get('/api/knowledgebase/{mailbox_id}/{article_id}/{slug?}', ['uses' => 'KnowledgeBaseApiController@getFrontendArticle', 'laroute' => false])->name('knowledgebase.article');

});

/**
 * API.
 */
Route::group(
    ['middleware' => ['bindings', \Modules\ApiWebhooks\Http\Middleware\ApiAuth::class],
    'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\ApiExtender\Http\Controllers'], function () {

        Route::post('/api/report/{report_name}', ['uses' => 'ReportApiController@publicReport', 'laroute' => false])->name('public.report');
        
});

