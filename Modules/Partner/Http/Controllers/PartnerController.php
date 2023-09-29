<?php

namespace Modules\Partner\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Partner\Entities\PartnerEqual;
use Modules\Partner\Entities\PartnerSosialMedia;
use Modules\Partner\Entities\PartnerStore;
use Modules\Partner\Entities\OfficialPartner;
use Modules\Partner\Entities\OfficialPartnerDetail;
use Modules\Partner\Entities\OfficialPartnerHome;
use Modules\Partner\Http\Requests\PartnerWebhookCreateBulkRequest;
use Modules\Partner\Http\Requests\PartnerWebhookCreateRequest;
use Modules\Partner\Http\Requests\PartnerWebhookUpdateRequest;

class PartnerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $post = $request->json()->all();
        $query = PartnerEqual::query();

        if (isset($post['city_code']) && is_array($post['city_code'])) {
            $query->whereIn('city_code', $post['city_code']);
        }

        if(isset($post['search'])){
            $searchTerm = $post['search'];
            $query->whereHas('partner_store', function ($subquery) use ($searchTerm) {
                $subquery->where('store_name', 'like', '%' . $searchTerm . '%');
            });
        }

        if(isset($post['type'])){
            $query->where('type', $post['type']);
        }

        $paginate = isset($post['pagination_total_row']) ? (int) $post['pagination_total_row'] : 8;
        $page = isset($post['page']) ? (int) $post['page'] : 1;
        $query->with('city.province', 'partner_store.partner_sosial_media');
        $partners = $query->paginate($paginate, ['*'], 'page', $page);
        foreach ($partners->items() as $partner) {
            $imageUrl = $partner->images;
            if (!str_contains($imageUrl, 'https://')) {
                $partner->images = asset(json_decode($imageUrl));
            } else {
                $partner->images = json_decode($imageUrl);

            }
        }

        return $this->ok('', $partners);
    }

    public function show(PartnerEqual $partner): JsonResponse
    {
        $partner_result = $partner->load('city.province', 'partner_store.partner_sosial_media');
        $imageUrl = $partner_result->images;
        if (!str_contains($imageUrl, 'https://')) {
            $imageUrl = asset(json_decode($imageUrl));
        } else {
            $imageUrl = json_decode($imageUrl);
        }
        $partner_result->images = $imageUrl;
        return $this->ok('success', $partner_result);
    }

    public function webHookCreate(PartnerWebhookCreateRequest $request)
    {
        $post = $request->json()->all();
        $payload_partner_equal = [
            'equal_id' => $post['id'],
            'name' => $post['name'],
            'email' => $post['email'],
            'phone' => $post['phone'],
            'id_member' => $post['id_member'],
            'is_suspended' => $post['is_suspended'],
        ];
        $partner_equal = PartnerEqual::create($payload_partner_equal);
        $partner_equal_id = $partner_equal->id;
        $val_partner_store = PartnerStore::where(['equal_id' => $post['mitra_store']['id']])->first();
        if($val_partner_store){
            $partner_store = PartnerStore::find($val_partner_store->id)->update([
                'partner_equal_id' => $partner_equal_id,
                'equal_id' => $post['mitra_store']['id'],
                'store_name' => $post['mitra_store']['store_name'],
                'store_address' => $post['mitra_store']['store_address'],
                'store_city' => $post['mitra_store']['store_city'],
            ]);
            $partner_store_id = $val_partner_store->id;
        } else {
            $partner_store = PartnerStore::create([
                'partner_equal_id' => $partner_equal_id,
                'equal_id' => $post['mitra_store']['id'],
                'store_name' => $post['mitra_store']['store_name'],
                'store_address' => $post['mitra_store']['store_address'],
                'store_city' => $post['mitra_store']['store_city']
            ]);
            $partner_store_id = $partner_store->id;
        }
        foreach($post['mitra_store']['social_media_stores'] as $key){
            $val_sosial_media = PartnerSosialMedia::where([
                'equal_id' => $key['id'],
                'partner_store_id' => $partner_store_id
            ])->first();
            $type = '';
            switch($key['type']){
                case "1":
                    $type = "Instagram";
                    break;
                case "2":
                    $type = "Tiktok";
                    break;
                case "3":
                    $type = "Tokopedia";
                    break;
                case "4":
                    $type = "Shopee";
                    break;
                case "5":
                    $type = "Bukalapak";
                    break;
            }
            if($val_sosial_media){
                $partner_sosial_media = PartnerSosialMedia::find($val_sosial_media->id)->update([
                    'equal_id' => $key['id'],
                    'partner_store_id' => $partner_store_id,
                    'type' => $type,
                    'url' => $key['url']
                ]);
            } else {
                $partner_sosial_media = PartnerSosialMedia::create([
                    'equal_id' => $key['id'],
                    'partner_store_id' => $partner_store_id,
                    'type' => $type,
                    'url' => $key['url']
                ]);
            }
        }

        return $this->ok("succes", $post);
    }

    public function webHookUpdate(PartnerWebhookUpdateRequest $request)
    {
        $post = $request->json()->all();
        $payload_partner_equal = [
            'equal_id' => $post['id'],
            'name' => $post['name'],
            'email' => $post['email'],
            'phone' => $post['phone'],
            'id_member' => $post['id_member'],
            'is_suspended' => $post['is_suspended'],
        ];
        $val_partner_equal = PartnerEqual::where(['equal_id' => $post['id']])->first();
        $partner_equal = PartnerEqual::find($val_partner_equal->id)->update($payload_partner_equal);
        $partner_equal_id = $val_partner_equal->id;
        $val_partner_store = PartnerStore::where(['equal_id' => $post['mitra_store']['id']])->first();
        if($val_partner_store){
            $partner_store = PartnerStore::find($val_partner_store->id)->update([
                'partner_equal_id' => $partner_equal_id,
                'equal_id' => $post['mitra_store']['id'],
                'store_name' => $post['mitra_store']['store_name'],
                'store_address' => $post['mitra_store']['store_address'],
                'store_city' => $post['mitra_store']['store_city'],
            ]);
            $partner_store_id = $val_partner_store->id;
        } else {
            $partner_store = PartnerStore::create([
                'partner_equal_id' => $partner_equal_id,
                'equal_id' => $post['mitra_store']['id'],
                'store_name' => $post['mitra_store']['store_name'],
                'store_address' => $post['mitra_store']['store_address'],
                'store_city' => $post['mitra_store']['store_city']
            ]);
            $partner_store_id = $partner_store->id;
        }
        foreach($post['mitra_store']['social_media_stores'] as $key){
            $val_sosial_media = PartnerSosialMedia::where([
                'equal_id' => $key['id'],
                'partner_store_id' => $partner_store_id
            ])->first();
            $type = '';
            switch($key['type']){
                case "1":
                    $type = "Instagram";
                    break;
                case "2":
                    $type = "Tiktok";
                    break;
                case "3":
                    $type = "Tokopedia";
                    break;
                case "4":
                    $type = "Shopee";
                    break;
                case "5":
                    $type = "Bukalapak";
                    break;
            }
            if($val_sosial_media){
                $partner_sosial_media = PartnerSosialMedia::find($val_sosial_media->id)->update([
                    'equal_id' => $key['id'],
                    'partner_store_id' => $partner_store_id,
                    'type' => $type,
                    'url' => $key['url']
                ]);
            } else {
                $partner_sosial_media = PartnerSosialMedia::create([
                    'equal_id' => $key['id'],
                    'partner_store_id' => $partner_store_id,
                    'type' => $type,
                    'url' => $key['url']
                ]);
            }
        }

        return $this->ok("succes", $post);
    }

    public function webHookDelete(Request $request)
    {
        $post = $request->json()->all();
        $val_partner_store = PartnerStore::where(['equal_id' => $post['mitra_store']['id']])->first();
        if($val_partner_store){
            if($post['mitra_store']['social_media_stores']){
                foreach($post['mitra_store']['social_media_stores'] as $key){
                    $val_sosial_media = PartnerSosialMedia::where([
                        'equal_id' => $key['id'],
                        'partner_store_id' => $val_partner_store->id
                    ])->first();
                    if($val_sosial_media)
                        $deleted_sosial_media = PartnerSosialMedia::find($val_sosial_media->id)->delete();
                }
            }
        }
        if($val_partner_store)
            $deleted_store = PartnerStore::find($val_partner_store->id)->delete();
        $val_partner_equal = PartnerEqual::where(['equal_id' => $post['id']])->first();
        if($val_partner_equal)
            $partner = PartnerEqual::find($val_partner_equal->id)->delete();
        return $this->ok("success","");
    }

    public function official_partner(){
        $official = OfficialPartner::first();
        $detail = OfficialPartnerDetail::all();
        return $this->ok('success', [
            'official' => $official,
            'detail' => $detail
        ]);
    }

    public function official_partner_home(){
        $officialPartnerHome = OfficialPartnerHome::with('partner_equal.partner_store.partner_sosial_media')->get();
        $officialPartnerHome->transform(function ($item, $key) {
            $item->partner_equal->images = [json_decode($item->partner_equal->images)];
            return $item;
        });
        return $this->ok("success", $officialPartnerHome);
    }

    public function webHookCreateBulk(PartnerWebhookCreateBulkRequest $request)
    {
        $postData = $request->json(); // Mengambil data JSON dari permintaan
        $data = $postData->all(); // Mendapatkan seluruh data dalam array

        // Loop melalui setiap elemen dalam data yang diterima
        foreach ($data as $item) {
            $partner_equal = PartnerEqual::create([
                'equal_id' => $item['id'],
                'name' => $item['name'],
                'email' => $item['email'],
                'phone' => $item['phone'],
                'id_member' => $item['id_member'],
                'is_suspended' => $item['is_suspended'],
            ]);

            $mitra_store = $item['mitra_store']; // Mendapatkan data mitra toko dari elemen saat ini

            // Mencari atau membuat entitas PartnerStore berdasarkan 'id' mitra toko
            $partner_store = PartnerStore::firstOrNew(['equal_id' => $mitra_store['id']]);
            $partner_store->partner_equal_id = $partner_equal->id;
            $partner_store->store_name = $mitra_store['store_name'];
            $partner_store->store_address = $mitra_store['store_address'];
            $partner_store->store_city = $mitra_store['store_city'];
            $partner_store->save();

            // Loop melalui setiap sosial media toko
            foreach ($mitra_store['social_media_stores'] as $social_media_store) {
                $type = '';
                switch ($social_media_store['type']) {
                    case "1":
                        $type = "Instagram";
                        break;
                    case "2":
                        $type = "Tiktok";
                        break;
                    case "3":
                        $type = "Tokopedia";
                        break;
                    case "4":
                        $type = "Shopee";
                        break;
                    case "5":
                        $type = "Bukalapak";
                        break;
                }
                // Mencari atau membuat entitas PartnerSocialMedia berdasarkan 'id' sosial media
                $partner_sosial_media = PartnerSosialMedia::firstOrNew([
                    'equal_id' => $social_media_store['id'],
                    'partner_store_id' => $partner_store->id,
                ]);
                $partner_sosial_media->type = $type;
                $partner_sosial_media->url = $social_media_store['url'];
                $partner_sosial_media->save();
            }
        }

        return $this->ok("success", $data);
    }
}
