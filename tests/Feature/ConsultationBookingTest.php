<?php

use App\Mail\ConsultationBooked;
use App\Mail\ConsultationConfirmed;
use App\Models\Consultation;
use App\Models\Setting;
use App\Models\User;
use App\Support\ConsultationSlots;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
    RateLimiter::clear('consultation:127.0.0.1');

    // Monday 2 November 2026, 8:00am in Lagos (7:00 UTC).
    $this->travelTo(CarbonImmutable::create(2026, 11, 2, 7, 0, 0, 'UTC'));
});

test('free times follow the working days, hours and minimum notice', function () {
    $days = ConsultationSlots::available();

    // 3 hours' notice from 8:00am means the first time today is 11:00; the last 15-minute call starts at 16:30.
    expect($days['2026-11-02'][0])->toBe('11:00')
        ->and(end($days['2026-11-02']))->toBe('16:30')
        ->and($days)->toHaveKey('2026-11-03')
        // Saturday and Sunday are not working days.
        ->and($days)->not->toHaveKey('2026-11-07')
        ->and($days)->not->toHaveKey('2026-11-08');
});

test('a visitor can book a call and both sides are emailed', function () {
    Setting::put(['email' => 'owner@example.com']);

    Livewire::test('consultation-booking')
        ->call('pickDate', '2026-11-03')
        ->set('time', '10:30')
        ->set('name', 'Ngozi Eze')
        ->set('phone', '08031234567')
        ->set('email', 'ngozi@example.com')
        ->set('topic', 'Restaurant website with orders')
        ->call('book')
        ->assertHasNoErrors()
        ->assertSee('Your call is booked!');

    $call = Consultation::query()->sole();

    // 10:30am in Lagos is 9:30 UTC.
    expect($call->starts_at->utc()->format('Y-m-d H:i'))->toBe('2026-11-03 09:30')
        ->and($call->when())->toBe('Tuesday 3 November, 10:30am (WAT)');

    Mail::assertSent(ConsultationBooked::class, fn ($mail) => $mail->hasTo('owner@example.com'));
    Mail::assertSent(ConsultationConfirmed::class, fn ($mail) => $mail->hasTo('ngozi@example.com'));

    expect(ConsultationSlots::available()['2026-11-03'])->not->toContain('10:30');
});

test('a time that has just been taken cannot be booked twice', function () {
    Consultation::factory()->create(['starts_at' => ConsultationSlots::toUtc('2026-11-03', '10:30')]);

    Livewire::test('consultation-booking')
        ->set('date', '2026-11-03')
        ->set('time', '10:30')
        ->set('name', 'Chidi')
        ->set('phone', '08031234567')
        ->call('book')
        ->assertHasErrors('time');

    expect(Consultation::query()->count())->toBe(1);
});

test('times outside the working hours or in the past cannot be booked', function (string $date, string $time) {
    Livewire::test('consultation-booking')
        ->set('date', $date)
        ->set('time', $time)
        ->set('name', 'Chidi')
        ->set('phone', '08031234567')
        ->call('book')
        ->assertHasErrors('time');

    expect(Consultation::query()->count())->toBe(0);
})->with([
    'before opening' => ['2026-11-03', '07:00'],
    'on a Saturday' => ['2026-11-07', '10:00'],
    'too soon today' => ['2026-11-02', '09:00'],
]);

test('a name and phone number are needed', function () {
    Livewire::test('consultation-booking')
        ->set('date', '2026-11-03')
        ->set('time', '10:30')
        ->call('book')
        ->assertHasErrors(['name' => 'required', 'phone' => 'required']);
});

test('the booking page explains when online booking is closed', function () {
    Setting::put(['consult_enabled' => '0']);

    $this->get(route('consultation'))->assertOk()->assertSee('Online booking is closed right now.');
});

test('the admin sees booked calls and can mark them done or cancel them', function () {
    $call = Consultation::factory()->create(['name' => 'Ngozi Eze', 'starts_at' => ConsultationSlots::toUtc('2026-11-03', '10:30')]);
    $this->actingAs(User::factory()->admin()->create());

    $this->get(route('admin.consultations'))->assertOk()->assertSee('Ngozi Eze')->assertSee('Tuesday 3 November, 10:30am (WAT)');

    Livewire::test('pages::admin.consultations')->call('setStatus', $call->id, 'cancelled');

    expect($call->refresh()->status)->toBe('cancelled')
        ->and(ConsultationSlots::available()['2026-11-03'])->toContain('10:30');
});

test('the admin can change the booking hours', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.consultations')
        ->set('days', ['6'])
        ->set('hours.consult_start', '10:00')
        ->set('hours.consult_end', '12:00')
        ->call('saveHours')
        ->assertHasNoErrors();

    expect(array_keys(ConsultationSlots::available()))->toBe(['2026-11-07', '2026-11-14'])
        ->and(ConsultationSlots::available()['2026-11-07'])->toBe(['10:00', '10:30', '11:00', '11:30']);
});

test('the booking hours must make sense', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.consultations')
        ->set('hours.consult_start', '17:00')
        ->set('hours.consult_end', '09:00')
        ->call('saveHours')
        ->assertHasErrors('hours.consult_end');
});
