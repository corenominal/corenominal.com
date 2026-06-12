<?php

namespace App\Controllers\Bookmarks;

use App\Controllers\BaseController;
use App\Models\BookmarkModel;
use CodeIgniter\HTTP\ResponseInterface;

class Feed extends BaseController
{
    public function rss(): ResponseInterface
    {
        $bookmarks = (new BookmarkModel())
            ->where('private', 0)
            ->orderBy('created_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll(20);

        $feedUrl  = site_url('bookmarks/feed/rss');
        $homeUrl  = site_url('bookmarks/');
        $siteName = esc((string) config('App')->siteName);
        $now      = date('r');

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
        $xml .= '  <channel>' . "\n";
        $xml .= '    <title>' . $siteName . ' — Bookmarks</title>' . "\n";
        $xml .= '    <link>' . $homeUrl . '</link>' . "\n";
        $xml .= '    <description>Latest bookmarks from ' . $siteName . '</description>' . "\n";
        $xml .= '    <language>en-GB</language>' . "\n";
        $xml .= '    <lastBuildDate>' . $now . '</lastBuildDate>' . "\n";
        $xml .= '    <atom:link href="' . $feedUrl . '" rel="self" type="application/rss+xml"/>' . "\n";

        foreach ($bookmarks as $bookmark) {
            $permalink   = site_url('bookmarks/' . $bookmark['uuid']);
            $title       = htmlspecialchars((string) $bookmark['title'], ENT_XML1, 'UTF-8');
            $pubDate     = date('r', strtotime((string) $bookmark['created_at']));
            $description = '<![CDATA[' . ($bookmark['notes_html'] ?? '') . ']]>';

            $xml .= '    <item>' . "\n";
            $xml .= '      <title>' . $title . '</title>' . "\n";
            $xml .= '      <link>' . esc($bookmark['url']) . '</link>' . "\n";
            $xml .= '      <guid isPermaLink="true">' . $permalink . '</guid>' . "\n";
            $xml .= '      <pubDate>' . $pubDate . '</pubDate>' . "\n";
            $xml .= '      <description>' . $description . '</description>' . "\n";
            $xml .= '    </item>' . "\n";
        }

        $xml .= '  </channel>' . "\n";
        $xml .= '</rss>' . "\n";

        return $this->response
            ->setHeader('Content-Type', 'application/rss+xml; charset=utf-8')
            ->setBody($xml);
    }
}
