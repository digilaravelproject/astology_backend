<?php

namespace Database\Seeders;

use App\Models\FoundersWord;
use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $languages = [
            [
                'name' => 'English',
                'code' => 'en',
                'is_active' => true,
            ],
            [
                'name' => 'Hindi',
                'code' => 'hi',
                'is_active' => true,
            ],
            [
                'name' => 'Marathi',
                'code' => 'mr',
                'is_active' => true,
            ],
        ];

        foreach ($languages as $langData) {
            Language::firstOrCreate(
                ['code' => $langData['code']],
                ['name' => $langData['name'], 'is_active' => $langData['is_active']]
            );
        }

        // Backfill existing founders_words with default language (English) if language_id is null
        $defaultLang = Language::where('code', 'en')->first() ?? Language::first();
        if ($defaultLang) {
            FoundersWord::whereNull('language_id')->update(['language_id' => $defaultLang->id]);
        }
    }
}
