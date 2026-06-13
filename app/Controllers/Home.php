<?php

namespace App\Controllers;

use App\Models\BookmarkModel;
use App\Models\GitHubActivityModel;

class Home extends BaseController
{
    public function index()
    {
        // Check if there are any users in the database, and if not, redirect to the login page to encourage setup.
        $userModel = model('UserModel');
        if ($userModel->countAllResults() === 0) {
            return redirect()->to('/auth/register');
        }
        // If logged in show home page, otherwise show under construction page
        if(is_logged_in()) {
            helper(['status', 'bookmark']);

            // Get the latest status post
            $model  = model('StatusModel');
            $status = $model->orderBy('created_at', 'DESC')->first();

            $latestBookmarkRow = (new BookmarkModel())
                ->where('private', 0)
                ->orderBy('created_at', 'DESC')
                ->orderBy('id', 'DESC')
                ->first();

            $data['status']          = $status !== null ? status_with_media($status) : null;
            $data['latestBookmark']  = $latestBookmarkRow !== null ? bookmark_with_tags($latestBookmarkRow) : null;
            $data['mastodonHandle']  = config('Mastodon')->account;
            $data['mastodonProfile'] = config('Mastodon')->profile;
            $githubModel             = new GitHubActivityModel();
            $githubGrouped           = $githubModel->getGroupedByDate(56);
            $heatmap                 = [];
            for ($i = 55; $i >= 0; $i--) {
                $d             = date('Y-m-d', strtotime("-{$i} days"));
                $heatmap[$d]   = count($githubGrouped[$d] ?? []);
            }
            $data['githubHeatmap']   = $heatmap;
            $data['githubActivity']  = $githubGrouped;
            $data['js']              = ['home'];
            $data['css']             = ['status/timeline', 'github-heatmap'];
            $data['title']           = 'Tech Enthusiast and Web Developer';
            return view('home', $data);
        } else {
            $data['title'] = 'Under Construction';
            return view('under-construction', $data);
        }
    }
}
