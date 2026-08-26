<?php

namespace Database\Factories;

use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Person> */
class PersonFactory extends Factory
{
    protected $model = Person::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'birth_date' => fake()->optional()->date(),
            'death_date' => fake()->optional()->date(),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
