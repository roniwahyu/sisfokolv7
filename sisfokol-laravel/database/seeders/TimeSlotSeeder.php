<?php

namespace Database\Seeders;

use App\Models\Hour;
use App\Models\TimeSlot;
use Illuminate\Database\Seeder;

class TimeSlotSeeder extends Seeder
{
    public function run(): void
    {
        $hours = Hour::all();

        foreach ($hours as $hour) {
            TimeSlot::create([
                'name' => $hour->name,
                'hour_id' => $hour->id,
                'start_time' => $hour->start_time,
                'end_time' => $hour->end_time,
                'order' => $hour->order,
            ]);
        }
    }
}
