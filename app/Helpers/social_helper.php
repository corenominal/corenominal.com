<?php

/**
 * Returns <link rel="me"> tags for all active social verification tags.
 */
function social_verification_tags(): string
{
    try {
        $model = new \App\Models\SocialVerificationTagModel();
        $tags  = $model->select('url')->findAll();

        if (empty($tags)) {
            return '';
        }

        $html = '';
        foreach ($tags as $tag) {
            $html .= '<link rel="me" href="' . esc($tag['url'], 'attr') . '">' . "\n";
        }

        return $html;
    } catch (\Throwable $e) {
        return '';
    }
}
