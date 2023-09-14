<?php

namespace Modules\Partner\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Partner\Entities\OfficialPartner;
use Modules\Partner\Entities\OfficialPartnerDetail;

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

        // $partner = [
        //     [
        //         'name' => 'Desy Putri Sukmawati',
        //         'email' => 'desy@gmail.com',
        //         ''
        //     ]
        // ]

    }

}
