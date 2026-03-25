<?php

namespace App\Console\Commands;

use App\Mail\Borrow\OrderDelivered;
use App\Mail\Borrow\OrderReceived;
use App\Mail\Borrow\OrderShipped;
use App\Mail\Borrow\OverdueReturnReminder;
use App\Mail\Borrow\ReturnReminder;
use App\Mail\subscription\DeactivatedSubscription;
use App\Mail\subscription\ExpiredSubscription;
use App\Mail\subscription\PendingSubscription;
use App\Mail\subscription\StartSubscription;
use App\Models\BorrowOrder;
use App\Models\Book;
use App\Models\Plan;
use App\Models\Subscription;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class MailSmokeTest extends Command
{
    protected $signature = 'mail:smoke-test
                            {--user= : Existing user email to use (default: dev-admin@example.com)}
                            {--to= : Override recipient email (default: same as --user)}
                            {--skip-borrow : Skip borrow-order mailables}
                            {--skip-auth : Skip verification + reset password emails}
                            {--skip-subscriptions : Skip subscription mailables}';

    protected $description = 'Send a set of emails (borrow + auth) to verify SMTP configuration.';

    public function handle()
    {
        $userEmail = (string) ($this->option('user') ?: 'dev-admin@example.com');
        $to = (string) ($this->option('to') ?: $userEmail);

        /** @var User|null $user */
        $user = User::where('email', $userEmail)->first();
        if (!$user) {
            $this->error("User not found: {$userEmail}");
            return 2;
        }

        $this->info("Using user={$user->email} (id={$user->id}) to={$to}");
        $this->line('Mail mailer: '.config('mail.default'));
        $this->line('Mail host: '.config('mail.mailers.smtp.host'));
        $this->line('Mail from: '.config('mail.from.address'));

        $failures = 0;

        if (!$this->option('skip-auth')) {
            if ($to !== $user->email) {
                $this->warn("Auth emails (verify/reset) will be sent to the user's email: {$user->email} (ignoring --to={$to})");
            }

            // 1) Verification email
            $this->line('');
            $this->info('1) Email verification notification');
            try {
                $user->email_verified_at = null;
                $user->save();
                $user->sendEmailVerificationNotification();
                $this->info('   OK');
            } catch (\Throwable $e) {
                $failures++;
                $this->error('   FAILED: '.$e->getMessage());
            }

            // 2) Password reset email
            $this->line('');
            $this->info('2) Password reset email');
            try {
                $status = Password::broker()->sendResetLink(['email' => $user->email]);
                $this->line('   Status: '.$status);
                if ($status === Password::RESET_LINK_SENT) {
                    $this->info('   OK');
                } else {
                    $failures++;
                    $this->error('   FAILED: unexpected status');
                }
            } catch (\Throwable $e) {
                $failures++;
                $this->error('   FAILED: '.$e->getMessage());
            }
        }

        if (!$this->option('skip-borrow')) {
            $this->line('');
            $this->info('3) Borrow order mailables (received/shipped/delivered + reminders)');

            try {
                $order = new BorrowOrder();
                $order->id = random_int(1000, 9999);
                $order->start_date = now()->toDateString();
                $order->end_date = now()->addMonth()->toDateString();
                $order->shipment_number = 'TEST'.Str::upper(Str::random(6));
                $order->shipment_status = 'confirmed';
                $order->setRelation('user', $user);

                // Attach a couple of books (if any exist), otherwise dummy models.
                $books = Book::query()->select('id', 'title')->limit(2)->get();
                if ($books->isEmpty()) {
                    $b1 = new Book(['id' => 1, 'title' => 'Test Book 1']);
                    $b2 = new Book(['id' => 2, 'title' => 'Test Book 2']);
                    $books = collect([$b1, $b2]);
                }
                $order->setRelation('books', $books);

                Mail::to($to)->send(new OrderReceived($order));
                $this->info('   Received OK');

                Mail::to($to)->send(new OrderShipped($order));
                $this->info('   Shipped OK');

                Mail::to($to)->send(new OrderDelivered($order));
                $this->info('   Delivered OK');

                // Before-due reminder (simulates "ends tomorrow")
                $beforeDue = clone $order;
                $beforeDue->end_date = now()->addDay()->toDateString();
                $beforeDue->status = 'Delivered';
                $beforeDue->return_shipment_number = null;
                Mail::to($to)->send(new ReturnReminder($beforeDue));
                $this->info('   Return reminder (before due) OK');

                // After-due reminder (overdue) — only when WaitingReturnShipment and still no return shipment number
                $overdue = clone $order;
                $overdue->end_date = now()->subDays(5)->toDateString();
                $overdue->status = 'WaitingReturnShipment';
                $overdue->return_shipment_number = null;
                Mail::to($to)->send(new OverdueReturnReminder($overdue));
                $this->info('   Overdue reminder (every 3 days) OK');
            } catch (\Throwable $e) {
                $failures++;
                $this->error('   FAILED: '.$e->getMessage());
            }
        }

        if (!$this->option('skip-subscriptions')) {
            $this->line('');
            $this->info('4) Subscription mailables (pending/activated/deactivated/expired)');

            try {
                $plan = Plan::query()->select('id', 'name', 'price', 'books_quota')->first();
                if (!$plan) {
                    $plan = new Plan([
                        'id' => 1,
                        'name' => 'Test Plan',
                        'price' => 0,
                        'books_quota' => 10,
                    ]);
                }

                $subscription = new Subscription();
                $subscription->id = random_int(1000, 9999);
                $subscription->status = 'pending';
                $subscription->transaction_amount = 123.45;
                $subscription->transaction_date = now();
                $subscription->start = now();
                $subscription->end = now()->addYear();
                $subscription->created_at = now();
                $subscription->updated_at = now();
                $subscription->setRelation('user', $user);
                $subscription->setRelation('plan', $plan);

                Mail::to($to)->send(new PendingSubscription($subscription));
                $this->info('   Pending OK');

                $subscription->status = 'active';
                Mail::to($to)->send(new StartSubscription($subscription));
                $this->info('   Activated OK');

                $subscription->status = 'deactivated';
                Mail::to($to)->send(new DeactivatedSubscription($subscription));
                $this->info('   Deactivated OK');

                $subscription->status = 'expired';
                Mail::to($to)->send(new ExpiredSubscription($subscription));
                $this->info('   Expired OK');
            } catch (\Throwable $e) {
                $failures++;
                $this->error('   FAILED: '.$e->getMessage());
            }
        }

        $this->line('');
        if ($failures) {
            $this->error("Smoke test finished with failures={$failures}");
            return 1;
        }

        $this->info('Smoke test finished: all OK');
        return 0;
    }
}

