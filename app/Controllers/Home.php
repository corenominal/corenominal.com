<?php

namespace App\Controllers;

use App\Models\BikeModel;
use App\Models\BookmarkModel;
use App\Models\GitHubActivityModel;
use App\Models\PostModel;
use App\Models\RideModel;
use App\Models\RidePhotoModel;

class Home extends BaseController
{
    public function index()
    {
        // Check if there are any users in the database, and if not, redirect to the login page to encourage setup.
        $userModel = model('UserModel');
        if ($userModel->countAllResults() === 0) {
            return redirect()->to('/auth/register');
        }

        helper(['status', 'bookmark', 'rides']);

        // Get the latest status post
        $model  = model('StatusModel');
        $status = $model->orderBy('created_at', 'DESC')->first();

        $latestBookmarkRow = (new BookmarkModel())
            ->where('private', 0)
            ->orderBy('created_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->first();

        $postModel = new PostModel();
        $recentPosts = $postModel
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->orderBy('published_at', 'DESC')
            ->findAll(4);

        $latestPost  = $recentPosts[0] ?? null;
        $morePosts   = array_slice($recentPosts, 0);

        $data['status']          = $status !== null ? status_with_media($status) : null;
        $data['latestPost']      = $latestPost;
        $data['morePosts']       = $morePosts;
        $data['latestBookmark']  = $latestBookmarkRow !== null ? bookmark_with_tags($latestBookmarkRow) : null;
        $data['mastodonHandle']  = config('Mastodon')->account;
        $data['mastodonProfile'] = config('Mastodon')->profile;
        $githubModel             = new GitHubActivityModel();
        $githubGrouped           = $githubModel->getGroupedByDate(98);
        $heatmap                 = [];
        for ($i = 97; $i >= 0; $i--) {
            $d             = date('Y-m-d', strtotime("-{$i} days"));
            $heatmap[$d]   = count($githubGrouped[$d] ?? []);
        }
        $data['githubHeatmap']   = $heatmap;
        $data['githubActivity']  = $githubGrouped;

        $latestRide = (new RideModel())
            ->orderBy('started_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->first();

        $latestRideCover    = null;
        $latestRideBikeName = null;

        if ($latestRide !== null) {
            $photo = (new RidePhotoModel())
                ->where('ride_id', $latestRide['id'])
                ->orderBy('sort_order', 'ASC')
                ->first();
            $latestRideCover = $photo['file_name'] ?? null;

            if (! empty($latestRide['bike_id'])) {
                $bike = (new BikeModel())->find($latestRide['bike_id']);
                if ($bike) {
                    $latestRideBikeName = $bike['name'] !== null && $bike['name'] !== ''
                        ? $bike['name']
                        : trim($bike['brand'] . ' ' . $bike['model']);
                }
            }
        }

        $data['latestRide']         = $latestRide;
        $data['latestRideCover']    = $latestRideCover;
        $data['latestRideBikeName'] = $latestRideBikeName;

        $data['js']              = ['home'];
        $data['css']             = ['status/timeline', 'github-heatmap'];
        $data['title']           = 'Tech Enthusiast and Web Developer';
        return view('home', $data);
    }
}
