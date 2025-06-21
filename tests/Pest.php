<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

// For Feature tests, use the package's TestCase that extends Orchestra Testbench
uses(Vsent\LaravelToastify\Tests\TestCase::class)->in('Feature');

// For Unit tests, you might use a simpler base TestCase or also the package's TestCase
// if they need some Laravel features or service container bindings.
// If unit tests are pure PHP and don't need Laravel context, you might use:
// uses(PHPUnit\Framework\TestCase::class)->in('Unit');
// However, given our DTOs and Manager might use Laravel helpers (like Str, Collection),
// using the package's TestCase for Unit tests as well can be beneficial.
uses(Vsent\LaravelToastify\Tests\TestCase::class)->in('Unit');


/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of powerful expectations and assertions.
|
| https://pestphp.com/docs/expectations
|
*/

// expect()->extend('toBeOne', function () {
//     return $this->toBe(1);
// });

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the amount of code in your test files.
|
*/

// function something()
// {
//     // ..
// }
