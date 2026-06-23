<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EducationArticle;

class EducationArticleSeeder extends Seeder
{
    public function run(): void
    {
        EducationArticle::factory()->count(10)->create();
    }
}
