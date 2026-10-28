<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Nreljed\MarketingModule\Enums\ContactStatus;
use Nreljed\MarketingModule\Models\Contact;

class SyncMarketingCustomers extends Command
{
    protected $signature = 'marketing:sync-customers {--dry-run : Show what would be synced without writing}';

    protected $description = 'Sync shop customers (users with the customer role) into the marketing audience as contacts.';

    public function handle(): int
    {
        $customers = User::whereHas('roles', fn ($q) => $q->where('slug', 'customer'))->get();

        if ($customers->isEmpty()) {
            $this->warn('No customer users found.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $created = 0;
        $updated = 0;

        foreach ($customers as $user) {
            if (! $user->email) {
                continue;
            }

            $payload = [
                'first_name' => $user->first_name ?: $user->name,
                'last_name' => $user->last_name,
                'phone' => $user->phone,
                'status' => $user->email_verified_at
                    ? ContactStatus::Active
                    : ContactStatus::Lead,
                'source' => 'shop',
            ];

            if ($dryRun) {
                $this->line("[dry-run] {$user->email} -> ".json_encode($payload));

                continue;
            }

            $contact = Contact::where('email', $user->email)->first();

            if ($contact) {
                $contact->fill($payload)->save();
                $updated++;
            } else {
                Contact::create(array_merge(['email' => $user->email], $payload));
                $created++;
            }
        }

        $this->info("Synced {$customers->count()} customers: {$created} created, {$updated} updated.");

        return self::SUCCESS;
    }
}
