<?php

/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace Tests\Unit\Jwt;

use function describe;

use InvalidArgumentException;
use Peniti\FilamentAuth0\Jwt\Claims;
use stdClass;

describe(Claims::class, function () {
    describe('constructor', function () {
        it('can be instantiated with an array', function () {
            $claims = new Claims(['name' => 'John', 'age' => 30]);

            expect($claims)->toBeInstanceOf(Claims::class);
        });

        it('can be instantiated with stdClass', function () {
            $data = new stdClass();
            $data->name = 'John';
            $data->age = 30;

            $claims = new Claims($data);

            expect($claims)->toBeInstanceOf(Claims::class);
        });
    });

    describe('string()', function () {
        it('returns the string when claim exists and is string', function () {
            $claims = new Claims(['name' => 'John Doe']);

            $result = $claims->string('name');

            expect($result)->toBe('John Doe');
        });

        it('throws exception when claim exists but is not string', function () {
            $claims = new Claims(['age' => 30]);

            $claims->string('age');
        })->throws(InvalidArgumentException::class, 'The value of the claim [age] must be a string, integer given.');

        it('returns default value when claim does not exist', function () {
            $claims = new Claims([]);

            $result = $claims->string('name', 'Default Name');

            expect($result)->toBe('Default Name');
        });

        it('returns null when claim does not exist and optional is true', function () {
            $claims = new Claims([]);

            $result = $claims->string('name', null, true);

            expect($result)->toBeNull();
        });

        it('evaluates the closure default when claim does not exist', function () {
            $claims = new Claims([]);

            $result = $claims->string('name', fn () => 'Generated Name');

            expect($result)->toBe('Generated Name');
        });

        it('returns null when optional is true and claim is wrong type', function () {
            $claims = new Claims(['age' => 30]);

            $result = $claims->string('age', null, true);

            expect($result)->toBeNull();
        });
    });

    describe('integer()', function () {
        it('returns the integer when claim exists and is integer', function () {
            $claims = new Claims(['age' => 30]);

            $result = $claims->integer('age');

            expect($result)->toBe(30);
        });

        it('throws exception when claim exists but is not integer', function () {
            $claims = new Claims(['age' => 'thirty']);

            $claims->integer('age');
        })->throws(InvalidArgumentException::class, 'The value of the claim [age] must be an integer, string given.');

        it('returns default value when claim does not exist', function () {
            $claims = new Claims([]);

            $result = $claims->integer('age', 18);

            expect($result)->toBe(18);
        });

        it('returns null when claim does not exist and optional is true', function () {
            $claims = new Claims([]);

            $result = $claims->integer('age', null, true);

            expect($result)->toBeNull();
        });

        it('evaluates the closure default when claim does not exist', function () {
            $claims = new Claims([]);

            $result = $claims->integer('age', fn () => 25);

            expect($result)->toBe(25);
        });

        it('returns null when optional is true and claim is wrong type', function () {
            $claims = new Claims(['age' => 'thirty']);

            $result = $claims->integer('age', null, true);

            expect($result)->toBeNull();
        });
    });

    describe('float()', function () {
        it('returns the float when claim exists and is float', function () {
            $claims = new Claims(['price' => 19.99]);

            $result = $claims->float('price');

            expect($result)->toBe(19.99);
        });

        it('throws exception when claim exists but is not float', function () {
            $claims = new Claims(['price' => '19.99']);

            $claims->float('price');
        })->throws(InvalidArgumentException::class, 'The value of the claim [price] must be a float, string given.');

        it('returns default value when claim does not exist', function () {
            $claims = new Claims([]);

            $result = $claims->float('price', 0.0);

            expect($result)->toBe(0.0);
        });

        it('returns null when claim does not exist and optional is true', function () {
            $claims = new Claims([]);

            $result = $claims->float('price', null, true);

            expect($result)->toBeNull();
        });

        it('evaluates the closure default when claim does not exist', function () {
            $claims = new Claims([]);

            $result = $claims->float('price', fn () => 29.99);

            expect($result)->toBe(29.99);
        });

        it('returns null when optional is true and claim is wrong type', function () {
            $claims = new Claims(['price' => '19.99']);

            $result = $claims->float('price', null, true);

            expect($result)->toBeNull();
        });
    });

    describe('boolean()', function () {
        it('returns true when claim exists and is true', function () {
            $claims = new Claims(['active' => true]);

            $result = $claims->boolean('active');

            expect($result)->toBeTrue();
        });

        it('returns false when claim exists and is false', function () {
            $claims = new Claims(['active' => false]);

            $result = $claims->boolean('active');

            expect($result)->toBeFalse();
        });

        it('throws exception when claim exists but is not boolean', function () {
            $claims = new Claims(['active' => 'true']);

            $claims->boolean('active');
        })->throws(InvalidArgumentException::class, 'The value of the claim [active] must be a boolean, string given.');

        it('returns default value when claim does not exist', function () {
            $claims = new Claims([]);

            $result = $claims->boolean('active', false);

            expect($result)->toBeFalse();
        });

        it('returns null when claim does not exist and optional is true', function () {
            $claims = new Claims([]);

            $result = $claims->boolean('active', null, true);

            expect($result)->toBeNull();
        });

        it('evaluates the closure default when claim does not exist', function () {
            $claims = new Claims([]);

            $result = $claims->boolean('active', fn () => true);

            expect($result)->toBeTrue();
        });

        it('returns null when optional is true and claim is wrong type', function () {
            $claims = new Claims(['active' => 'true']);

            $result = $claims->boolean('active', null, true);

            expect($result)->toBeNull();
        });
    });

    describe('get()', function () {
        it('returns value when claim exists', function () {
            $claims = new Claims(['name' => 'John']);

            $result = $claims->get('name');

            expect($result)->toBe('John');
        });

        it('returns default value when claim does not exist', function () {
            $claims = new Claims([]);

            $result = $claims->get('name', 'Default');

            expect($result)->toBe('Default');
        });

        it('supports dot notation for nested arrays', function () {
            $claims = new Claims(['user' => ['profile' => ['name' => 'John']]]);

            $result = $claims->get('user.profile.name');

            expect($result)->toBe('John');
        });

        it('supports dot notation for nested stdClass', function () {
            $user = new stdClass();
            $profile = new stdClass();
            $profile->name = 'John';
            $user->profile = $profile;

            $claims = new Claims(['user' => $user]);

            $result = $claims->get('user.profile.name');

            expect($result)->toBe('John');
        });

        it('returns null for non-existent nested path without default', function () {
            $claims = new Claims(['user' => ['name' => 'John']]);

            $result = $claims->get('user.email');

            expect($result)->toBeNull();
        });

        it('returns default for non-existent nested path', function () {
            $claims = new Claims(['user' => ['name' => 'John']]);

            $result = $claims->get('user.email', 'default@example.com');

            expect($result)->toBe('default@example.com');
        });
    });

    describe('toArray()', function () {
        it('converts the array claims to array', function () {
            $claims = new Claims(['name' => 'John', 'age' => 30]);

            $result = $claims->toArray();

            expect($result)->toBe(['name' => 'John', 'age' => 30]);
        });

        it('converts the stdClass claims to array', function () {
            $data = new stdClass();
            $data->name = 'John';
            $data->age = 30;

            $claims = new Claims($data);

            $result = $claims->toArray();

            expect($result)->toBe(['name' => 'John', 'age' => 30]);
        });

        it('handles nested structures', function () {
            $claims = new Claims([
                'user' => [
                    'name' => 'John',
                    'profile' => [
                        'bio' => 'Developer',
                    ],
                ],
            ]);

            $result = $claims->toArray();

            expect($result)->toBe([
                'user' => [
                    'name' => 'John',
                    'profile' => [
                        'bio' => 'Developer',
                    ],
                ],
            ]);
        });
    });
});
