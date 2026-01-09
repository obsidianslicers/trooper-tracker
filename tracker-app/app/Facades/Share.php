<?php

namespace App\Facades;

use Illuminate\Support\Arr;

class Share
{
    /**
     * The url of the page to share
     *
     * @var string
     */
    private string $url;

    /**
     * The generated urls
     *
     * @var string
     */
    private array $social_urls = [];

    /**
     * Optional text for Twitter
     * and Linkedin title
     *
     * @var string
     */
    private string $title;

    /**
     * Return a string with html at the end
     * of the chain.
     *
     * @return string
     */
    public function __toString()
    {
        $html = '<div class="btn-group" role="group">';

        foreach ($this->social_urls as $provider => $url)
        {
            $fa_icon = config("share.{$provider}.fa-icon");

            $html .= '<a href="' . e($url) . '" class="btn btn-outline-secondary" target="_blank" rel="noopener noreferrer">';
            $html .= '<i class="fa fa-brands ' . e($fa_icon) . '"></i>';
            $html .= '</a>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * @param string $url
     * @param string $title
     * @return $this
     */
    public function page($url, $title)
    {
        $this->url = $url;
        $this->title = $title;

        return $this;
    }

    /**
     * @param string|null $title
     * @param array $options
     * @param string|null $prefix
     * @param string|null $suffix
     * @return $this
     */
    public function currentPage($title = null)
    {
        $url = request()->getUri();

        return $this->page($url, $title);
    }

    /**
     * Facebook share link
     *
     * @return $this
     */
    public function facebook()
    {
        $url = config('share.facebook.uri') . $this->url;

        $this->buildLink('facebook', $url);

        return $this;
    }

    /**
     * Twitter share link
     *
     * @return $this
     */
    public function twitter()
    {
        $base = config('share.twitter.uri');
        $url = $base . '?text=' . urlencode($this->title) . '&url=' . $this->url;

        $this->buildLink('twitter', $url);

        return $this;
    }

    /**
     * Reddit share link
     *
     * @return $this
     */
    public function reddit()
    {
        $base = config('share.reddit.uri');
        $url = $base . '?title=' . urlencode($this->title) . '&url=' . $this->url;

        $this->buildLink('reddit', $url);

        return $this;
    }

    /**
     * Telegram share link
     *
     * @return $this
     */
    public function telegram()
    {
        $base = config('share.telegram.uri');
        $url = $base . '?url=' . $this->url . '&text=' . urlencode($this->title);

        $this->buildLink('telegram', $url);

        return $this;
    }

    /**
     * Whatsapp share link
     *
     * @return $this
     */
    public function whatsapp()
    {
        $url = config('share.whatsapp.uri') . $this->url;

        $this->buildLink('whatsapp', $url);

        return $this;
    }

    /**
     * Linked in share link
     *
     * @return $this
     */
    public function linkedin()
    {
        $base = config('share.linkedin.uri');
        $mini = config('share.linkedin.extra.mini');
        $url = $base . '?mini=' . $mini . '&url=' . $this->url . '&title=' . urlencode($this->title);

        $this->buildLink('linkedin', $url);

        return $this;
    }

    /**
     * Pinterest share link
     *
     * @return $this
     */
    public function pinterest()
    {
        $url = config('share.pinterest.uri') . $this->url;

        $this->buildLink('pinterest', $url);

        return $this;
    }

    /**
     * Get the raw generated links.
     *
     * @return string|array
     */
    public function getRawLinks()
    {
        if (count($this->social_urls) === 1)
        {
            return Arr::first($this->social_urls);
        }

        return $this->social_urls;
    }

    /**
     * Build a single link
     *
     * @param string $provider
     * @param string $url
     */
    private function buildLink($provider, $url)
    {

        $this->social_urls[$provider] = $url;
    }
}
