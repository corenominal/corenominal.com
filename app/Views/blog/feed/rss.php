<?php echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n"; ?><rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title><?= esc((string)$title) ?></title>
        <link><?= esc((string)$baseURL) ?></link>
        <description><?= esc((string)$description) ?></description>
        <language>en-gb</language>
        <atom:link href="<?= esc((string)$feedURL) ?>" rel="self" type="application/rss+xml" />
        <?php foreach ($posts as $post) : ?>
        <item>
            <title><?= esc((string)$post['title']) ?></title>
            <link><?= esc((string)$baseURL) ?>/blog/posts/<?= esc((string)$post['slug']) ?></link>
            <guid isPermaLink="true"><?= esc((string)$baseURL) ?>/blog/posts/<?= esc((string)$post['slug']) ?></guid>
            <?php if (! empty($post['excerpt'])) : ?>
            <description><?= esc((string)$post['excerpt']) ?></description>
            <?php endif; ?>
            <pubDate><?= date('r', strtotime($post['published_at'])) ?></pubDate>
        </item>
        <?php endforeach; ?>
    </channel>
</rss>
