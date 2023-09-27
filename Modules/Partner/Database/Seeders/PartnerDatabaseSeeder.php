<?php

namespace Modules\Partner\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Partner\Entities\OfficialPartner;
use Modules\Partner\Entities\OfficialPartnerDetail;
use KodePandai\Indonesia\Models\City;
use Modules\Partner\Entities\PartnerEqual;
use Faker\Factory as Faker;
use Modules\Partner\Entities\OfficialPartnerHome;

class PartnerDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $faker = Faker::create();

        OfficialPartner::create([
            'description' => '<ul>
                <li>
                    Pembelian awal cukup dengan 5 paket Daviena skincare boleh mix series apa saja lebih kurang senilai 1,2jt –
                    1,3jt kamu sudah mendapat harga reseller dan menjadi reseller resmi, Repeat Order selanjutnya sesuai rules
                    reseller.
                </li>
                <li>
                    Kesempatan terbatas karena ada slot perkota, sehingga kesempatanmu mengembangkan bisnis akan lebih terbuka
                </li>
                <li>Customer yang order di kami akan di arahkan ke reseller terdekat dengan customer</li>
                <li>Mendapat bimbingan dan konsultasi seputar daviena dan pengembangan bisnis dengan santai dan menyenangkan
                </li>
                <li>Tersedia reward menarik setiap akhir tahun</li>
                <li>Mendapat sertifikat resmi anggota</li>
            </ul>'
        ]);
        $detail = [
            [
                'title' => 'AGEN',
                'description' => '1. Agen minimal order 5 Paket Lengkap, Potongan Rp.40.000,-<br> 2.Agen Star+ minimal order 25 Paket Lengkap, Potongan Rp.50.000,-<br>3.Repeat Order untuk Reseller Star & Reseller Star+ hanya 5 paket lengkap',
                'link' => 'https://daviena.belum.live'
            ],
            [
                'title' => 'DISTRIBUTOR',
                'description' => '1. Distributor minimal order 5 Paket lengkap, Potongan Rp.40.000,-<BR> 2. Repeat Order Minimal 1 Paket Lengkap potongan Rp.20.000,-',
                'link' => 'https://daviena.belum.live'
            ],
            [
                'title' => 'STORE',
                'description' => '1. Store minimal order 100 Paket lengkap, Potongan Rp.50.000,-<BR> 2. Repeat Order Minimal 10 Paket Lengkap potongan Rp.30.000,-',
                'link' => 'https://daviena.belum.live'
            ],
        ];
        foreach($detail as $key){
            OfficialPartnerDetail::create([
                'title' => $key['title'],
                'description' => $key['description'],
                'link' => $key['link'],
            ]);
        }

        $partner = [
            [
                'name' => 'Desy Putri Sukmawati',
                'email' => 'desy@gmail.com',
                'phone' => $faker->phoneNumber(),
                'images' => json_encode($faker->imageUrl()),
                'city_code' => City::InRandomOrder()->first()->code,
                'store_name' => $faker->name(),
                'store_address' => $faker->address(),
                'store_city' => $faker->city,
                'username_instagram' => 'desy_putry',
                'url_instagram' => 'https://instagram.com',
                'username_tiktok' => 'desy_putry',
                'url_tiktok' => 'https://tiktok.com',
                'username_tokopedia' => 'desy_putry',
                'url_tokopedia' => 'https://tokopedia.com',
                'username_shopee' => 'desy_putry',
                'url_shopee' => 'https://shopee.com',
                'username_bukalapak' => 'desy_putry',
                'url_bukalapak' => 'https://bukalapak.com',
            ],
            [
                'name' => 'Siti Yuyun',
                'email' => 'siti@gmail.com',
                'phone' => $faker->phoneNumber(),
                'images' => json_encode($faker->imageUrl()),
                'city_code' => City::InRandomOrder()->first()->code,
                'store_name' => $faker->name(),
                'store_address' => $faker->address(),
                'store_city' => $faker->city,
                'username_instagram' => 'siti_yuyun',
                'url_instagram' => 'https://instagram.com',
                'username_tiktok' => 'siti_yuyun',
                'url_tiktok' => 'https://tiktok.com',
                'username_tokopedia' => 'siti_yuyun',
                'url_tokopedia' => 'https://tokopedia.com',
                'username_shopee' => 'siti_yuyun',
                'url_shopee' => 'https://shopee.com',
                'username_bukalapak' => 'siti_yuyun',
                'url_bukalapak' => 'https://bukalapak.com',
            ],
            [
                'name' => 'Dwi Suwarni',
                'email' => 'dwisuwarni@gmail.com',
                'phone' => $faker->phoneNumber(),
                'images' => json_encode($faker->imageUrl()),
                'city_code' => City::InRandomOrder()->first()->code,
                'store_name' => $faker->name(),
                'store_address' => $faker->address(),
                'store_city' => $faker->city,
                'username_instagram' => 'dwi_suwarni',
                'url_instagram' => 'https://instagram.com',
                'username_tiktok' => 'dwi_suwarni',
                'url_tiktok' => 'https://tiktok.com',
                'username_tokopedia' => 'dwi_suwarni',
                'url_tokopedia' => 'https://tokopedia.com',
                'username_shopee' => 'dwi_suwarni',
                'url_shopee' => 'https://shopee.com',
                'username_bukalapak' => 'dwi_suwarni',
                'url_bukalapak' => 'https://bukalapak.com',
            ],
            [
                'name' => 'Tiwi',
                'email' => 'tiwi@gmail.com',
                'phone' => $faker->phoneNumber(),
                'images' => json_encode($faker->imageUrl()),
                'city_code' => City::InRandomOrder()->first()->code,
                'store_name' => $faker->name(),
                'store_address' => $faker->address(),
                'store_city' => $faker->city,
                'username_instagram' => '@daviena.skincarebdg',
                'url_instagram' => 'https://instagram.com',
                'username_tiktok' => '@daviena.skincarebdg',
                'url_tiktok' => 'https://tiktok.com',
                'username_tokopedia' => '@daviena.skincarebdg',
                'url_tokopedia' => 'https://tokopedia.com',
                'username_shopee' => '@daviena.skincarebdg',
                'url_shopee' => 'https://shopee.com',
                'username_bukalapak' => '@daviena.skincarebdg',
                'url_bukalapak' => 'https://bukalapak.com',
            ]
        ];

        foreach($partner as $key){
            $partner_payload = [
                'equal_id' => 0,
                'name' => $key['name'],
                'email' => $key['email'],
                'phone' => $key['phone'],
                'city_code' => $key['city_code'],
                'images' => $key['images']
            ];
            $partner = PartnerEqual::create($partner_payload);
            $partner_store_payload = [
                'equal_id' => 0,
                'partner_equal_id' => $partner->id,
                'store_name' => $key['store_name'],
                'store_address' => $key['store_address'],
                'store_city' => $key['store_city']
            ];
            $partner_store = $partner->partner_store()->create($partner_store_payload);
            if ($key['url_instagram'] || $key['username_instagram']) {
                $payload_ig = [
                    'equal_id' => 0,
                    'type' => 'Instagram',
                    'username' => $key['username_instagram'],
                    'url' => $key['url_instagram']
                ];
                $partner_sosial_media_1 = $partner_store->partner_sosial_media()->create($payload_ig);
            }
            if ($key['url_tiktok'] || $key['username_tiktok']) {
                $payload_tiktok = [
                    'equal_id' => 0,
                    'type' => 'Tiktok',
                    'username' => $key['username_tiktok'],
                    'url' => $key['url_tiktok']
                ];
                $partner_sosial_media_2 = $partner_store->partner_sosial_media()->create($payload_tiktok);
            }
            if ($key['url_tokopedia'] || $key['username_tokopedia']) {
                $payload_tokopedia = [
                    'equal_id' => 0,
                    'type' => 'Tokopedia',
                    'username' => $key['username_tokopedia'],
                    'url' => $key['url_tokopedia']
                ];
                $partner_sosial_media_3 = $partner_store->partner_sosial_media()->create($payload_tokopedia);
            }
            if ($key['url_shopee'] || $key['username_shopee']) {
                $payload_shopee = [
                    'equal_id' => 0,
                    'type' => 'Shopee',
                    'username' => $key['username_shopee'],
                    'url' => $key['url_shopee']
                ];
                $partner_sosial_media_4 = $partner_store->partner_sosial_media()->create($payload_shopee);
            }
            if ($key['url_bukalapak'] || $key['username_bukalapak']) {
                $payload_bk = [
                    'equal_id' => 0,
                    'type' => 'Bukalapak',
                    'username' => $key['username_bukalapak'],
                    'url' => $key['url_bukalapak']
                ];
                $partner_sosial_media_5 = $partner_store->partner_sosial_media()->create($payload_bk);
            }
        }

        $official_partner_home = [1,2,3,4];

        foreach($official_partner_home as $key){
            OfficialPartnerHome::create([
                'partner_equal_id' => $key
            ]);
        }
    }

}
