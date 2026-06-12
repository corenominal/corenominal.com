<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

// Grouping routes for the Auth controllers
$routes->group('auth', function($routes) {
    $routes->get('/', 'Auth\Login::index');
    $routes->post('/', 'Auth\Login::process');
    $routes->get('logout', 'Auth\Logout::index');
    $routes->get('register', 'Auth\Register::index');
    $routes->get('register/verify', 'Auth\Register::verify');
    $routes->post('register', 'Auth\Register::process');
    $routes->get('password-reset', 'Auth\PasswordReset::index');
    $routes->post('password-reset', 'Auth\PasswordReset::request');
    $routes->get('password-reset/confirm/(:segment)', 'Auth\PasswordReset::confirm/$1');
    $routes->post('password-reset/confirm', 'Auth\PasswordReset::update');
});

// Main admin dashboard
$routes->get('admin', 'Admin\Dashboard::index');

// Grouping routes for the Auth Admin controllers
$routes->group('admin/auth', function($routes) {
    $routes->get('/', 'Auth\Admin\Dashboard::index');
    $routes->get('users', 'Auth\Admin\Users::index');
    $routes->get('users/data', 'Auth\Admin\Users::getData');
    $routes->post('users/bulk-delete', 'Auth\Admin\Users::bulkDelete');
    $routes->get('users/(:num)', 'Auth\Admin\Users::getUser/$1');
    $routes->post('users/(:num)', 'Auth\Admin\Users::updateUser/$1');
    $routes->delete('users/(:num)', 'Auth\Admin\Users::deleteUser/$1');
    $routes->get('groups', 'Auth\Admin\Groups::index');
    $routes->get('groups/data', 'Auth\Admin\Groups::getData');
    $routes->get('groups/group-names', 'Auth\Admin\Groups::getGroupNames');
    $routes->get('groups/users', 'Auth\Admin\Groups::getUsers');
    $routes->post('groups/create', 'Auth\Admin\Groups::createGroup');
    $routes->post('groups/bulk-delete', 'Auth\Admin\Groups::bulkDelete');
    $routes->get('groups/(:num)', 'Auth\Admin\Groups::getGroup/$1');
    $routes->post('groups/(:num)', 'Auth\Admin\Groups::updateGroup/$1');
    $routes->delete('groups/(:num)', 'Auth\Admin\Groups::deleteGroup/$1');
    $routes->get('apikeys', 'Auth\Admin\ApiKeys::index');
    $routes->get('apikeys/data', 'Auth\Admin\ApiKeys::getData');
    $routes->post('apikeys/create', 'Auth\Admin\ApiKeys::createApikey');
    $routes->post('apikeys/bulk-delete', 'Auth\Admin\ApiKeys::bulkDelete');
    $routes->get('apikeys/(:num)', 'Auth\Admin\ApiKeys::getApikey/$1');
    $routes->post('apikeys/(:num)', 'Auth\Admin\ApiKeys::updateApikey/$1');
    $routes->delete('apikeys/(:num)', 'Auth\Admin\ApiKeys::deleteApikey/$1');
});

// Grouping routes for the AI API controllers
$routes->group('api/ai', function ($routes) {
    $routes->group('status', function ($routes) {
        $routes->match(['post', 'options'], 'rewrite', 'Api\Ai\Status::rewrite');
    });
    $routes->group('images', function ($routes) {
        $routes->match(['post', 'options'], 'alttext', 'Api\Ai\Images::alttext');
        $routes->match(['post', 'options'], 'describe', 'Api\Ai\Images::describe');
    });
    $routes->group('blog', function ($routes) {
        $routes->match(['post', 'options'], 'analyse', 'Api\Ai\Blog::analyse');
        $routes->match(['post', 'options'], 'rewrite', 'Api\Ai\Blog::rewrite');
        $routes->match(['post', 'options'], 'excerpt', 'Api\Ai\Blog::excerpt');
        $routes->match(['post', 'options'], 'creative', 'Api\Ai\Blog::creative');
        $routes->match(['post', 'options'], 'outline', 'Api\Ai\Blog::outline');
    });
    $routes->group('tags', function ($routes) {
        $routes->match(['post', 'options'], 'generate', 'Api\Ai\Tags::generate');
    });
    $routes->group('ollama', function ($routes) {
        $routes->match(['get', 'options'], 'list', 'Api\Ai\Ollama::list');
    });
});

// Public status routes
$routes->group('status', function ($routes) {
    $routes->get('/', 'Status\Home::index');
    $routes->get('feed/rss', 'Status\Feed::rss');
    $routes->get('timeline/load', 'Status\Home::loadMoreStatuses');
    $routes->get('(:segment)', 'Status\Home::show/$1');
});

// Grouping routes for the Social Admin controllers
$routes->group('admin/social', function($routes) {
    $routes->get('/', static fn() => redirect()->to('/admin/social/tags'));
    $routes->get('tags', 'Social\Admin\Tags::index');
    $routes->get('tags/data', 'Social\Admin\Tags::getData');
    $routes->post('tags/create', 'Social\Admin\Tags::createTag');
    $routes->post('tags/bulk-delete', 'Social\Admin\Tags::bulkDelete');
    $routes->get('tags/(:num)', 'Social\Admin\Tags::getTag/$1');
    $routes->post('tags/(:num)', 'Social\Admin\Tags::updateTag/$1');
    $routes->delete('tags/(:num)', 'Social\Admin\Tags::deleteTag/$1');
});

// Admin status routes (adminfilter + sessionfilter applied globally via Filters.php)
$routes->group('admin/status', function ($routes) {
    $routes->get('/', 'Status\Admin\Home::index');
    $routes->get('export', 'Status\Admin\Export::index');
    $routes->get('export/(:segment)', 'Status\Admin\Export::download/$1');
});

// Status API routes (apifilter applied globally via Filters.php)
$routes->group('api/status', function ($routes) {
    $routes->options('(:any)', static function () { return ''; });
    $routes->get('ping', 'Status\Api\Test::ping');
    $routes->post('statuses', 'Status\Api\Statuses::create');
    $routes->get('statuses/latest', 'Status\Api\Statuses::latest');
    $routes->get('statuses/(:num)', 'Status\Api\Statuses::get/$1');
    $routes->patch('statuses/(:num)', 'Status\Api\Statuses::update/$1');
    $routes->delete('statuses/(:num)', 'Status\Api\Statuses::delete/$1');
    $routes->post('media', 'Status\Api\Media::upload');
    $routes->delete('media/(:num)', 'Status\Api\Media::delete/$1');
    $routes->get('drafts', 'Status\Api\Drafts::index');
    $routes->post('drafts', 'Status\Api\Drafts::create');
    $routes->patch('drafts/(:num)', 'Status\Api\Drafts::update/$1');
    $routes->delete('drafts/(:num)', 'Status\Api\Drafts::delete/$1');
});

// Grouping routes for CLI commands
$routes->group('cli', function($routes) {
    $routes->cli('test/index/(:segment)', 'CLI\Test::index/$1');
    $routes->cli('test/count', 'CLI\Test::count');
    $routes->cli('sendmail/process', 'CLI\Sendmail::process');
});

// Grouping routes for Debug controllers
$routes->group('debug', function($routes) {
    $routes->get('/', 'Debug\Home::index');
    $routes->get('(:segment)', 'Debug\Rerouter::reroute/$1');
    $routes->get('(:segment)/(:segment)', 'Debug\Rerouter::reroute/$1/$2');
});

// Public bookmarks routes
$routes->group('bookmarks', function ($routes) {
    $routes->get('/', 'Bookmarks\Home::index');
    $routes->get('load', 'Bookmarks\Home::loadMore');
    $routes->get('feed/rss', 'Bookmarks\Feed::rss');
    $routes->get('(:segment)', 'Bookmarks\Home::show/$1');
});

// Admin bookmarks routes (adminfilter applied globally via Filters.php)
$routes->group('admin/bookmarks', function ($routes) {
    $routes->get('/', 'Bookmarks\Admin\Home::index');
    $routes->post('delete', 'Bookmarks\Admin\Home::delete');
    $routes->get('create', 'Bookmarks\Admin\BookmarkForm::create');
    $routes->get('(:segment)/edit', 'Bookmarks\Admin\BookmarkForm::edit/$1');
});

// API bookmarks routes (apifilter applied globally via Filters.php)
$routes->group('api/bookmarks', function ($routes) {
    $routes->options('(:any)', static function () { return ''; });
    $routes->post('/', 'Bookmarks\Api\Bookmarks::create');
    $routes->get('latest', 'Bookmarks\Api\Bookmarks::latest');
    $routes->get('check-url', 'Bookmarks\Api\Bookmarks::checkUrl');
    $routes->get('tags', 'Bookmarks\Api\Tags::index');
    $routes->post('markdown/preview', 'Bookmarks\Api\MarkdownPreview::convert');
    $routes->get('screenshot/preview', 'Bookmarks\Api\ScreenshotPreview::url');
    $routes->post('screenshot/capture', 'Bookmarks\Api\ScreenshotPreview::capture');
    $routes->put('(:segment)', 'Bookmarks\Api\Bookmarks::update/$1');
});

// Metrics collection endpoint
$routes->post('/metrics', 'Metrics::receive');

// Unauthorised route
$routes->get('/unauthorised', 'Unauthorised::index');
// Custom 404 route
$routes->set404Override('App\Controllers\Errors::show404');