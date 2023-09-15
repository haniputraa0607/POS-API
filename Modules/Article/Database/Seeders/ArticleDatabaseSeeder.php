<?php

namespace Modules\Article\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Article\Entities\Article;

class ArticleDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();


        $titles = ['Kandungan Daviena Gold Series dalam Semua Produknya', 'Daviena Skincare untuk Usia Berapa? Penjelasannya Disini!', '7 Manfaat Skincare Daviena & Khasiatnya untuk Kulit!'];
        $i=0;
        foreach ($titles as $title) {
            Article::create([
                'title' => $title,
                'description' => 'All in one, Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam ultrices enimacnibh tristique semper. Aliquam at dolor at justo suscipit ullamcorper.',
                'writer' => 'Admin',
                'image' => 'https://daviena.belum.live/images/article/'.($i+1).'.png',
                'release_date' => date('Y-m-d'),
            ]);
            $i++;
        }
    }
}
