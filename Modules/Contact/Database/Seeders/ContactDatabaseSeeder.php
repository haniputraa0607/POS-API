<?php

namespace Modules\Contact\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Contact\Entities\ContactMessage;
use Modules\Contact\Entities\ContactOfficial;
use Modules\Contact\Entities\ContactSosialMedia;

class ContactDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

       $data_contact = [
        [
            'type' => 'Tiktok',
            'username' => 'daviena.official',
            'link' => 'https://tiktok.com'
        ],
        [
            'type' => 'Tiktok Shop',
            'username' => 'Tiktok Shop',
            'link' => 'https://tiktok.com'
        ],
        [
            'type' => 'Tokopedia',
            'username' => 'davienaskincareindonesia',
            'link' => 'https://tokopedia.com'
        ],
        [
            'type' => 'Shopee',
            'username' => 'ownerdavienaskincare',
            'link' => 'https://shopee.com'
        ],
        ];
        foreach($data_contact as $key){
            ContactSosialMedia::create([
                'type' => $key['type'],
                'username' => $key['username'],
                'link' => $key['link'],
            ]);
        }

        ContactOfficial::create([
            'official_name' => 'WhatsApp',
            'official_value' => json_encode('(+62) 858 3986 0132')
        ]);

        ContactOfficial::create([
            'official_name' => 'Working Hour',
            'official_value' => json_encode([
                'Monday - Friday : 09.00 - 22.00',
                'Saturday & Sunday : 09.00 - 12.00'
            ])
        ]);
    }
}
