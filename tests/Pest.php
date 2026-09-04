<?php

use App\Ai\Agents\SuggestBetterAnchor;
use App\Ai\Agents\SuggestDayOrder;
use App\Ai\Agents\SuggestNewPlaces;
use App\Ai\Agents\SuggestSmallestStep;
use Database\Factories\HabitFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        // Kein Test spricht mit der Claude API. Ohne diese Zeile löste ein
        // vergessener Fake eine echte, bezahlte Anfrage aus — und der Test
        // hinge am Netz statt an der eigenen Logik.
        SuggestSmallestStep::fake()->preventStrayPrompts();
        SuggestBetterAnchor::fake()->preventStrayPrompts();
        SuggestDayOrder::fake()->preventStrayPrompts();
        SuggestNewPlaces::fake()->preventStrayPrompts();

        // Die Factory vergibt Situationen reihum; ohne diesen Reset hinge es
        // von der Zahl der vorherigen Tests ab, welchen Moment eine
        // Gewohnheit bekommt.
        HabitFactory::resetSituations();
    })
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}
