<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tt_faq_sections', function (Blueprint $table)
        {
            $table->id();

            $table->text('label');
            $table->string('icon', 64);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();
            $table->trooperstamps();
        });

        // Seed the original 9 sections and capture their IDs by slug
        $section_ids = [];
        $sections = [
            'registration'  => ['label' => 'Getting Started & Registration', 'icon' => 'fa-user-plus',      'sort_order' => 1],
            'account-types' => ['label' => 'Account Types',                  'icon' => 'fa-id-card',        'sort_order' => 2],
            'organizations' => ['label' => 'Organizations & Club Memberships','icon' => 'fa-sitemap',        'sort_order' => 3],
            'costumes'      => ['label' => 'Costumes',                        'icon' => 'fa-shirt',          'sort_order' => 4],
            'events'        => ['label' => 'Events',                          'icon' => 'fa-calendar',       'sort_order' => 5],
            'signup'        => ['label' => 'Signing Up for Events',           'icon' => 'fa-clipboard-check','sort_order' => 6],
            'guests'        => ['label' => 'Guests',                          'icon' => 'fa-user-group',     'sort_order' => 7],
            'friends'       => ['label' => 'Friends',                         'icon' => 'fa-handshake',      'sort_order' => 8],
            'videos'        => ['label' => 'How-To Videos',                   'icon' => 'fa-circle-play',    'sort_order' => 9],
        ];

        $now = now();
        foreach ($sections as $slug => $data)
        {
            $section_ids[$slug] = DB::table('tt_faq_sections')->insertGetId([
                'label'      => $data['label'],
                'icon'       => $data['icon'],
                'sort_order' => $data['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Add section_id to tt_faq (nullable during transition)
        Schema::table('tt_faq', function (Blueprint $table)
        {
            $table->foreignId('section_id')
                ->nullable()
                ->after('id')
                ->constrained('tt_faq_sections')
                ->cascadeOnDelete();
        });

        // Populate section_id from the old section slug column
        foreach ($section_ids as $slug => $id)
        {
            DB::table('tt_faq')
                ->where('section', $slug)
                ->update(['section_id' => $id]);
        }

        // Drop the old section column
        Schema::table('tt_faq', function (Blueprint $table)
        {
            $table->dropColumn('section');
        });
    }

    public function down(): void
    {
        Schema::table('tt_faq', function (Blueprint $table)
        {
            $table->string('section', 64)->nullable()->after('id');
        });

        // Restore section slugs from section labels
        $slug_map = [
            'Getting Started & Registration'  => 'registration',
            'Account Types'                   => 'account-types',
            'Organizations & Club Memberships'=> 'organizations',
            'Costumes'                        => 'costumes',
            'Events'                          => 'events',
            'Signing Up for Events'           => 'signup',
            'Guests'                          => 'guests',
            'Friends'                         => 'friends',
            'How-To Videos'                   => 'videos',
        ];

        foreach ($slug_map as $label => $slug)
        {
            $section_id = DB::table('tt_faq_sections')->where('label', $label)->value('id');
            if ($section_id)
            {
                DB::table('tt_faq')->where('section_id', $section_id)->update(['section' => $slug]);
            }
        }

        Schema::table('tt_faq', function (Blueprint $table)
        {
            $table->dropForeign(['section_id']);
            $table->dropColumn('section_id');
        });

        Schema::dropIfExists('tt_faq_sections');
    }
};
