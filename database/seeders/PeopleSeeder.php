<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use App\Models\{Person, Picture};

class PeopleSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $p = Person::create([
              'name' => Arr::random(['Esther','Mina','Yuna','Irene','Jisoo','Haerin','Sana','Nayeon']) . " ".($i + 1),
              'age'  => rand(20, 35),
              'lat'  => -6.2 + (mt_rand(-50, 50) / 1000),
              'lng'  => 106.8 + (mt_rand(-50, 50) / 1000),
              'city' => 'Jakarta',
            ]);
            Picture::create([
              'person_id' => $p->id,
              'url' => "https://picsum.photos/seed/".($i + 1)."/900/1200.jpg",
              'sort_order' => 0
            ]);
        }
    }
}
