<?php

namespace App\Libraries;

use League\CommonMark\GithubFlavoredMarkdownConverter;

/**
 * Markdown library for converting Markdown to HTML.
 *
 * Wraps the League CommonMark GitHub Flavored Markdown converter,
 * providing set/get accessors and a convert method.
 */
class Markdown
{
    private $markdown;

    public function __construct()
    {

    }

    /**
     * Set the Markdown.
     *
     * @param string $title The markdown to set.
     * @return void
     */
    public function setMarkdown($markdown)
    {
        // Validate the markdown is a string
        if (!is_string($markdown)) {
            throw new \InvalidArgumentException("Markdown must be a string");
        }
        // Validate the markdown is not empty
        if (empty($markdown)) {
            throw new \InvalidArgumentException("Markdown cannot be empty");
        }
        $this->markdown = $markdown;
    }

    /**
     * Get the Markdown.
     *
     * @return string The Markdown.
     */
    public function getMarkdown()
    {
        return $this->markdown ?? null;
    }

    /**
     * Convert the Markdown to HTML.
     *
     * @return string The converted HTML.
     */
    public function convert()
    {
        // Prepare the data to be sent
        $data = ['markdown' => $this->markdown];

        // Throw an exception if any required fields are missing
        if (empty($this->markdown)) {
            throw new \InvalidArgumentException("Markdown field is required");
        }

        // Convert the markdown to HTML
        $converter = new GithubFlavoredMarkdownConverter();
        $html = $converter->convert($data['markdown']);
        $html = $html->getContent();
        return $html;
    }
}