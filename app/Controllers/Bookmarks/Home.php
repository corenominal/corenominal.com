<?php

namespace App\Controllers\Bookmarks;

use App\Controllers\BaseController;
use App\Models\BookmarkModel;
use CodeIgniter\HTTP\ResponseInterface;

class Home extends BaseController
{
    public function index(): string
    {
        $limit         = 20;
        $query         = trim((string) $this->request->getGet('q'));
        $bookmarksData = $this->getBookmarksBatch(0, $limit, $query);

        return view('bookmarks/home', [
            'title'             => 'Bookmarks',
            'js'                => ['bookmarks/home'],
            'css'               => [],
            // 'templateMaxWidth'  => '100%',
            'bookmarks'         => $bookmarksData['bookmarks'],
            'hasMoreBookmarks'  => $bookmarksData['hasMore'],
            'bookmarkBatchSize' => $limit,
            'searchQuery'       => $query,
        ]);
    }

    public function show(string $uuid): string
    {
        $bookmarkModel = new BookmarkModel();
        $bookmark      = $bookmarkModel->where('uuid', $uuid)->first();

        if ($bookmark === null) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Bookmark not found.');
        }

        if (! user_in_group('administrators') && ! empty($bookmark['private'])) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Bookmark not found.');
        }

        if (! user_in_group('administrators')) {
            $bookmarkModel->set('hitcounter', 'hitcounter + 1', false)
                ->where('id', $bookmark['id'])
                ->update();

            $bookmark['hitcounter'] = (int) ($bookmark['hitcounter'] ?? 0) + 1;
        }

        return view('bookmarks/bookmark', [
            'title'            => esc($bookmark['title']),
            'js'               => ['bookmarks/home'],
            'css'              => [],
            // 'templateMaxWidth' => '640px',
            'bookmark'         => $bookmark,
            'backUrl'          => site_url('bookmarks'),
            'backLabel'        => 'Back to bookmarks',
        ]);
    }

    public function loadMore(): ResponseInterface
    {
        $offset = max(0, (int) $this->request->getGet('offset'));
        $limit  = (int) $this->request->getGet('limit');
        $query  = trim((string) $this->request->getGet('q'));

        $limit = max(1, min(50, $limit ?: 20));

        $bookmarksData = $this->getBookmarksBatch($offset, $limit, $query);

        $html = view('bookmarks/partials/bookmark_items', [
            'bookmarks' => $bookmarksData['bookmarks'],
        ]);

        return $this->response->setJSON([
            'html'       => $html,
            'nextOffset' => $offset + count($bookmarksData['bookmarks']),
            'hasMore'    => $bookmarksData['hasMore'],
        ]);
    }

    private function getBookmarksBatch(int $offset, int $limit, string $query = ''): array
    {
        $bookmarkModel = new BookmarkModel();

        if (! user_in_group('administrators')) {
            $bookmarkModel->where('private', 0);
        }

        if ($query !== '') {
            $bookmarkModel
                ->groupStart()
                ->like('title', $query)
                ->orLike('notes', $query)
                ->orLike('tags', $query)
                ->groupEnd();
        }

        $rows = $bookmarkModel
            ->orderBy('created_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll($limit + 1, $offset);

        $hasMore   = count($rows) > $limit;
        $bookmarks = array_slice($rows, 0, $limit);

        return [
            'bookmarks' => $bookmarks,
            'hasMore'   => $hasMore,
        ];
    }
}
