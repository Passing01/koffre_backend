<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Créer un utilisateur admin par défaut
        User::updateOrCreate(
            ['phone' => '+22606407309'], // Numéro de téléphone admin
            [
                'fullname' => 'Administrateur',
                'phone' => '+22606407309',
                'is_verified' => true,
                'is_admin' => true,
                'country_code' => 'BF',
            ]
        );

        $this->command->info('✅ Utilisateur admin créé avec succès!');
        $this->command->info('📱 Téléphone: +22606407309');
        $this->command->info('👤 Nom: Administrateur');
    }
}
