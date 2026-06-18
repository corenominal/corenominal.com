<?php

namespace App\Controllers\Startpage\Admin;

use App\Controllers\BaseController;

class Home extends BaseController
{
    public function index(): mixed
    {
        $historyModel   = model('StartHistoryModel');
        $redirectsModel = model('StartRedirectsModel');
        $searchModel    = model('StartSearchModel');
        $categoryModel  = model('StartShortcutCategoryModel');
        $shortcutModel  = model('StartShortcutModel');

        // Handle query string (e.g. from browser address bar)
        if (null !== $this->request->getGet('q')) {
            $q = trim((string) $this->request->getGet('q'));

            if ($q !== '') {
                $existing = $historyModel->where('q', $q)->first();
                if ($existing) {
                    $historyModel->update($existing['id'], ['count' => $existing['count'] + 1]);
                } else {
                    $historyModel->insert(['q' => $q, 'count' => 1]);
                }

                $redirect = $redirectsModel->where('phrase', $q)->first();
                if ($redirect) {
                    return redirect()->to($redirect['url']);
                }

                $searchEngines = $searchModel->findAll();
                foreach ($searchEngines as $engine) {
                    $prefix = $engine['phrase'] . ' ';
                    if (strncmp($q, $prefix, strlen($prefix)) === 0) {
                        $term = substr($q, strlen($prefix));
                        $url  = str_replace('%s', urlencode($term), $engine['url']);
                        return redirect()->to($url);
                    }
                }

                return redirect()->to('https://www.google.com/search?q=' . urlencode($q));
            }
        }

        $data['history']        = $historyModel->orderBy('updated_at', 'DESC')->findAll(50);
        $data['redirects']      = $redirectsModel->findAll();
        $data['search_engines'] = $searchModel->findAll();

        $categories = $categoryModel->orderBy('sort_order', 'ASC')->findAll();
        foreach ($categories as &$cat) {
            $cat['shortcuts'] = $shortcutModel
                ->where('category_id', $cat['id'])
                ->orderBy('sort_order', 'ASC')
                ->findAll();
        }
        unset($cat);
        $data['shortcut_categories'] = $categories;

        $data['js']              = ['startpage/admin/home'];
        $data['css']             = ['startpage/admin/home'];
        $data['title']           = 'Start Page';
        $data['templateMenu']    = 'admin/sidebar-menu';
        $data['templateMaxWidth'] = '100%';
        $data['manifest']        = '/manifest-startpage.json';

        return view('startpage/admin/home', $data);
    }

    public function command(): \CodeIgniter\HTTP\ResponseInterface
    {
        if (! $this->request->is('json')) {
            return $this->response->setJSON(['error' => 'Expecting JSON data.']);
        }

        $data = $this->request->getJSON(true);

        if (empty($data['q'])) {
            return $this->response->setJSON(['error' => 'No query provided.']);
        }

        $q = trim((string) $data['q']);

        $historyModel = model('StartHistoryModel');
        $existing     = $historyModel->where('q', $q)->first();
        if ($existing === null) {
            $historyModel->insert(['q' => $q, 'count' => 1]);
        } else {
            $historyModel->update($existing['id'], ['count' => $existing['count'] + 1]);
        }

        $response      = $this->processQuery($q);
        $response['q'] = $q;

        return $this->response->setJSON($response);
    }

    public function historySuggestions(): \CodeIgniter\HTTP\ResponseInterface
    {
        $q = trim((string) $this->request->getGet('q'));

        if ($q === '') {
            return $this->response->setJSON([]);
        }

        $model   = model('StartHistoryModel');
        $results = $model
            ->like('q', $q)
            ->orderBy('count', 'DESC')
            ->orderBy('updated_at', 'DESC')
            ->limit(10)
            ->findAll();

        return $this->response->setJSON(array_column($results, 'q'));
    }

    private function processQuery(string $q): array
    {
        if (str_starts_with($q, 'https://') || str_starts_with($q, 'http://')) {
            if (filter_var($q, FILTER_VALIDATE_URL)) {
                return ['url' => $q];
            }
        }

        if (str_starts_with($q, '/')) {
            return $this->processCommand($q);
        }

        $redirectsModel = model('StartRedirectsModel');
        $redirect       = $redirectsModel->where('phrase', $q)->first();
        if ($redirect) {
            return ['url' => $redirect['url']];
        }

        if (strpos($q, ' ') !== false) {
            $terms       = explode(' ', $q);
            $searchModel = model('StartSearchModel');
            $search      = $searchModel->where('phrase', $terms[0])->first();
            if ($search) {
                unset($terms[0]);
                $term = implode(' ', $terms);
                $url  = str_replace('%s', urlencode($term), $search['url']);
                return ['url' => $url];
            }
        }

        return ['url' => 'https://www.google.com/search?q=' . urlencode($q)];
    }

    private function processCommand(string $q): array
    {
        if ($q === '/ping') {
            return ['html' => '<p>pong!</p>'];
        }

        if ($q === '/hello') {
            return ['html' => '<p>Hello, World!</p>'];
        }

        if (strpos($q, ' ') !== false) {
            [$command, $rest] = explode(' ', $q, 2);
            $args             = trim($rest);

            if ($command === '/ping') {
                $output = shell_exec('ping -c 5 ' . escapeshellarg($args)) ?? '';
                return ['html' => '<pre><code>' . htmlspecialchars($output, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code></pre>'];
            }

            if ($command === '/whois') {
                $output = shell_exec('whois ' . escapeshellarg($args)) ?? '';
                return ['html' => '<pre><code>' . htmlspecialchars($output, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code></pre>'];
            }

            if ($command === '/dig') {
                $output = shell_exec('dig ' . escapeshellarg($args)) ?? '';
                return ['html' => '<pre><code>' . htmlspecialchars($output, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code></pre>'];
            }

            if ($command === '/headers') {
                $output = shell_exec('curl --head ' . escapeshellarg($args)) ?? '';
                return ['html' => '<pre><code>' . htmlspecialchars($output, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code></pre>'];
            }

            if ($command === '/traceroute') {
                $output = shell_exec('timeout 15 traceroute -w 2 -q 1 -m 20 ' . escapeshellarg($args) . ' 2>&1') ?? '';
                return ['html' => '<pre><code>' . htmlspecialchars($output, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code></pre>'];
            }

            if ($command === '/mx') {
                $output = shell_exec('dig MX ' . escapeshellarg($args)) ?? '';
                return ['html' => '<pre><code>' . htmlspecialchars($output, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code></pre>'];
            }

            if ($command === '/ns') {
                $output = shell_exec('dig NS ' . escapeshellarg($args)) ?? '';
                return ['html' => '<pre><code>' . htmlspecialchars($output, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code></pre>'];
            }

            if ($command === '/rdns') {
                $output = shell_exec('dig -x ' . escapeshellarg($args)) ?? '';
                return ['html' => '<pre><code>' . htmlspecialchars($output, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code></pre>'];
            }

            if ($command === '/ssl') {
                $output = shell_exec('openssl s_client -connect ' . escapeshellarg($args . ':443') . ' < /dev/null 2>&1') ?? '';
                return ['html' => '<pre><code>' . htmlspecialchars($output, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code></pre>'];
            }
        }

        return ['html' => '<p>Unrecognised command.</p>'];
    }
}
