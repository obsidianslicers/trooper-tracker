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

        $now = now();
        $sections = [
            ['label' => 'Getting Started & Registration',  'icon' => 'fa-user-plus',       'sort_order' => 1],
            ['label' => 'Account Types',                   'icon' => 'fa-id-card',          'sort_order' => 2],
            ['label' => 'Organizations & Club Memberships','icon' => 'fa-sitemap',          'sort_order' => 3],
            ['label' => 'Costumes',                        'icon' => 'fa-shirt',            'sort_order' => 4],
            ['label' => 'Events',                          'icon' => 'fa-calendar',         'sort_order' => 5],
            ['label' => 'Signing Up for Events',           'icon' => 'fa-clipboard-check',  'sort_order' => 6],
            ['label' => 'Guests',                          'icon' => 'fa-user-group',       'sort_order' => 7],
            ['label' => 'Friends',                         'icon' => 'fa-handshake',        'sort_order' => 8],
            ['label' => 'How-To Videos',                   'icon' => 'fa-circle-play',      'sort_order' => 9],
        ];

        foreach ($sections as &$section)
        {
            $section['created_at'] = $now;
            $section['updated_at'] = $now;
        }

        DB::table('tt_faq_sections')->insert($sections);

        Schema::create('tt_faq', function (Blueprint $table)
        {
            $table->id();

            $table->foreignId('section_id')->constrained('tt_faq_sections')->cascadeOnDelete();
            $table->text('title');
            $table->text('description')->nullable();
            $table->string('video_url', 512)->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();
            $table->trooperstamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tt_faq');
        Schema::dropIfExists('tt_faq_sections');
    }
};
