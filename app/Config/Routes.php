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
        $routes->match(['post', 'options'], 'rewrite', 'Ai\Api\Status::rewrite');
    });
    $routes->group('images', function ($routes) {
        $routes->match(['post', 'options'], 'alttext', 'Ai\Api\Images::alttext');
        $routes->match(['post', 'options'], 'describe', 'Ai\Api\Images::describe');
    });
    $routes->group('blog', function ($routes) {
        $routes->match(['post', 'options'], 'analyse', 'Ai\Api\Blog::analyse');
        $routes->match(['post', 'options'], 'rewrite', 'Ai\Api\Blog::rewrite');
        $routes->match(['post', 'options'], 'excerpt', 'Ai\Api\Blog::excerpt');
        $routes->match(['post', 'options'], 'creative', 'Ai\Api\Blog::creative');
        $routes->match(['post', 'options'], 'outline', 'Ai\Api\Blog::outline');
    });
    $routes->group('tags', function ($routes) {
        $routes->match(['post', 'options'], 'generate', 'Ai\Api\Tags::generate');
    });
    $routes->group('ollama', function ($routes) {
        $routes->match(['get', 'options'], 'list', 'Ai\Api\Ollama::list');
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
    $routes->get('generator', 'Status\Admin\Generator::index');
});

// Status generator API routes (apifilter applied globally via Filters.php)
$routes->group('api/status/generator', function ($routes) {
    $routes->options('(:any)', static function () { return ''; });
    $routes->post('stream', 'Status\Api\Generator::stream');
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
    $routes->cli('fetch-github-activity', 'CLI\FetchGitHubActivity::index');
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
    $routes->match(['post', 'options'], '/', 'Bookmarks\Api\Bookmarks::create');
    $routes->get('latest', 'Bookmarks\Api\Bookmarks::latest');
    $routes->get('check-url', 'Bookmarks\Api\Bookmarks::checkUrl');
    $routes->get('tags', 'Bookmarks\Api\Tags::index');
    $routes->post('markdown/preview', 'Bookmarks\Api\MarkdownPreview::convert');
    $routes->get('screenshot/preview', 'Bookmarks\Api\ScreenshotPreview::url');
    $routes->post('screenshot/capture', 'Bookmarks\Api\ScreenshotPreview::capture');
    $routes->put('(:segment)', 'Bookmarks\Api\Bookmarks::update/$1');
});

// Public blog routes
$routes->group('blog', function ($routes) {
    $routes->get('/', 'Blog\Home::index');
    $routes->get('posts', 'Blog\Home::morePosts');
    $routes->get('posts/(:segment)/json', 'Blog\Post::showJson/$1');
    $routes->get('posts/(:segment)/markdown', 'Blog\Post::showMarkdown/$1');
    $routes->get('posts/(:segment)', 'Blog\Post::show/$1');
    $routes->get('tags/(:segment)', 'Blog\Tag::show/$1');
    $routes->get('search', 'Blog\Search::index');
    $routes->get('feed/rss', 'Blog\Feed::rss');
});

// Admin blog routes (adminfilter applied globally via Filters.php)
$routes->group('admin/blog', function ($routes) {
    $routes->get('/', 'Blog\Admin\Home::index');
    $routes->post('posts/delete', 'Blog\Admin\Home::deletePosts');
    $routes->post('posts/preview', 'Blog\Admin\Posts::preview');
    $routes->post('posts/upload_featured_image', 'Blog\Admin\Posts::upload_featured_image');
    $routes->post('posts/remove_featured_image', 'Blog\Admin\Posts::remove_featured_image');
    $routes->get('posts/list_featured_images', 'Blog\Admin\Posts::list_featured_images');
    $routes->post('posts/upload_body_image', 'Blog\Admin\Posts::upload_body_image');
    $routes->post('posts/upload_video', 'Blog\Admin\Posts::upload_video');
    $routes->post('posts/remove_video', 'Blog\Admin\Posts::remove_video');
    $routes->get('posts/create', 'Blog\Admin\Posts::create');
    $routes->post('posts/store', 'Blog\Admin\Posts::store');
    $routes->get('posts/(:num)/edit', 'Blog\Admin\Posts::edit/$1');
    $routes->post('posts/(:num)/update', 'Blog\Admin\Posts::update/$1');
});

// Blog API routes (apifilter applied globally via Filters.php)
$routes->group('api/blog', function ($routes) {
    $routes->options('(:any)', static function () { return ''; });
    $routes->get('ping', 'Blog\Api\Test::ping');
    $routes->get('posts/latest', 'Blog\Api\Posts::latest');
});

// Admin startpage routes (adminfilter applied globally via Filters.php)
$routes->group('admin/startpage', function ($routes) {
    $routes->get('/', 'Startpage\Admin\Home::index');
    $routes->post('command', 'Startpage\Admin\Home::command');
    $routes->get('history/suggestions', 'Startpage\Admin\Home::historySuggestions');
    $routes->get('history', 'Startpage\Admin\History::index');
    $routes->post('history/delete', 'Startpage\Admin\History::delete');
    $routes->get('redirects', 'Startpage\Admin\Redirects::index');
    $routes->post('redirects/add', 'Startpage\Admin\Redirects::add');
    $routes->post('redirects/edit', 'Startpage\Admin\Redirects::edit');
    $routes->post('redirects/delete', 'Startpage\Admin\Redirects::delete');
    $routes->get('search', 'Startpage\Admin\Search::index');
    $routes->post('search/add', 'Startpage\Admin\Search::add');
    $routes->post('search/edit', 'Startpage\Admin\Search::edit');
    $routes->post('search/delete', 'Startpage\Admin\Search::delete');
    $routes->get('shortcuts', 'Startpage\Admin\Shortcuts::index');
    $routes->post('shortcuts/category/add', 'Startpage\Admin\Shortcuts::categoryAdd');
    $routes->post('shortcuts/category/edit', 'Startpage\Admin\Shortcuts::categoryEdit');
    $routes->post('shortcuts/category/delete', 'Startpage\Admin\Shortcuts::categoryDelete');
    $routes->post('shortcuts/category/reorder', 'Startpage\Admin\Shortcuts::categoryReorder');
    $routes->post('shortcuts/add', 'Startpage\Admin\Shortcuts::shortcutAdd');
    $routes->post('shortcuts/edit', 'Startpage\Admin\Shortcuts::shortcutEdit');
    $routes->post('shortcuts/delete', 'Startpage\Admin\Shortcuts::shortcutDelete');
    $routes->post('shortcuts/reorder', 'Startpage\Admin\Shortcuts::shortcutReorder');
    $routes->get('import-export', 'Startpage\Admin\ImportExport::index');
    $routes->get('export/history', 'Startpage\Admin\ImportExport::exportHistory');
    $routes->get('export/redirects', 'Startpage\Admin\ImportExport::exportRedirects');
    $routes->get('export/search', 'Startpage\Admin\ImportExport::exportSearch');
    $routes->post('import/history', 'Startpage\Admin\ImportExport::importHistory');
    $routes->post('import/redirects', 'Startpage\Admin\ImportExport::importRedirects');
    $routes->post('import/search', 'Startpage\Admin\ImportExport::importSearch');
});

// API startpage routes (apifilter applied globally via Filters.php)
$routes->group('api/startpage', function ($routes) {
    $routes->options('(:any)', static function () { return ''; });
    $routes->match(['post', 'options'], 'redirects', 'Startpage\Api\Redirects::create');
});

// Admin notes routes (adminfilter applied globally via Filters.php)
$routes->group('admin/notes', function ($routes) {
    $routes->get('/', 'Notes\Admin\Home::index');
    $routes->get('key', 'Notes\Admin\Key::index');
    $routes->get('new', 'Notes\Admin\Editor::new');
    $routes->get('(:num)/edit', 'Notes\Admin\Editor::edit/$1');
});

// API notes routes (apifilter applied globally via Filters.php)
$routes->group('api/notes', function ($routes) {
    $routes->options('(:any)', static function () { return ''; });
    $routes->get('list', 'Notes\Api\Notes::list');
    $routes->get('(:num)', 'Notes\Api\Notes::find/$1');
    $routes->post('/', 'Notes\Api\Notes::create');
    $routes->post('preview', 'Notes\Api\Notes::preview');
    $routes->patch('(:num)', 'Notes\Api\Notes::update/$1');
    $routes->delete('(:num)', 'Notes\Api\Notes::delete/$1');
    $routes->get('(:num)/revisions', 'Notes\Api\Notes::listRevisions/$1');
    $routes->get('(:num)/revision/(:num)', 'Notes\Api\Notes::findRevision/$1/$2');
    $routes->delete('(:num)/revision/(:num)', 'Notes\Api\Notes::deleteRevision/$1/$2');
    $routes->delete('(:num)/revisions', 'Notes\Api\Notes::deleteRevisions/$1');
});

// Admin bikes routes (adminfilter applied globally via Filters.php)
$routes->group('admin/bikes', function ($routes) {
    $routes->get('/', 'Bikes\Admin\Home::index');
    $routes->post('delete', 'Bikes\Admin\Home::delete');
    $routes->get('create', 'Bikes\Admin\BikeForm::create');
    $routes->get('(:num)/edit', 'Bikes\Admin\BikeForm::edit/$1');
    $routes->get('(:num)/notes/create', 'Bikes\Admin\NoteForm::create/$1');
    $routes->get('(:num)/notes/(:num)/edit', 'Bikes\Admin\NoteForm::edit/$1/$2');
});

// API bikes routes (apifilter applied globally via Filters.php)
$routes->group('api/bikes', function ($routes) {
    $routes->options('(:any)', static function () { return ''; });
    $routes->post('/', 'Bikes\Api\Bikes::create');
    $routes->put('(:num)', 'Bikes\Api\Bikes::update/$1');
    $routes->post('(:num)/photos', 'Bikes\Api\BikePhotos::upload/$1');
    $routes->delete('(:num)/photos/(:num)', 'Bikes\Api\BikePhotos::delete/$1/$2');
    $routes->post('(:num)/photos/reorder', 'Bikes\Api\BikePhotos::reorder/$1');
    $routes->post('notes/preview', 'Bikes\Api\BikeNotes::preview');
    $routes->post('(:num)/notes', 'Bikes\Api\BikeNotes::create/$1');
    $routes->put('(:num)/notes/(:num)', 'Bikes\Api\BikeNotes::update/$1/$2');
    $routes->delete('(:num)/notes/(:num)', 'Bikes\Api\BikeNotes::delete/$1/$2');
    $routes->post('(:num)/notes/(:num)/media', 'Bikes\Api\BikeNoteMedia::upload/$1/$2');
    $routes->delete('(:num)/notes/(:num)/media/(:num)', 'Bikes\Api\BikeNoteMedia::delete/$1/$2/$3');
    $routes->post('(:num)/notes/(:num)/media/reorder', 'Bikes\Api\BikeNoteMedia::reorder/$1/$2');
});

// Admin rides routes (adminfilter applied globally via Filters.php)
$routes->group('admin/rides', function ($routes) {
    $routes->get('/', 'Rides\Admin\Home::index');
    $routes->post('delete', 'Rides\Admin\Home::delete');
    $routes->get('create', 'Rides\Admin\RideForm::create');
    $routes->get('(:num)/edit', 'Rides\Admin\RideForm::edit/$1');
});

// API rides routes (apifilter applied globally via Filters.php)
$routes->group('api/rides', function ($routes) {
    $routes->options('(:any)', static function () { return ''; });
    $routes->post('upload', 'Rides\Api\Rides::upload');
    $routes->put('(:num)', 'Rides\Api\Rides::update/$1');
    $routes->post('(:num)/photos', 'Rides\Api\RidePhotos::upload/$1');
    $routes->delete('(:num)/photos/(:num)', 'Rides\Api\RidePhotos::delete/$1/$2');
    $routes->post('(:num)/photos/reorder', 'Rides\Api\RidePhotos::reorder/$1');
});

// Admin todo routes (adminfilter applied globally via Filters.php)
$routes->group('admin/todo', function ($routes) {
    $routes->get('/', 'Todo\Admin\Home::index');
});

// API todo routes (apifilter applied globally via Filters.php)
$routes->group('api/todo', function ($routes) {
    $routes->options('(:any)', static function () { return ''; });
    $routes->get('items', 'Todo\Api\TodoItems::index');
    $routes->get('counts', 'Todo\Api\TodoItems::counts');
    $routes->get('categories', 'Todo\Api\TodoItems::categories');
    $routes->post('items', 'Todo\Api\TodoItems::create');
    $routes->post('items/(:segment)/status', 'Todo\Api\TodoItems::updateStatus/$1');
    $routes->post('items/(:segment)/pin', 'Todo\Api\TodoItems::togglePin/$1');
    $routes->post('items/(:segment)/delete', 'Todo\Api\TodoItems::delete/$1');
    $routes->post('items/(:segment)/restore', 'Todo\Api\TodoItems::restore/$1');
    $routes->post('items/(:segment)/destroy', 'Todo\Api\TodoItems::destroy/$1');
    $routes->post('items/(:segment)', 'Todo\Api\TodoItems::update/$1');
});

// Admin AI chat routes (adminfilter applied globally via Filters.php)
$routes->group('admin/ai', function ($routes) {
    $routes->get('/', 'Ai\Admin\Home::index');
    $routes->get('chat', 'Ai\Admin\Chat::index');
    $routes->get('chat/(:segment)', 'Ai\Admin\Chat::session/$1');
    $routes->get('prompt', 'Ai\Admin\Prompt::index');
    $routes->post('prompt/update', 'Ai\Admin\Prompt::update');
    $routes->post('prompt/revert/(:num)', 'Ai\Admin\Prompt::revert/$1');
    $routes->get('openrouter-models', 'Ai\Admin\OpenrouterModels::index');
    $routes->post('openrouter-models/save', 'Ai\Admin\OpenrouterModels::save');
});

// API AI chat routes (apifilter applied globally via Filters.php)
$routes->group('api/ai/chat', function ($routes) {
    $routes->options('(:any)', static function () { return ''; });
    $routes->get('models', 'Ai\Api\Chat::models');
    $routes->get('search', 'Ai\Api\Chat::search');
    $routes->get('sessions', 'Ai\Api\Chat::sessions');
    $routes->post('session', 'Ai\Api\Chat::createSession');
    $routes->patch('session/(:segment)', 'Ai\Api\Chat::updateSession/$1');
    $routes->delete('session/(:segment)', 'Ai\Api\Chat::deleteSession/$1');
    $routes->get('messages/(:segment)', 'Ai\Api\Chat::messages/$1');
    $routes->post('stream', 'Ai\Api\Chat::stream');
    $routes->post('extract-pdf', 'Ai\Api\Chat::extractPdf');
});

// About page route
$routes->get('/about', 'About::index');

// Contact page routes
$routes->get('/contact', 'Contact::index');
$routes->post('/contact/send', 'Contact::send');

// Metrics collection endpoint
$routes->post('/metrics', 'Metrics::receive');

// Admin metrics routes
$routes->group('admin/metrics', function ($routes) {
    $routes->get('/', 'Metrics\Admin\Dashboard::index');
    $routes->get('paths', 'Metrics\Admin\Dashboard::paths');
    $routes->get('log', 'Metrics\Admin\Dashboard::log');
});

// Unauthorised route
$routes->get('/unauthorised', 'Unauthorised::index');
// Custom 404 route
$routes->set404Override('App\Controllers\Errors::show404');