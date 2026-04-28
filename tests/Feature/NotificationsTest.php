<?php

use Illuminate\Notifications\DatabaseNotification;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\deleteJson;

beforeEach(function () {
    $this->user   = createUserWithType('doctor', 'menna@gmail.com');
    $this->doctor = $this->user->doctor;
    actingAs($this->user);
});

function createNotification($doctor, bool $read = false): DatabaseNotification
{
    return DatabaseNotification::create([
        'id'              => \Str::uuid(),
        'type'            => 'App\Notifications\TestNotification',
        'notifiable_type' => 'App\Models\Doctor',
        'notifiable_id'   => $doctor->id,
        'data'            => ['message' => 'Test notification'],
        'read_at'         => $read ? now() : null,
    ]);
}

describe('Notifications Index', function () {

    it('returns 200 and correct structure', function () {
        createNotification($this->doctor);

        getJson(route('notifications.index'))
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ]);
    });

    it('returns empty list when no notifications', function () {
        getJson(route('notifications.index'))
            ->assertOk()
            ->assertJsonFragment(['data' => []]);
    });

    it('returns 401 for guest', function () {
        auth()->logout();
        getJson(route('notifications.index'))->assertStatus(401);
    });

});

describe('Notifications Unread Count', function () {

    it('returns correct unread count', function () {
        createNotification($this->doctor);
        createNotification($this->doctor);
        createNotification($this->doctor, read: true);

        getJson(route('notifications.unreadCount'))
            ->assertOk()
            ->assertJsonFragment(['unread_count' => 2]);
    });

    it('returns zero when all notifications are read', function () {
        createNotification($this->doctor, read: true);
        createNotification($this->doctor, read: true);

        getJson(route('notifications.unreadCount'))
            ->assertOk()
            ->assertJsonFragment(['unread_count' => 0]);
    });

    it('returns zero when no notifications exist', function () {
        getJson(route('notifications.unreadCount'))
            ->assertOk()
            ->assertJsonFragment(['unread_count' => 0]);
    });

    it('returns 401 for guest', function () {
        auth()->logout();
        getJson(route('notifications.unreadCount'))->assertStatus(401);
    });

});

describe('Notifications Read Single', function () {

    it('marks a single notification as read', function () {
        $notification = createNotification($this->doctor);

        patchJson(route('notifications.read', $notification->id))
            ->assertOk()
            ->assertJsonFragment(['message' => 'Notification marked as read']);

        expect($notification->fresh()->read_at)->not->toBeNull();
    });

    it('returns 401 for guest', function () {
        auth()->logout();
        $notification = createNotification($this->doctor);

        patchJson(route('notifications.read', $notification->id))
            ->assertStatus(401);
    });

});

describe('Notifications Read All', function () {

    it('marks all notifications as read', function () {
        createNotification($this->doctor);
        createNotification($this->doctor);
        createNotification($this->doctor);

        patchJson(route('notifications.readAll'))
            ->assertOk()
            ->assertJsonFragment(['message' => 'All notifications marked as read']);

        expect($this->doctor->unreadNotifications()->count())->toBe(0);
    });

    it('returns ok even when no unread notifications', function () {
        createNotification($this->doctor, read: true);

        patchJson(route('notifications.readAll'))
            ->assertOk();
    });

    it('does not mark other doctors notifications as read', function () {
        $otherDoctor = createUserWithType('doctor', 'hager@gmail.com')->doctor;
        createNotification($otherDoctor);

        patchJson(route('notifications.readAll'))->assertOk();

        expect($otherDoctor->unreadNotifications()->count())->toBe(1);
    });

    it('returns 401 for guest', function () {
        auth()->logout();
        patchJson(route('notifications.readAll'))->assertStatus(401);
    });

});

describe('Notifications Clear All', function () {

    it('deletes all notifications successfully', function () {
        createNotification($this->doctor);
        createNotification($this->doctor);
        createNotification($this->doctor, read: true);

        deleteJson(route('notifications.clearAll'))
            ->assertOk()
            ->assertJsonFragment(['message' => 'All notifications deleted successfully']);

        expect($this->doctor->notifications()->count())->toBe(0);
    });

    it('returns ok even when no notifications exist', function () {
        deleteJson(route('notifications.clearAll'))
            ->assertOk();
    });

    it('does not delete other doctors notifications', function () {
        $otherDoctor = createUserWithType('doctor', 'abdelrahman@gmail.com')->doctor;
        createNotification($otherDoctor);

        deleteJson(route('notifications.clearAll'))->assertOk();

        expect($otherDoctor->notifications()->count())->toBe(1);
    });

    it('returns 401 for guest', function () {
        auth()->logout();
        deleteJson(route('notifications.clearAll'))->assertStatus(401);
    });

});