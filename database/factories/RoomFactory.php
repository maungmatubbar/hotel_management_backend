<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'room_name' => fake()->words(2, true),
            'room_type' => fake()->randomElement(['ac', 'non_ac']),
            'capacity' => fake()->numberBetween(1, 6),
            'rate' => fake()->randomFloat(2, 1000, 10000),
            'available_rooms' => fake()->numberBetween(0, 10),
            'status' => 'available',
            'amenities' => fake()->randomElements(['wifi', 'ac', 'tv', 'breakfast'], 2),
            'description' => fake()->sentence(),
        ];
    }
}
