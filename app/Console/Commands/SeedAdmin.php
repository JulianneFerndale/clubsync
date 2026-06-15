<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class SeedAdmin extends Command
{
    protected $signature   = 'app:seed-admin {--firebase-uid= : Skip Firebase registration and use this UID directly}';
    protected $description = 'Create the DSA admin account in Firebase and MySQL';

    public function handle(): int
    {
        $email    = 'dsa.admin@sccpag.edu.ph';
        $password = 'Admin@ClubSync1';
        $apiKey   = config('firebase.api_key');

        $this->info('Seeding admin user: ' . $email);

        // ── 1. Firebase: register, sign in, or use provided UID ─────────────
        $firebaseUid = $this->option('firebase-uid') ?: null;

        if ($firebaseUid) {
            $this->info('Using provided Firebase UID: ' . $firebaseUid);
        } else {
            $signUpUrl = config('firebase.sign_up_url') . '?key=' . $apiKey;
            $response  = Http::post($signUpUrl, [
                'email'             => $email,
                'password'          => $password,
                'returnSecureToken' => true,
            ]);

            if ($response->successful()) {
                $firebaseUid = $response->json('localId');
                $this->info('Firebase account created. UID: ' . $firebaseUid);
            } else {
                $error = $response->json('error.message', 'UNKNOWN');

                if ($error === 'EMAIL_EXISTS') {
                    // Account exists — try signing in with our password
                    $signInUrl    = config('firebase.sign_in_url') . '?key=' . $apiKey;
                    $signInResult = Http::post($signInUrl, [
                        'email'             => $email,
                        'password'          => $password,
                        'returnSecureToken' => true,
                    ]);

                    if ($signInResult->successful()) {
                        $firebaseUid = $signInResult->json('localId');
                        $this->info('Firebase account already exists. UID: ' . $firebaseUid);
                    } else {
                        $this->error('Firebase sign-in failed: ' . $signInResult->json('error.message'));
                        $this->newLine();
                        $this->warn('The Firebase account exists but uses a different password.');
                        $this->warn('To fix this:');
                        $this->warn('  1. Go to Firebase Console > Authentication > Users');
                        $this->warn('  2. Find ' . $email . ' and copy the UID');
                        $this->warn('  3. Run: php artisan app:seed-admin --firebase-uid=PASTE_UID_HERE');
                        $this->newLine();
                        $this->warn('Proceeding with MySQL record only for now.');
                    }
                } else {
                    $this->error('Firebase registration failed: ' . $error);
                    $this->warn('Proceeding with MySQL record only (login will require Firebase setup).');
                }
            }
        }

        // ── 2. MySQL: create or update user ─────────────────────────────────
        $user = User::firstOrNew(['email' => $email]);

        $user->first_name   = 'DSA';
        $user->last_name    = 'Admin';
        $user->email        = $email;
        $user->edp_number   = '0000000000';
        $user->password     = Hash::make($password);
        $user->role         = 'dsa';
        $user->is_admin     = true;
        $user->is_suspended = false;

        if ($firebaseUid) {
            $user->firebase_uid = $firebaseUid;
        }

        $user->save();

        $this->newLine();
        $this->info('✓ Admin user ready.');
        $this->table(
            ['Field', 'Value'],
            [
                ['Email',    $email],
                ['Password', $password],
                ['Role',     'dsa'],
                ['MySQL ID', $user->id],
                ['Firebase UID', $firebaseUid ?? '(not set)'],
            ]
        );
        $this->newLine();
        $this->warn('Save the password above — it will not be shown again.');

        return self::SUCCESS;
    }
}
