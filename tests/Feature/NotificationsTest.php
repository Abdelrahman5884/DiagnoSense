```php
<?php

use Illuminate\Notifications\DatabaseNotification;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;

beforeEach(function () {
    $this->user = createUserWithType('doctor', 'menna@gmail.com');
    $this->doctor = $this->user->doctor;
    actingAs($this->user);
});

function createNotification($doctor, bool $read = false): DatabaseNotification
{
    return DatabaseNotification::create([
        'id' => \Str::uuid(),
        'type' => 'App\\Notifications\\TestNotification',
        'notifiable_type' => 'App\\Models\\Doctor',
        'notifiable_id' => $doctor->id,
        'data' => ['message' => 'Test notification'],
        'read_at' => $read ? now() : null,
    ]);
}

it('returns notifications list', function () {
    createNotification($this->doctor);

    getJson(route('notifications.index'))
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data',
        ]);
});

it('returns unread notifications count', function () {
    createNotification($this->doctor);
    createNotification($this->doctor);
    createNotification($this->doctor, read: true);

    getJson(route('notifications.unreadCount'))
        ->assertOk()
        ->assertJsonFragment([
            'unread_count' => 2,
        ]);
});

it('marks notification as read', function () {
    $notification = createNotification($this->doctor);

    patchJson(route('notifications.read', $notification->id))
        ->assertOk();

    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('marks all notifications as read', function () {
    createNotification($this->doctor);
    createNotification($this->doctor);

    patchJson(route('notifications.readAll'))
        ->assertOk();

    expect($this->doctor->unreadNotifications()->count())
        ->toBe(0);
});

it('deletes all notifications', function () {
    createNotification($this->doctor);
    createNotification($this->doctor);

    deleteJson(route('notifications.clearAll'))
        ->assertOk();

    expect($this->doctor->notifications()->count())
        ->toBe(0);
});