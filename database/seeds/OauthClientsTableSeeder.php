<?php

use Illuminate\Database\Seeder;

class OauthClientsTableSeeder extends Seeder
{
    public function run()
    {
        

        \DB::table('oauth_clients')->delete();
        
        \DB::table('oauth_clients')->insert(array (
            0 => 
            array (
                'id' => 1,
                'user_id' => NULL,
                'name' => 'Laravel Personal Access Client',
                'secret' => 'dvSfWrQvhYDm3HyLxz9GvTDUuDjPRhjDH0UHjs4z',
                'redirect' => 'http://kopikenangan.ap-southeast-1.elasticbeanstalk.com',
                'personal_access_client' => 1,
                'password_client' => 0,
                'revoked' => 0,
                'created_at' => '2018-05-09 16:18:32',
                'updated_at' => '2018-05-09 16:18:32',
            ),
            1 => 
            array (
                'id' => 2,
                'user_id' => NULL,
                'name' => 'Laravel Password Grant Client',
                'secret' => 'GWlcMCI9xJFGCM0oTJ1mNzfaLE90nMf9SB9d0dea',
                'redirect' => 'http://kopikenangan.ap-southeast-1.elasticbeanstalk.com',
                'personal_access_client' => 0,
                'password_client' => 1,
                'revoked' => 0,
                'created_at' => '2018-05-09 16:18:32',
                'updated_at' => '2018-05-09 16:18:32',
            ),
        ));
        
        
    }
}