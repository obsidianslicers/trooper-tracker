<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Points the FAQ's How-To Videos at new recordings of the current screens, and adds videos for
 * newer features. They are recorded by tests/how-to-videos.
 *
 * The FAQ belongs to each garrison, so only a video still linked to the seeded recording is
 * changed, and a video already in the section isn't added twice. A tracker without the
 * seeded How-To Videos section is left alone.
 */
return new class extends Migration {
    private const SECTION = 'How-To Videos';

    /** Seeded videos: title => [the seeded link, the new one]. */
    private const CHANGED = [
        'Getting Started & Registration' => [
            'https://www.youtube.com/watch?v=JPkDomoU86s',
            'https://youtu.be/fn0cDEYxhzM',
        ],
        'Setting Up Your Profile' => [
            'https://youtu.be/2Yfe1gDFs68',
            'https://youtu.be/ssFal7Gze1g',
        ],
        'Adding Your Costumes' => [
            'https://youtu.be/dZH80acyRvg',
            'https://youtu.be/jMReRxycjMw',
        ],
        'Browsing & Joining Events' => [
            'https://youtu.be/HIFjIoTPnTc',
            'https://youtu.be/VyIicG_uc1M',
        ],
        'Reading Your Service Record' => [
            'https://youtu.be/0TnnUMDEejA',
            'https://youtu.be/cDWCMJEh7bY',
        ],
        'Adding An Organization' => [
            'https://youtu.be/dkliWoLKrq0',
            'https://youtu.be/1f2PKUBO-eQ',
        ],
        'Command Staff - Overview' => [
            'https://youtu.be/DJcNoX0rQ5o',
            'https://youtu.be/vPCw9t5fIV4',
        ],
        'Command Staff - Create & Manage Events' => [
            'https://youtu.be/9221Iqdo35Q',
            'https://youtu.be/jEBbBa6wVLk',
        ],
        'Command Staff - Trooper Requests' => [
            'https://youtu.be/tZpetAW2ob4',
            'https://youtu.be/mB-AChFaEbc',
        ],
    ];

    /** New videos: title => link, in order after the seeded ones. */
    private const ADDED = [
        'Confirming Your Troop' => 'https://youtu.be/1lGCBTWXAws',
        'Bringing Guests & Friends' => 'https://youtu.be/gA8UYVeiGVI',
        'Uploading & Tagging Photos' => 'https://youtu.be/iafTwbsrQlc',
        'Choosing Your Notifications' => 'https://youtu.be/eAJB32jw810',
        'Linking Trackers with the HoloNet' => 'https://youtu.be/E43C3HLT4Eg',
        'Command Staff - Approving Costumes' => 'https://youtu.be/f3Vly_WZtVA',
        'Command Staff - Roles & Permissions' => 'https://youtu.be/elk6L_oB3fk',
    ];

    public function up(): void
    {
        $section_id = DB::table('tt_faq_sections')
            ->where('label', self::SECTION)
            ->whereNull('deleted_at')
            ->value('id');

        if ($section_id === null)
        {
            return;
        }

        foreach (self::CHANGED as $title => [$was, $now])
        {
            $this->items((int) $section_id, $title)
                ->where('video_url', $was)
                ->update(['video_url' => $now, 'updated_at' => now()]);
        }

        $this->addVideos((int) $section_id);
    }

    /**
     * The old recordings showed screens that no longer exist, so there is nothing to restore.
     */
    public function down(): void {}

    private function addVideos(int $section_id): void
    {
        $sort_order = (int) DB::table('tt_faq')
            ->where('section_id', $section_id)
            ->whereNull('deleted_at')
            ->max('sort_order');

        foreach (self::ADDED as $title => $url)
        {
            if ($this->items($section_id, $title)->exists())
            {
                continue;
            }

            DB::table('tt_faq')->insert([
                'section_id' => $section_id,
                'title' => $title,
                'video_url' => $url,
                'sort_order' => ++$sort_order,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function items(int $section_id, string $title): Builder
    {
        return DB::table('tt_faq')
            ->where('section_id', $section_id)
            ->where('title', $title)
            ->whereNull('deleted_at');
    }
};
