<?php

namespace Database\Seeders;

use App\Models\ConnectedAccount;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // TODO use the factory
        User::create([
            'name' => 'Kenny Uzoma',
            'email' => 'kennyuzoma@gmail.com',
            'password' => Hash::make('password')
        ]);

        ConnectedAccount::create([
            'service' => 'twitter',
            'user_id' => 1,
            'metadata' => [
	            "access_token" => "2583364141-gaNkWD9cQjlZCLx1tj8O2R8ih7JlPg93J0XWxob",
	            "bearer_token" => "AAAAAAAAAAAAAAAAAAAAAORfQAEAAAAAIgOagCPXm%2F8%2BYMdctpQUxNVf8Us%3Dgolp48hRrlYFDYmnsUcOfjGpwQ1rOD1w7W825Xe4xwkBtGWztY",
	            "consumer_key" => "dlsLbz21L3xlJCWqSP8dakF4f",
	            "access_secret" => "YbWPbgzwq8R6aDOm0zSJtluMzPb2ExHfiqEwthCsle9du",
	            "consumer_secret" => "cOmKzOt4sqyMGeXGrBl0qSLLHViIq7jXnPvSzEQWd0ooxFaNSt"
            ]
        ]);
        // \App\Models\User::factory(10)->create();
    }
}
