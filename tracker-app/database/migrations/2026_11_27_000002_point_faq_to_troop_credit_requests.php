<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The FAQ answer for a missing troop sent troopers to their unit leader. Troopers can now ask
 * for credit themselves, so the answer points to Request Troop Credit.
 *
 * Only an answer still as FaqSeeder wrote it is changed: one Command Staff edited keeps their
 * text.
 */
return new class extends Migration {
    private const SECTION = 'Signing Up for Events';

    private const TITLE = 'I am missing troop data / My troop data is incorrect.';

    /** md5 of the seeded answer, "Please refer to your unit leader to get this corrected." */
    private const WAS = 'ec7117938bb076eada99dc0075a3bc9a';

    public const DESCRIPTION = <<<'MD'
Trooped an event but never signed up for it? Go to **Service Records → Request Troop Credit**, find the event by its date or title, and tell us what proves you were there. You can also press **I was here, request credit** on the page of any completed event.

The event coordinator or a moderator checks your request, and you'll get a notification when it's decided.

If something else in your troop history is wrong, contact your unit leader.
MD;

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

        $row = DB::table('tt_faq')
            ->where('section_id', $section_id)
            ->where('title', self::TITLE)
            ->whereNull('deleted_at')
            ->first();

        if ($row === null || md5(trim(str_replace("\r\n", "\n", (string) $row->description))) !== self::WAS)
        {
            return;
        }

        DB::table('tt_faq')->where('id', $row->id)->update([
            'description' => self::DESCRIPTION,
            'updated_at' => now(),
        ]);
    }

    /**
     * The old answer is still right for anyone who can't use the new page, but it sends
     * troopers the long way round; there is no reason to put it back.
     */
    public function down(): void
    {
    }
};
