<?php

declare(strict_types=1);

namespace App\Facades;

use Illuminate\Support\Arr;

class Share
{
    /**
     * The url of the page to share
     */
    private string $url;

    /**
     * The generated urls
     *
     * @var array<string, string>
     */
    private array $social_urls = [];

    /**
     * Optional text for Twitter and LinkedIn title
     */
    private string $title;

    /**
     * Return a string with html at the end of the chain.
     */
    public function __toString(): string
    {
        $html = '<div class="btn-group" role="group">';

        foreach ($this->social_urls as $provider => $url)
        {
            $fa_icon = config("share.{$provider}.fa-icon");

            $html .= '<a href="'.e($url).'" class="btn btn-outline-secondary" target="_blank" rel="noopener noreferrer">';
            $html .= '<i class="fa fa-brands '.e($fa_icon).'"></i>';
            $html .= '</a>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Set the page URL and title to share.
     *
     * @param  string  $url  The URL to share
     * @param  string  $title  The title of the page
     * @return $this
     */
    public function page(string $url, string $title): static
    {
        $this->url = $url;
        $this->title = $title;

        return $this;
    }

    /**
     * Set the current page URL and optional title to share.
     *
     * @param  string|null  $title  Optional title for the current page
     * @return $this
     */
    public function currentPage(?string $title = null): static
    {
        $url = request()->getUri();

        return $this->page($url, $title);
    }

    /**
     * Add Facebook share link.
     *
     * @return $this
     */
    public function facebook(): static
    {
        $url = config('share.facebook.uri').$this->url;

        $this->buildLink('facebook', $url);

        return $this;
    }

    /**
     * Add Twitter share link.
     *
     * @return $this
     */
    public function twitter(): static
    {
        $base = config('share.twitter.uri');
        $url = $base.'?text='.urlencode($this->title).'&url='.$this->url;

        $this->buildLink('twitter', $url);

        return $this;
    }

    /**
     * Add Reddit share link.
     *
     * @return $this
     */
    public function reddit(): static
    {
        $base = config('share.reddit.uri');
        $url = $base.'?title='.urlencode($this->title).'&url='.$this->url;

        $this->buildLink('reddit', $url);

        return $this;
    }

    /**
     * Add Telegram share link.
     *
     * @return $this
     */
    public function telegram(): static
    {
        $base = config('share.telegram.uri');
        $url = $base.'?url='.$this->url.'&text='.urlencode($this->title);

        $this->buildLink('telegram', $url);

        return $this;
    }

    /**
     * Add WhatsApp share link.
     *
     * @return $this
     */
    public function whatsapp(): static
    {
        $url = config('share.whatsapp.uri').$this->url;

        $this->buildLink('whatsapp', $url);

        return $this;
    }

    /**
     * Add LinkedIn share link.
     *
     * @return $this
     */
    public function linkedin(): static
    {
        $base = config('share.linkedin.uri');
        $mini = config('share.linkedin.extra.mini');
        $url = $base.'?mini='.$mini.'&url='.$this->url.'&title='.urlencode($this->title);

        $this->buildLink('linkedin', $url);

        return $this;
    }

    /**
     * Add Pinterest share link.
     *
     * @return $this
     */
    public function pinterest(): static
    {
        $url = config('share.pinterest.uri').$this->url;

        $this->buildLink('pinterest', $url);

        return $this;
    }

    /**
     * Get the raw generated links.
     *
     * @return string|array<string, string>
     */
    public function getRawLinks(): string|array
    {
        if (count($this->social_urls) === 1)
        {
            return Arr::first($this->social_urls);
        }

        return $this->social_urls;
    }

    /**
     * Build a single link.
     *
     * @param  string  $provider  The social media provider name
     * @param  string  $url  The generated share URL
     */
    private function buildLink(string $provider, string $url): void
    {
        $this->social_urls[$provider] = $url;
    }
}
