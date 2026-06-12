<?php

use App\Models\BookmarkTagModel;

if (! function_exists('bookmark_with_tags')) {
    function bookmark_with_tags(array $bookmark): array
    {
        $bookmark['tagList'] = [];

        $id = isset($bookmark['id']) ? (int) $bookmark['id'] : 0;

        if ($id <= 0) {
            return $bookmark;
        }

        $rows = (new BookmarkTagModel())->where('bookmark_id', $id)->orderBy('tag', 'ASC')->findAll();

        foreach ($rows as $row) {
            $bookmark['tagList'][] = bookmark_tag_row_to_array($row);
        }

        return $bookmark;
    }
}

if (! function_exists('bookmark_tag_row_to_array')) {
    function bookmark_tag_row_to_array(array $row): array
    {
        return [
            'id'   => (int) $row['id'],
            'tag'  => (string) ($row['tag'] ?? ''),
            'slug' => (string) ($row['slug'] ?? ''),
        ];
    }
}
