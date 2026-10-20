<?php

namespace Tests\Feature;

use App\Livewire\NotificationBell;
use App\Mail\BookingCancelledMail;
use App\Mail\BookingConfirmedMail;
use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Host;
use App\Models\Message;
use App\Models\Property;
use App\Models\User;
use App\Notifications\BookingCancelledNotification;
use App\Notifications\BookingConfirmedNotification;
use App\Notifications\NewMessageNotification;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Tests\TestCase;

class MessagingTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::factory()->create();
    }

    private function conversationBetween(User $a, User $b): Conversation
    {
        return Conversation::findOrCreateDirect($a, $b);
    }

    public function test_messages_require_authentication(): void
    {
        $this->get(route('messages.index'))->assertRedirect(route('login'));
    }

    public function test_start_direct_conversation_creates_shared_conversation(): void
    {
        $guest = $this->makeUser();
        $other = $this->makeUser();

        $this->actingAs($guest)
            ->post(route('messages.start', $other))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $conversation = Conversation::sole();

        $this->assertSame(
            collect([$guest->id, $other->id])->sort()->values()->all(),
            $conversation->users()->pluck('users.id')->sort()->values()->all(),
        );
    }

    public function test_starting_same_conversation_twice_reuses_it(): void
    {
        $guest = $this->makeUser();
        $other = $this->makeUser();

        $this->actingAs($guest)->post(route('messages.start', $other));
        $this->actingAs($guest)->post(route('messages.start', $other));

        $this->assertSame(1, Conversation::count());
    }

    public function test_cannot_message_self(): void
    {
        $guest = $this->makeUser();

        $this->actingAs($guest)
            ->post(route('messages.start', $guest))
            ->assertForbidden();
    }

    public function test_cannot_message_suspended_user(): void
    {
        $guest = $this->makeUser();
        $suspended = $this->makeUser();
        $suspended->suspend('Test suspendat');

        $this->actingAs($guest)
            ->post(route('messages.start', $suspended))
            ->assertForbidden();
    }

    public function test_non_participant_cannot_view_conversation(): void
    {
        $a = $this->makeUser();
        $b = $this->makeUser();
        $intruder = $this->makeUser();
        $conversation = $this->conversationBetween($a, $b);

        $this->actingAs($intruder)
            ->get(route('messages.show', $conversation))
            ->assertForbidden();
    }

    public function test_participant_can_send_message_and_recipient_gets_notification(): void
    {
        $guest = $this->makeUser();
        $other = $this->makeUser();
        $conversation = $this->conversationBetween($guest, $other);

        $this->actingAs($guest);

        Volt::test('messages.show', ['conversation' => $conversation])
            ->set('body', 'Bună ziua, este disponibilă proprietatea?')
            ->call('send')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $guest->id,
            'body' => 'Bună ziua, este disponibilă proprietatea?',
        ]);

        $this->assertSame(
            1,
            $other->refresh()->notifications()
                ->where('type', NewMessageNotification::class)
                ->count(),
        );

        $this->assertSame(0, $guest->refresh()->notifications()->count());
    }

    public function test_empty_message_is_rejected(): void
    {
        $guest = $this->makeUser();
        $other = $this->makeUser();
        $conversation = $this->conversationBetween($guest, $other);

        $this->actingAs($guest);

        Volt::test('messages.show', ['conversation' => $conversation])
            ->set('body', '')
            ->call('send')
            ->assertHasErrors(['body']);

        $this->assertSame(0, Message::count());
    }

    public function test_unread_count_and_mark_read_flow(): void
    {
        $guest = $this->makeUser();
        $host = $this->makeUser();
        $conversation = $this->conversationBetween($guest, $host);

        $conversation->sendMessage($host, 'Confirmare rezervare.');

        $this->assertSame(1, $guest->refresh()->unreadMessagesCount());
        $this->assertSame(0, $host->refresh()->unreadMessagesCount());

        $this->actingAs($guest)
            ->get(route('messages.show', $conversation))
            ->assertOk();

        $this->assertSame(0, $guest->refresh()->unreadMessagesCount());
    }

    public function test_messages_index_lists_conversations(): void
    {
        $guest = $this->makeUser();
        $host = $this->makeUser();
        $conversation = $this->conversationBetween($guest, $host);
        $conversation->sendMessage($host, 'Salut, ai un mesaj.');

        $this->actingAs($guest);

        Volt::test('messages.index')
            ->assertSee($host->name)
            ->assertSee('Salut, ai un mesaj.')
            ->assertSee('conversation-unread', false);
    }

    public function test_booking_confirmation_notifies_guest_and_host_in_app(): void
    {
        Mail::fake();

        $host = Host::factory()->create();
        $property = Property::factory()->for($host)->create(['status' => 'published']);
        $guest = $this->makeUser();

        $booking = Booking::factory()->create([
            'property_id' => $property->id,
            'guest_id' => $guest->id,
            'status' => 'pending',
            'payment_status' => 'pending',
            'source' => 'direct',
        ]);

        app(BookingService::class)->confirm($booking);

        Mail::assertSent(BookingConfirmedMail::class);

        $this->assertSame(
            1,
            $guest->refresh()->notifications()
                ->where('type', BookingConfirmedNotification::class)
                ->count(),
        );
        $this->assertSame(
            1,
            $host->user->refresh()->notifications()
                ->where('type', BookingConfirmedNotification::class)
                ->count(),
        );
    }

    public function test_booking_cancellation_notifies_guest_and_host_in_app(): void
    {
        Mail::fake();

        $host = Host::factory()->create();
        $property = Property::factory()->for($host)->create(['status' => 'published']);
        $guest = $this->makeUser();

        $booking = Booking::factory()->create([
            'property_id' => $property->id,
            'guest_id' => $guest->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'source' => 'direct',
        ]);

        app(BookingService::class)->cancel($booking, 'Solicitarea oaspetelui');

        Mail::assertSent(BookingCancelledMail::class);

        $this->assertSame(
            1,
            $guest->refresh()->notifications()
                ->where('type', BookingCancelledNotification::class)
                ->count(),
        );
        $this->assertSame(
            1,
            $host->user->refresh()->notifications()
                ->where('type', BookingCancelledNotification::class)
                ->count(),
        );
    }

    public function test_start_conversation_from_booking_attaches_booking_context(): void
    {
        $host = Host::factory()->create();
        $property = Property::factory()->for($host)->create(['status' => 'published']);
        $guest = $this->makeUser();

        $booking = Booking::factory()->create([
            'property_id' => $property->id,
            'guest_id' => $guest->id,
            'status' => 'pending',
            'payment_status' => 'pending',
            'source' => 'direct',
        ]);

        $this->actingAs($guest)
            ->post(route('messages.start', $host->user).'?booking='.$booking->reference)
            ->assertRedirect();

        $conversation = Conversation::sole();

        $this->assertSame($booking->id, $conversation->booking_id);
        $this->assertSame('Rezervare '.$booking->reference, $conversation->subject);
    }

    public function test_dashboard_shows_messages_nav_with_unread_badge(): void
    {
        $guest = $this->makeUser();
        $other = $this->makeUser();
        $this->conversationBetween($guest, $other)
            ->sendMessage($other, 'Mesaj necitit.');

        $this->actingAs($guest)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('nav-messages', false)
            ->assertSee('nav-messages-badge', false);
    }

    public function test_booking_page_shows_contact_link(): void
    {
        $host = Host::factory()->create();
        $property = Property::factory()->for($host)->create(['status' => 'published']);
        $guest = $this->makeUser();

        $booking = Booking::factory()->create([
            'property_id' => $property->id,
            'guest_id' => $guest->id,
            'status' => 'pending',
            'payment_status' => 'pending',
            'source' => 'direct',
        ]);

        $this->actingAs($guest)
            ->get(route('bookings.show', $booking))
            ->assertOk()
            ->assertSee('contact-link', false)
            ->assertSee('/messages/with/'.$host->user->id, false);
    }

    public function test_notification_bell_marks_notification_read_and_redirects(): void
    {
        $guest = $this->makeUser();
        $other = $this->makeUser();
        $conversation = $this->conversationBetween($guest, $other);
        $conversation->sendMessage($other, 'Salut!');

        $notification = $guest->refresh()->notifications()->first();
        $this->assertNotNull($notification);

        $this->actingAs($guest);

        Livewire::test(NotificationBell::class)
            ->call('markRead', $notification->id)
            ->assertRedirect(route('messages.show', $conversation));

        $this->assertNotNull($notification->refresh()->read_at);
    }

    public function test_notification_bell_marks_all_read(): void
    {
        $guest = $this->makeUser();
        $other = $this->makeUser();
        $conversation = $this->conversationBetween($guest, $other);
        $conversation->sendMessage($other, 'Salut!');
        $conversation->sendMessage($other, 'Încă unul!');

        $this->actingAs($guest);

        Livewire::test(NotificationBell::class)
            ->call('markAllRead');

        $this->assertSame(
            0,
            $guest->refresh()->unreadNotifications()->count(),
        );
    }

    public function test_notification_open_rejects_external_urls(): void
    {
        $guest = $this->makeUser();
        $other = $this->makeUser();
        $conversation = $this->conversationBetween($guest, $other);
        $conversation->sendMessage($other, 'Salut!');

        $notification = $guest->refresh()->notifications()->first();
        $this->assertNotNull($notification);

        $notification->forceFill([
            'data' => ['title' => 'Test', 'url' => 'https://evil.example.com'],
        ])->saveQuietly();

        $this->actingAs($guest);

        Livewire::test(NotificationBell::class)
            ->call('markRead', $notification->id)
            ->assertRedirect('/dashboard');
    }
}
